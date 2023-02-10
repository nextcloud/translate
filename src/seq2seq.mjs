import ort from 'onnxruntime-node';
import path from 'path';
import { fileURLToPath } from 'url';
import tf from '@tensorflow/tfjs'
import getStdin from "get-stdin";
import vocab from '../models/vocab.json' assert { type: "json" };

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

global.__dirname = __dirname

const T5_pad_token_id = 58100
const T5_eos_token_id = 0
const VOC_SIZE = 58101
const TEMPERATURE = 0.5
const REPETITION_PENALTY = 1
const TOP_K = VOC_SIZE
const TOP_P = 0.95


async function main(model, inputText) {
	if (inputText === '-') {
		inputText = await getStdin()
	}

	const encoderSession = await ort.InferenceSession.create(__dirname+'/../models/'+model+'/encoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu']
	})

	const decoderSession = await ort.InferenceSession.create(__dirname+'/../models'+model+'/decoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu']
	})

	const generatedSentences = []

	for (const sentence of inputText.split('.')) {
		let inputData = tokenize(sentence).map(token => vocab[token])
		const reverseVocab = Object.fromEntries(Object.entries(vocab).map(([token, id]) => [id, token]))

		let decoderInputData = Int32Array.from([T5_pad_token_id])
		let generation = ''
		const filterOutEOS = tf.tensor(Array(VOC_SIZE).fill(1).map((_, i) => T5_eos_token_id === i ? -Infinity : 1))
		const inputTensor = new ort.Tensor('int64', BigInt64Array.from([...Array.from(inputData).map(num => BigInt(num)), 0n]), [1, inputData.length + 1]);
		const attentionMask = new ort.Tensor('int64', BigInt64Array.from(new Array(inputData.length + 1).fill(1n)), [1, inputData.length + 1]);

		const {last_hidden_state: lastHiddenState} = await encoderSession.run({
			input_ids: inputTensor,
			attention_mask: attentionMask
		})

		for (let i = 0; i < max_length; i++) {
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

				let id = tf.argMax(probsSqueezed)

				return id
			})

			const token = await id.data()
			id.dispose()

			decoderInputData = Int32Array.from([...decoderInputData, ...token])

			if (token[0] === T5_eos_token_id) {
				break
			}
		}

		generation = [...decoderInputData.slice(1, -1)].map(tokenId => reverseVocab[tokenId].replace('\u2581', ' ')).join('')
		generatedSentences.push(generation.substring(1))
	}

	return generatedSentences.join('. ')
}

console.log(await main(
	process.argv[2],
	process.argv[3]
))


function tokenize(text) {
	const words = text.replace(/\s/, ' ').replace('\n', ' ').split(' ').filter(word => word !== '')
	const tokens = []
	for (let i=0; i<words.length; i++) {
		let currentToken = '\u2581'
		let rest = ''
		currentToken += words[i]
		let n = 0
		let j = 0
		do {
			while (typeof vocab[currentToken] === 'undefined' && currentToken.length > 0) {
				rest = currentToken.substring(currentToken.length-1) + rest
				currentToken = currentToken.substring(0, currentToken.length-1)
			}
			if (currentToken === '') {
				//console.warn('Could not tokenize '+words[i])
				break
			}
			tokens.push(currentToken)
			currentToken = rest
			rest = ''
			if (n++ > 100) throw new Error('PEBCAK')
		}while(currentToken.length)
	}
	return tokens
}
