<?php

// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Translate\SetupChecks;

use OCA\Translate\Service\SettingsService;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class NodejsWorks implements ISetupCheck {

	public function __construct(
		private IL10N $l10n,
		private SettingsService $settingsService,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Checking whether the Node.js executable of the translate app works');
	}

	/**
	 * @inheritDoc
	 */
	public function getCategory(): string {
		return 'ai';
	}

	public function run(): SetupResult {
		try {
			exec($this->settingsService->getSetting('node_binary') . ' --version' . ' 2>&1', $output, $returnCode);
			if ($returnCode !== 0) {
				throw new \Exception();
			}
		} catch (\Throwable $e) {
			return SetupResult::error($this->l10n->t('The node.js executable for the translate app is not working. Go to the admin settings of the translate app to troubleshoot.'));
		}

		return SetupResult::success($this->l10n->t('The node.js executable for the translate app is working.'));
	}
}
