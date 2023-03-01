<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Translate\Provider;

use OCA\Translate\Service\TranslateService;
use OCP\ICacheFactory;
use OCP\Translation\IDetectLanguageProvider;
use OCP\Translation\ITranslationProvider;
use OCP\Translation\LanguageTuple;
use Psr\Log\LoggerInterface;

class Translation implements ITranslationProvider, IDetectLanguageProvider {
	private ICacheFactory $cacheFactory;

	private TranslateService $translator;

	private LoggerInterface $logger;

	public function __construct(ICacheFactory $cacheFactory, TranslateService $translator, LoggerInterface $logger) {
		$this->cacheFactory = $cacheFactory;
		$this->translator = $translator;
		$this->logger = $logger;
	}

	public function getName(): string {
		return 'Opus models by the University of Helsinki';
	}

	public function getAvailableLanguages(): array {
		$cache = $this->cacheFactory->createDistributed('translate');
		if ($cached = $cache->get('languages')) {
			return $cached;
		}

		$directoryIterator = new \DirectoryIterator(__DIR__ . '/../../models/');

		$availableLanguages = [];
		foreach ($directoryIterator as $dir) {
			if ($dir->isDot()) {
				continue;
			}
			if (!$dir->isDir()) {
				continue;
			}
			[$sourceLanguage, $targetLanguage] = explode('-', $dir->getFilename());
			$availableLanguages[] = new LanguageTuple($sourceLanguage, $sourceLanguage, $targetLanguage, $targetLanguage);
		}
		$cache->set('languages', $availableLanguages, 3600);
		return $availableLanguages;
	}

	public function detectLanguage(string $text): ?string {
		try {
			return trim($this->translator->detect($text));
		} catch(\RuntimeException $e) {
			$this->logger->warning('Language detection failed with: ' . $e->getMessage(), ['exception' => $e]);
			return null;
		}
	}

	public function translate(?string $fromLanguage, string $toLanguage, string $text): string {
		$fromLanguage = $fromLanguage ?? $this->detectLanguage($text);
		$model = $fromLanguage . '-' . $toLanguage;
		try {
			return trim($this->translator->seq2seq($model, $text));
		} catch(\RuntimeException $e) {
			$this->logger->warning('Translation failed with: ' . $e->getMessage(), ['exception' => $e]);
			return '';
		}
	}
}
