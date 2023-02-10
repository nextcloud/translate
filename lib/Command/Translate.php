<?php
/*
 * Copyright (c) 2022 The Recognize contributors.
 * This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
 */

namespace OCA\Translate\Command;

use OCA\Translate\Service\TranslateService;
use OCA\Recognize\Classifiers\Audio\MusicnnClassifier;
use OCA\Recognize\Classifiers\Images\ClusteringFaceClassifier;
use OCA\Recognize\Classifiers\Images\ImagenetClassifier;
use OCA\Recognize\Classifiers\Images\LandmarksClassifier;
use OCA\Recognize\Classifiers\Video\MovinetClassifier;
use OCA\Recognize\Db\QueueFile;
use OCA\Recognize\Exception\Exception;
use OCA\Recognize\Service\Logger;
use OCA\Recognize\Service\SettingsService;
use OCA\Recognize\Service\StorageService;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Translate extends Command {

	private TranslateService $llm;

	public function __construct(TranslateService $llm) {
		parent::__construct();
		$this->llm = $llm;
	}

	/**
	 * Configure the command
	 *
	 * @return void
	 */
	protected function configure() {
		$this->setName('llm:translate')
			->setDescription('Summarizes the input')
			->addArgument('from')
			->addArgument('to')
			->addArgument('input');
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
			$output->writeln($this->llm->seq2seqGreedy($input->getArgument('input')));
			return 0;
		}catch(\RuntimeException $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
	}
}
