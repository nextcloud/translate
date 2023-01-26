import ort from 'onnxruntime-node';
import {cleanText, sentencePieceProcessor} from './sentencepiece.mjs'
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs'
import tf from '@tensorflow/tfjs'
import getStdin from "get-stdin";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

global.__dirname = __dirname

const T5_pad_token_id = 0
const T5_eos_token_id = 1
const TEMPERATURE = 0.2
const TOP_K = 50
const VOC_SIZE = 250112

async function main(inputText, strategy, max_length, min_length, repetition_penalty = 1) {
	if (inputText === '-') {
		inputText = await getStdin()
	}
	let preprocessor = await sentencePieceProcessor(null, fs.readFileSync(__dirname+"/../model/spiece.model"));

	const session = await ort.InferenceSession.create(__dirname+'/../model/model.onnx');

	let inputData = preprocessor.encodeIds(cleanText(inputText)).filter(el => el <= 250111 && el>=-250112); // TODO: Look into proper tokenization algo
	let decoderInputData = Int32Array.from([T5_pad_token_id])
	let generation = ''
	const filterOutEOS = tf.tensor(Array(VOC_SIZE).fill(1).map((_, i) => T5_eos_token_id === i? -Infinity : 1))

	for (let i = 0; i<max_length; i++) {
		const inputTensor = new ort.Tensor('int64', BigInt64Array.from(Array.from(inputData).map(num => BigInt(num))), [1, inputData.length]);
		const attentionMask = new ort.Tensor('int64', BigInt64Array.from(new Array(inputData.length).fill(1n)), [1, inputData.length]);
		const decoderInputTensor = new ort.Tensor('int64', BigInt64Array.from(Array.from(decoderInputData).map(num => BigInt(num))), [1, decoderInputData.length]);
		const decoderAttentionMask = new ort.Tensor('int64', BigInt64Array.from(new Array(decoderInputData.length).fill(1n)), [1, decoderInputData.length]);

		// prepare feeds. use model input names as keys.
		const feeds = { input_ids: inputTensor, attention_mask: attentionMask, decoder_input_ids: decoderInputTensor, decoder_attention_mask: decoderAttentionMask };

		// feed inputs and run
		const results = await session.run(feeds);
		let result = tf.tensor(results.logits.data, results.logits.dims).div(TEMPERATURE)
		if (strategy === 'sampling') {
			result = result.div(TEMPERATURE)
		}

		let probsSqueezed = result.squeeze([0]).unstack().pop()

		if (min_length && min_length > 0 && min_length > i) {
			probsSqueezed = tf.where(tf.isInf(filterOutEOS), filterOutEOS, probsSqueezed)
		}


		if (repetition_penalty > 1) {
			const decoderInput = tf.tensor(decoderInputData)
			const scores = tf.gather(probsSqueezed, decoderInput)
			const penalty = tf.where(tf.greater(scores, 0), tf.onesLike(scores).div(repetition_penalty), tf.onesLike(scores).mul(repetition_penalty))
			const penalizedScores = scores.mul(penalty)
			const scattered = tf.scatterND(decoderInput, penalizedScores, probsSqueezed.shape).squeeze()
			probsSqueezed = tf.where(tf.notEqual(scattered, 0), scattered, probsSqueezed)
		}

		let id

		if (strategy === 'sampling') {
			const {values: probsTopK, indices: idsTopK} = tf.topk(probsSqueezed, TOP_K)
			const samples = tf.multinomial(probsTopK, 1, Math.round(Math.random()*100000))
			id = tf.gather(idsTopK, samples)
		}

		if (strategy === 'greedy') {
			id = tf.argMax(probsSqueezed)
		}

		const token = await id.data()
		generation = preprocessor.decodeIds([...decoderInputData, ...token])
		decoderInputData = Int32Array.from([...decoderInputData, ...token])

		if (token[0] === T5_eos_token_id) {
			break
		}
		console.log(generation)
	}
	return generation
}

console.log(await main(process.argv[4], process.argv[2], parseInt(process.argv[3]), 100, 1.5));
