<?php

declare(strict_types=1);

/*
 * Copyright (c) 2022 The Recognize contributors.
 * This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
 */

namespace OCA\Translate\Controller;

use OCA\Translate\Service\SettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class AdminController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private SettingsService $settingsService) {
		parent::__construct($appName, $request);
	}

	public function avx(): JSONResponse {
		try {
			$cpuinfo = file_get_contents('/proc/cpuinfo');
		} catch (\Throwable $e) {
			return new JSONResponse(['avx' => null]);
		}
		return new JSONResponse(['avx' => $cpuinfo !== false && strpos($cpuinfo, 'avx') !== false]);
	}

	public function nodejs(): JSONResponse {
		try {
			exec($this->settingsService->getSetting('node_binary') . ' --version' . ' 2>&1', $output, $returnCode);
		} catch (\Throwable $e) {
			return new JSONResponse(['nodejs' => null]);
		}

		if ($returnCode !== 0) {
			return new JSONResponse(['nodejs' => false]);
		}

		$version = trim(implode("\n", $output));
		return new JSONResponse(['nodejs' => $version]);
	}

	/**
	 * @param string $setting
	 * @param scalar $value
	 * @return JSONResponse
	 */
	public function setSetting(string $setting, float|bool|int|string $value): JSONResponse {
		try {
			$this->settingsService->setSetting($setting, (string) $value);
			return new JSONResponse([], Http::STATUS_OK);
		} catch (\Exception $e) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}
	}

	public function getSetting(string $setting): JSONResponse {
		return new JSONResponse(['value' => $this->settingsService->getSetting($setting)]);
	}
}
