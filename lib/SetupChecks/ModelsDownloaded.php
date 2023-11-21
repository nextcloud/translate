<?php

namespace OCA\Translate\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class ModelsDownloaded implements ISetupCheck {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Checking whether translate app models are downloaded');
	}

	/**
	 * @inheritDoc
	 */
	public function getCategory(): string {
		return 'ai';
	}

	public function run(): SetupResult {
		$modelPath = __DIR__ . '/../../models/';
		$iterator = new \DirectoryIterator($modelPath);
		if (iterator_count($iterator) > 3) {
			return SetupResult::success($this->l10n->t('Translate app models are downloaded and available for use.'));
		} else {
			return SetupResult::error($this->l10n->t('No translate app models are downloaded. You can download models for the languages you want on the command line with the appropriate occ command.'));
		}
	}
}
