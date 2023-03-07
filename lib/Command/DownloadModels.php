<?php
/*
 * Copyright (c) 2022 The Recognize contributors.
 * This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
 */

namespace OCA\Translate\Command;

use OCA\Translate\Service\DownloadModelsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadModels extends Command {
	private DownloadModelsService $downloader;

	public const DEFAULT_LANGS = [
		'de','en','es','fr','zh'
	];

	public function __construct(DownloadModelsService $downloader) {
		parent::__construct();
		$this->downloader = $downloader;
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('translate:download-models')
			->setDescription('Download the necessary machine learning models');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force download even if the model(s) are downloaded already');
		$this->addArgument('langs', InputArgument::IS_ARRAY, 'The languages to download', self::DEFAULT_LANGS);
	}

	/**
	 * Execute the command
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$langs = array_intersect($input->getArgument('langs'), self::DEFAULT_LANGS);
			foreach ($langs as $lang1) {
				foreach ($langs as $lang2) {
					if ($lang1 === $lang2) {
						continue;
					}
					$model = $lang1 . '-' . $lang2;
					$output->writeln("Downloading model ".$model);
					if ($this->downloader->download($model, $input->getOption('force'))) {
						$output->writeln('Successful');
					} else {
						$output->writeln('Model is not available, skipping');
					}
				}
			}
		} catch (\Exception $ex) {
			$output->writeln('<error>Failed to download models</error>');
			$output->writeln($ex->getMessage());
			return 1;
		}

		return 0;
	}
}
