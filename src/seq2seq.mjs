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
const VOC_SIZE = 250112
const TEMPERATURE = 0.1
const REPETITION_PENALTY = 1
const TOP_K = VOC_SIZE
const TOP_P = 0.9


async function main(inputText, strategy, max_length, min_length) {
	if (inputText === '-') {
		inputText = await getStdin()
	}

	let preprocessor = await sentencePieceProcessor(null, fs.readFileSync(__dirname+"/../model/spiece.model"));

	const encoderSession = await ort.InferenceSession.create(__dirname+'/../model/encoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu']
	});

	const decoderSession = await ort.InferenceSession.create(__dirname+'/../model/decoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu']
	});

	let inputData = preprocessor.encodeIds(inputText).filter(el => el <= 250111 && el>=-250112); // TODO: Look into proper tokenization algo
	console.log(preprocessor.decodeIds(inputData))
	let decoderInputData = Int32Array.from([T5_pad_token_id])
	let generation = ''
	const filterOutEOS = tf.tensor(Array(VOC_SIZE).fill(1).map((_, i) => T5_eos_token_id === i? -Infinity : 1))
	const inputTensor = new ort.Tensor('int64', BigInt64Array.from(Array.from(inputData).map(num => BigInt(num))), [1, inputData.length]);
	const attentionMask = new ort.Tensor('int64', BigInt64Array.from(new Array(inputData.length).fill(1n)), [1, inputData.length]);

	const {last_hidden_state: lastHiddenState} = await encoderSession.run({
		input_ids: inputTensor,
		attention_mask: attentionMask
	})

	for (let i = 0; i<max_length; i++) {
		const decoderInputTensor = new ort.Tensor('int64', BigInt64Array.from(Array.from(decoderInputData).map(num => BigInt(num))), [1, decoderInputData.length]);

		// feed inputs and run
		const results = await decoderSession.run({
			encoder_attention_mask: attentionMask,
			input_ids: decoderInputTensor,
			encoder_hidden_states: lastHiddenState
		})

		const id = tf.tidy(() => {
			let result = tf.tensor(results.logits.data, results.logits.dims)
			if (strategy === 'sampling') {
				result = result.div(TEMPERATURE)
			}

			let probsSqueezed = result.squeeze([0]).unstack().pop()

			if (min_length && min_length > 0 && min_length > i) {
				probsSqueezed = tf.where(tf.isInf(filterOutEOS), filterOutEOS, probsSqueezed)
			}

			if (REPETITION_PENALTY > 1) {
				const decoderInput = tf.tensor(decoderInputData)
				const scores = tf.gather(probsSqueezed, decoderInput)
				const penalty = tf.where(tf.greater(scores, 0), tf.onesLike(scores).div(REPETITION_PENALTY), tf.onesLike(scores).mul(REPETITION_PENALTY))
				const penalizedScores = scores.mul(penalty)
				const scattered = tf.scatterND(decoderInput, penalizedScores, probsSqueezed.shape).squeeze()
				probsSqueezed = tf.where(tf.notEqual(scattered, 0), scattered, probsSqueezed)
			}

			let id

			if (strategy === 'sampling') {
				const softprobsSqueezed = tf.softmax(probsSqueezed)
				const {values: probsTopK, indices: idsTopK} = tf.topk(softprobsSqueezed, TOP_K, true)
				const {values: logitsTopK} = tf.topk(probsSqueezed, TOP_K, true)
				const cumProbs = tf.cumsum(probsTopK, 0)
				let scoreMask = tf.less(cumProbs, TOP_P)
				const maskScores = tf.fill(cumProbs.shape, -Infinity)
				scoreMask = tf.concat([tf.ones([1], 'bool'), tf.slice(scoreMask, 1, -1)], -1)
				const topkLogits = tf.where(scoreMask, logitsTopK, maskScores)
				const samples = tf.multinomial(topkLogits, 1)
				id = tf.gather(idsTopK, samples)
			}

			if (strategy === 'greedy') {
				id = tf.argMax(probsSqueezed)
			}

			return id
		})

		const token = await id.data()

		decoderInputData = Int32Array.from([...decoderInputData, ...token])

		if (token[0] === T5_eos_token_id) {
			break
		}
		console.log(preprocessor.decodeIds([...decoderInputData]))
		id.dispose()
	}
	generation = preprocessor.decodeIds([...decoderInputData])
	return generation
}

console.log(await main(
	process.argv[4],
	process.argv[2],
	parseInt(process.argv[3]),
	Math.round(parseInt(process.argv[3])*0.8)
));
