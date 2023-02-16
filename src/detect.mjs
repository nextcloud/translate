import cld from 'cldpre'
import getStdin from 'get-stdin'

async function main(inputText) {
	if (inputText === '-') {
		inputText = await getStdin()
	}

	const result = await cld.detect(inputText)
	console.log(result.languages[0]?.code)
}

await main(process.argv[2])
