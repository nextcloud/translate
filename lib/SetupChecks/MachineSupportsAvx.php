<?php

namespace OCA\Translate\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MachineSupportsAvx implements ISetupCheck {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l10n->t('Checking whether your server supports AVX CPU instructions.');
	}

	/**
	 * @inheritDoc
	 */
	public function getCategory(): string {
		return 'ai';
	}

	public function run(): SetupResult {
		try {
			$cpuinfo = file_get_contents('/proc/cpuinfo');
			if ($cpuinfo === false) {
				throw new \Exception();
			} else if(strpos($cpuinfo, 'avx') === false) {
				return SetupResult::error($this->l10n->t('Your server does not support AVX CPU instructions. The translate app will not work here.'));
			}
		} catch (\Throwable $e) {
			return SetupResult::warning($this->l10n->t('Could not check whether your server supports AVX CPU instructions. The translate app may not work.'));
		}

		return SetupResult::success($this->l10n->t('Your server supports AVX CPU instructions, which are needed by the translate app.'));
	}
}
