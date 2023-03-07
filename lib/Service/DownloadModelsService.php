<?php
/*
 * Copyright (c) 2022 The Recognize contributors.
 * This file is licensed under the Affero General Public License version 3 or later. See the COPYING file.
 */

namespace OCA\Translate\Service;

use FilesystemIterator;
use OCA\Translate\Helper\TAR;
use OCP\Http\Client\IClientService;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DownloadModelsService {
	private IClientService $clientService;
	private bool $isCLI;

	public const AVAILABLE_MODELS = [
		"zh-en",
		"es-fr",
		"fr-de",
		"fr-en",
		"fr-es",
		"zh-de",
		"de-fr",
		"de-zh",
		"en-de",
		"en-es",
		"en-fr",
		"en-zh",
		"es-de",
		"es-en",
		"de-en",
		"de-es",
	];

	public function __construct(IClientService $clientService, bool $isCLI) {
		$this->clientService = $clientService;
		$this->isCLI = $isCLI;
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function download($model, $force = false) : bool {
		if (!in_array($model, self::AVAILABLE_MODELS, true)) {
			return false;
		}
		$targetPath = __DIR__ . '/../../models/'.$model;
		if (file_exists($targetPath)) {
			if ($force) {
				// remove model directory
				$it = new RecursiveDirectoryIterator($targetPath, FilesystemIterator::SKIP_DOTS);
				$files = new RecursiveIteratorIterator($it,
					RecursiveIteratorIterator::CHILD_FIRST);
				foreach ($files as $file) {
					if ($file->isDir()) {
						rmdir($file->getRealPath());
					} else {
						unlink($file->getRealPath());
					}
				}
				rmdir($targetPath);
			} else {
				return true;
			}
		}
		$archiveUrl = $this->getArchiveUrl($model);
		$archivePath = __DIR__ . '/../../'. $model .'.tar.gz';
		$timeout = $this->isCLI ? 0 : 480;
		$this->clientService->newClient()->get($archiveUrl, ['sink' => $archivePath, 'timeout' => $timeout]);
		$tarManager = new TAR($archivePath);
		$tarFiles = $tarManager->getFiles();
		$modelFolder = $tarFiles[0];
		$modelFiles = array_values(array_filter($tarFiles, function ($path) use ($modelFolder) {
			return str_starts_with($path, $modelFolder . '/') || str_starts_with($path, $modelFolder);
		}));
		$tarManager->extractList($modelFiles, $targetPath, $modelFolder . '/');
		unlink($archivePath);
		return true;
	}

	public function getArchiveUrl(string $model): string {
		return "https://github.com/nextcloud/translate/releases/download/v1.0.0/$model.tar.gz";
	}
}
