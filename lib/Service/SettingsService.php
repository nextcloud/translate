<?php

declare(strict_types=1);

/*
 * Copyright (c) 2022 The Recognize contributors.
 * This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
 */

namespace OCA\Translate\Service;

use OCP\IConfig;

class SettingsService {
	/** @var array<string,string>  */
	private const DEFAULTS = [
		'threads' => '0',
		'node_binary' => '',
	];

	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getSetting(string $key): string {
		return $this->config->getAppValue('translate', $key, self::DEFAULTS[$key]);
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function setSetting(string $key, string $value): void {
		if (!array_key_exists($key, self::DEFAULTS)) {
			throw new \Exception('Unknown settings key '.$key);
		}
		$this->config->setAppValue('translate', $key, $value);
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
