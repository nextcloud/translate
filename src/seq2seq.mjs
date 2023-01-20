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

async function main(inputText, strategy, max_length) {
	if (inputText === '-') {
		inputText = await getStdin()
	}
	let preprocessor = await sentencePieceProcessor(null, fs.readFileSync(__dirname+"/../model/spiece.model"));

	const session = await ort.InferenceSession.create(__dirname+'/../model/model.onnx');

	let inputData = preprocessor.encodeIds(cleanText(inputText)).filter(el => el <= 250111 && el>=-250112); // TODO: Look into proper tokenization algo
	let decoderInputData = Int32Array.from([T5_pad_token_id])
	let generation = ''
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
		const probsSqueezed = result.squeeze([0]).unstack().pop()

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
	}
	return generation
}

main(process.argv[4], process.argv[2], parseInt(process.argv[3]));
