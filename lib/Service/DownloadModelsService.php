<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

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
     * @param string $model
     * @param bool $force
     * @return bool
     * @throws \Exception
     */
	public function download(string $model, bool $force = false) : bool {
		if (!in_array($model, self::AVAILABLE_MODELS, true)) {
			return false;
		}
		$modelPath = __DIR__ . '/../../models/'.$model;
		if (file_exists($modelPath)) {
			if ($force) {
				// remove model directory
				$it = new RecursiveDirectoryIterator($modelPath, FilesystemIterator::SKIP_DOTS);
				$files = new RecursiveIteratorIterator($it,
					RecursiveIteratorIterator::CHILD_FIRST);
                /** @var \SplFileInfo $file */
                foreach ($files as $file) {
					if ($file->isDir()) {
						rmdir($file->getRealPath());
					} else {
						unlink($file->getRealPath());
					}
				}
				rmdir($modelPath);
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
		$targetPath = __DIR__ . '/../../models/';
		$tarManager->extractList($tarFiles, $targetPath, $modelPath . '/');
		unlink($archivePath);
		return true;
	}

	public function getArchiveUrl(string $model): string {
		return "https://github.com/nextcloud-releases/translate/releases/download/v1.1.3/$model.tar.gz";
	}
}
