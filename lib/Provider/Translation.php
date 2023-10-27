<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Marcel Klehr <mklehr@gmx.net>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Translate\Provider;

use OCA\Translate\Service\TranslateService;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\Translation\IDetectLanguageProvider;
use OCP\Translation\ITranslationProvider;
use OCP\Translation\LanguageTuple;
use Psr\Log\LoggerInterface;
use Punic\Language;

class Translation implements ITranslationProvider, IDetectLanguageProvider {
	private TranslateService $translator;

	private LoggerInterface $logger;
	private IL10N $l;
    private ICacheFactory $cacheFactory;

    public function __construct(ICacheFactory $cacheFactory, TranslateService $translator, LoggerInterface $logger, IL10N $l) {
		$this->cacheFactory = $cacheFactory;
		$this->translator = $translator;
		$this->logger = $logger;
		$this->l = $l;
	}

	public function getName(): string {
		return 'Opus models by the University of Helsinki';
	}

	public function getAvailableLanguages(): array {
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
			// Punic is a nextcloud/server dependency
			$sourceLanguageName = Language::getName($sourceLanguage, $this->l->getLanguageCode());
			$targetLanguageName = Language::getName($targetLanguage, $this->l->getLanguageCode());
			$availableLanguages[] = new LanguageTuple($sourceLanguage, $sourceLanguageName, $targetLanguage, $targetLanguageName);
		}
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
        if ($fromLanguage === null) {
            throw new \RuntimeException('Could not detect language');
        }
		$model = $fromLanguage . '-' . $toLanguage;
		try {
			return trim($this->translator->seq2seq($model, $text));
		} catch(\RuntimeException $e) {
			$this->logger->warning('Translation failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw $e;
		}
	}
}
