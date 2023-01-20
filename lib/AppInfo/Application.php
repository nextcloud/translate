<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Llm\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'llm';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
