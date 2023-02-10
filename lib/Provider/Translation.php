<?php
declare(strict_types=1);
namespace OCA\Translate\Provider;

use OCA\Translate\Service\TranslateService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Translation\ITranslationProvider;
use Psr\Log\LoggerInterface;

class Translation implements ITranslationProvider {
	private ICacheFactory $cacheFactory;

	private array $localCache = [];

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
			[$sourceLanguage, $targetLanguage] = explode('_', $dir);
			$availableLanguages[] = [
				'from' => [
					'code' => $sourceLanguage
				],
				'to' => [
					'code' => $targetLanguage
				],
			];
		}
		$cache->set('languages', $availableLanguages, 3600);
		return $availableLanguages;
	}

	public function detectLanguage(string $text): ?string {
		return null;
	}

	public function translate(?string $fromLanguage, string $toLanguage, string $text): string {
			$fromLanguage = $fromLanguage ?? $this->detectLanguage($text);
			$cacheKey = $fromLanguage . '-' . $toLanguage . ':' . md5($text);
			$model = $fromLanguage . '-' . $toLanguage;
			try {
				$result = $this->localCache[$cacheKey] ?? $this->translator->seq2seq($model, $text);
				$this->localCache[$cacheKey] = $result;
				return $result;
			}catch(\RuntimeException $e) {
				$this->logger->warning('Translation failed with: ' . $e->getMessage(), ['exception' => $e]);
				return '';
			}
	}
}
