<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Translate\AppInfo;

use OCA\Translate\Provider\Translation;
use OCA\Translate\SetupChecks\MachineSupportsAvx;
use OCA\Translate\SetupChecks\ModelsDownloaded;
use OCA\Translate\SetupChecks\NodejsWorks;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
	public const APP_ID = 'translate';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerTranslationProvider(Translation::class);
		$context->registerSetupCheck(MachineSupportsAvx::class);
		$context->registerSetupCheck(ModelsDownloaded::class);
		$context->registerSetupCheck(NodejsWorks::class);
	}

	public function boot(IBootContext $context): void {
		// TODO: Implement boot() method.
	}
}
