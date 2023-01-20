<?php

namespace OCA\Llm\Service;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class LlmService
{
	public const STRATEGY_GREEDY = 'greedy';
	public const STRATEGY_SAMPLING = 'sampling';

	private IConfig $config;
	private string $nodeBinary;

	private LoggerInterface $logger;

	public function __construct(IConfig $config, LoggerInterface $logger) {
		$this->config = $config;
		$this->nodeBinary = $this->config->getAppValue('recognize', 'node_binary', '');
		$this->logger = $logger;
	}

	/**
	 * @param string $input
	 * @return string
	 * @throws \RuntimeException
	 */
	public function summarize(string $input): string {
		return $this->seq2seqGreedy($input . ' Summary:');
	}

	/**
	 * @param string $input
	 * @param $maxLength
	 * @param $timeout
	 * @return string
	 * @throws \RuntimeException
	 */
	public function seq2seqGreedy(string $input, $maxLength = 50, $timeout = 5*60): string {
		return $this->seq2seq($input, self::STRATEGY_GREEDY, $maxLength, $timeout);
	}

	/**
	 * @param string $input
	 * @param $maxLength
	 * @param $timeout
	 * @return string
	 * @throws \RuntimeException
	 */
	public function seq2seqSampling(string $input, $maxLength = 50, $timeout = 5*60): string {
		return $this->seq2seq($input, self::STRATEGY_SAMPLING, $maxLength, $timeout);
	}

	/**
	 * @param string $input
	 * @param $strategy
	 * @param $maxLength
	 * @param $timeout
	 * @throws \RuntimeException
	 * @return string
	 */
	public function seq2seq(string $input, $strategy, $maxLength = 50, $timeout = 5*60) : string {
		$command = [
			$this->nodeBinary,
			dirname(__DIR__, 2) . '/src/seq2seq.mjs',
			$strategy,
			$maxLength,
			$input
		];

		$this->logger->debug('Running '.var_export($command, true));

		$proc = new Process($command, __DIR__);
		$proc->setTimeout($timeout);
		try {
			$proc->start();
			$buffer = '';
			$errOut = '';
			foreach ($proc as $type => $data) {
				if ($type !== $proc::OUT) {
					$errOut .= $data;
					continue;
				}
				$buffer .= $data;
			}
			if ($proc->getExitCode() !== 0) {
				$this->logger->warning($errOut);
				throw new \RuntimeException('LLM process failed');
			}
			return $buffer;
		} catch (ProcessTimedOutException $e) {
			$this->logger->warning($errOut);
			throw new \RuntimeException('LLM process timeout');
		} catch (RuntimeException $e) {
			$this->logger->warning($errOut);
			throw new \RuntimeException('LLM process failed');
		}
	}
}
