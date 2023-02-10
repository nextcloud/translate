<?php
declare(strict_types=1);
namespace OCA\Translate\Provider;

use OCA\Translate\Service\TranslateService;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\Translation\ITranslationProvider;

class Translation implements ITranslationProvider {
	private ICacheFactory $cacheFactory;

	private array $localCache = [];

	private TranslateService $translator;

	public function __construct(ICacheFactory $cacheFactory, TranslateService $translator) {
		$this->cacheFactory = $cacheFactory;
		$this->translator = $translator;
	}

	public function getName(): string {
		return 'Opus models by the University of Helsinki';
	}

	public function getAvailableLanguages(): array
	{
		$cache = $this->cacheFactory->createDistributed('integration_deepl');
		if ($cached = $cache->get('languages')) {
			return $cached;
		}

		$sourceLanguages = $this->translator->getSourceLanguages();
		$targetLanguages = $this->translator->getTargetLanguages();
		$availableLanguages = [];
		foreach ($sourceLanguages as $sourceLanguage) {
			foreach ($targetLanguages as $targetLanguage) {
				$availableLanguages[] = [
					'from' => [
						'code' => $sourceLanguage->code,
						'name' => $sourceLanguage->name,
					],
					'to' => [
						'code' => $targetLanguage->code,
						'name' => $targetLanguage->name,
					],
				];
			}
		}
		$cache->set('languages', $availableLanguages, 3600);
		return $availableLanguages;
	}

	public function detectLanguage(string $text): ?string
	{
		try {
			$cacheKey = md5($text);
			$result = $this->localCache[$cacheKey] ?? $this->translator->seq2seq($text, null, 'en');
			$this->localCache[$cacheKey] = $result;
			return $result->detectedSourceLang;
		} catch (DeepLException $e) {
			return null;
		}
	}

	public function translate(?string $fromLanguage, string $toLanguage, string $text): string {
			$fromLanguage = $fromLanguage ?? $this->detectLanguage($text);
			$cacheKey = $fromLanguage . $toLanguage . md5($text);
			$model = $fromLanguage . '-' . $toLanguage;
			try {
				$result = $this->localCache[$cacheKey] ?? $this->translator->seq2seq($model, $text);
				$this->localCache[$cacheKey] = $result;
				return $result;
			}catch(\RuntimeException $e) {
				return '';
			}
	}
}
