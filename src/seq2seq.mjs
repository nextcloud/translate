import ort from 'onnxruntime-node';
import path from 'path';
import { fileURLToPath } from 'url';
import tf from '@tensorflow/tfjs'
import getStdin from "get-stdin";
import sbd from 'sbd';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

global.__dirname = __dirname

const THREADS = parseInt(process.env.THREADS_THREADS)

async function main(model, inputText) {
	if (inputText === '-') {
		inputText = await getStdin()
	}

	const encoderSession = await ort.InferenceSession.create(__dirname+'/../models/'+model+'/encoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu'],
		...(THREADS && {
			intraOpNumThreads: THREADS,
			interOpNumThreads: THREADS,
		})
	})

	const decoderSession = await ort.InferenceSession.create(__dirname+'/../models/'+model+'/decoder_model.onnx', {
		executionMode: 'sequential',
		executionProviders: ['cuda', 'cpu'],
		...(THREADS && {
			intraOpNumThreads: THREADS,
			interOpNumThreads: THREADS,
		})
	})

	const vocab = (await import('../models/'+model+'/vocab.json', { assert: { type: "json" } })).default
	const reverseVocab = Object.fromEntries(Object.entries(vocab).map(([token, id]) => [id, token]))

	const config = (await import('../models/'+model+'/config.json', { assert: { type: "json" } })).default
	const pad_token_id = config.pad_token_id
	const eos_token_id = config.eos_token_id

	const generatedSentences = []

	for (const sentence of sbd.sentences(inputText, {newline_boundaries: true})) {
		if(sentence.trim() === '') {
			continue
		}
		let inputData = tokenize(sentence, vocab).map(token => vocab[token])

		let decoderInputData = Int32Array.from([pad_token_id])
		let generation = ''
		const inputTensor = new ort.Tensor('int64', BigInt64Array.from([...Array.from(inputData).map(num => BigInt(num)), 0n]), [1, inputData.length + 1]);
		const attentionMask = new ort.Tensor('int64', BigInt64Array.from(new Array(inputData.length + 1).fill(1n)), [1, inputData.length + 1]);

		const {last_hidden_state: lastHiddenState} = await encoderSession.run({
			input_ids: inputTensor,
			attention_mask: attentionMask
		})

		while (true) {
			const decoderInputTensor = new ort.Tensor('int64', BigInt64Array.from(Array.from(decoderInputData).map(num => BigInt(num))), [1, decoderInputData.length]);

			// feed inputs and run
			const results = await decoderSession.run({
				encoder_attention_mask: attentionMask,
				input_ids: decoderInputTensor,
				encoder_hidden_states: lastHiddenState
			})

			const id = tf.tidy(() => {
				let result = tf.tensor(results.logits.data, results.logits.dims)
				let probsSqueezed = result.squeeze([0]).unstack().pop()
				let id = tf.argMax(probsSqueezed)
				return id
			})

			const token = await id.data()
			id.dispose()

			decoderInputData = Int32Array.from([...decoderInputData, ...token])

			if (token[0] === eos_token_id) {
				break
			}
		}

		generation = [...decoderInputData.slice(1, -1)]
			.map(tokenId => reverseVocab[tokenId])
			.filter(Boolean)
			.map(word => word.replace('\u2581', ' '))
			.join('')
		generatedSentences.push(generation.substring(1))
	}

	return generatedSentences.join(' ')
}

console.log(await main(
	process.argv[2],
	process.argv[3]
))


function tokenize(text, vocab) {
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
