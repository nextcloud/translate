<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: The recognize contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Translate\Service;

use OCP\AppFramework\Services\IAppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;

class SettingsService {
	/** @var array<string,string>  */
	private const DEFAULTS = [
		'threads' => '0',
		'node_binary' => '',
	];

	public function __construct(
		private IAppConfig $config,
	) {
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getSetting(string $key): string {
		try {
			return $this->config->getAppValueString($key, self::DEFAULTS[$key]);
		} catch (AppConfigTypeConflictException $e) {
			return self::DEFAULTS[$key];
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return void
	 * @throws AppConfigTypeConflictException
	 */
	public function setSetting(string $key, string $value): void {
		if (!array_key_exists($key, self::DEFAULTS)) {
			throw new \Exception('Unknown settings key '.$key);
		}
		$this->config->setAppValueString($key, $value);
	}

	/**
	 * @return array
	 */
	public function getAll(): array {
		$settings = [];
		foreach (array_keys(self::DEFAULTS) as $key) {
			$settings[$key] = $this->getSetting($key);
		}
		return $settings;
	}
}
