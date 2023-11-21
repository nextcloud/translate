<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Translate\Service;

use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

class TranslateService {
	private IConfig $config;
	private string $nodeBinary;

	private LoggerInterface $logger;

	public function __construct(IConfig $config, LoggerInterface $logger) {
		$this->config = $config;
		$this->nodeBinary = $this->config->getAppValue('translate', 'node_binary', '');
		$this->logger = $logger;
	}

	/**
	 * @param string $input
	 * @param $strategy
	 * @param $maxLength
	 * @param $timeout
	 * @throws \RuntimeException
	 * @return string
	 */
	public function seq2seq(string $model, string $input, int $timeout = 5 * 60) : string {
		if (!in_array($model, DownloadModelsService::AVAILABLE_MODELS, true)) {
			throw new \RuntimeException('Model not supported');
		}

		$modelPath = __DIR__ . '/../../models/'.$model;
		if (!file_exists($modelPath)) {
			throw new \RuntimeException('Model not downloaded');
		}

		$command = [
			$this->nodeBinary,
			dirname(__DIR__, 2) . '/src/seq2seq.mjs',
			$model,
			$input
		];
		$env = [];
		// Set cores
		$cores = $this->config->getAppValue('translate', 'threads', '0');
		if ($cores !== '0') {
			$env['TRANSLATE_THREADS'] = $cores;
		}

		$this->logger->debug('Running '.var_export($command, true));

		$proc = new Process($command, __DIR__);
		$proc->setEnv($env);
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
				throw new \RuntimeException('Translate process failed');
			}
			return $buffer;
		} catch (ProcessTimedOutException $e) {
			if (isset($errOut)) {
				$this->logger->warning($errOut);
			}
			throw new \RuntimeException('Translate process timeout');
		} catch (RuntimeException $e) {
			if (isset($errOut)) {
				$this->logger->warning($errOut);
			}
			throw new \RuntimeException('Translate process failed');
		}
	}

	/**
	 * @param string $input
	 * @param int $timeout
	 * @throws \RuntimeException
	 * @return string
	 */
	public function detect(string $text, int $timeout = 5 * 60) : string {
		$command = [
			$this->nodeBinary,
			dirname(__DIR__, 2) . '/src/detect.mjs',
			$text
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
				throw new \RuntimeException('Language detection process failed');
			}
			return $buffer;
		} catch (ProcessTimedOutException $e) {
			if (isset($errOut)) {
				$this->logger->warning($errOut);
			}
			throw new \RuntimeException('Language detection process timeout');
		} catch (RuntimeException $e) {
			if (isset($errOut)) {
				$this->logger->warning($errOut);
			}
			throw new \RuntimeException('Language detection process failed');
		}
	}
}
