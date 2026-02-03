<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api-free.deepl.com/v2';

    public function __construct()
    {
        $this->apiKey = config('services.deepl.api_key') ?: null;
    }

    /**
     * Check if translation is available.
     */
    public function isAvailable(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    /**
     * Get supported languages.
     */
    public function getLanguages(): array
    {
        return [
            ['code' => 'EN', 'name' => 'English'],
            ['code' => 'DE', 'name' => 'German'],
            ['code' => 'FR', 'name' => 'French'],
            ['code' => 'ES', 'name' => 'Spanish'],
            ['code' => 'IT', 'name' => 'Italian'],
            ['code' => 'NL', 'name' => 'Dutch'],
            ['code' => 'PL', 'name' => 'Polish'],
            ['code' => 'PT', 'name' => 'Portuguese'],
            ['code' => 'RU', 'name' => 'Russian'],
            ['code' => 'JA', 'name' => 'Japanese'],
            ['code' => 'ZH', 'name' => 'Chinese'],
            ['code' => 'KO', 'name' => 'Korean'],
        ];
    }

    /**
     * Translate text to target language.
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $params = [
                'text' => [$text],
                'target_lang' => strtoupper($targetLang),
            ];

            if ($sourceLang) {
                $params['source_lang'] = strtoupper($sourceLang);
            }

            $response = Http::withHeaders([
                'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
            ])->post("{$this->baseUrl}/translate", $params);

            if (!$response->successful()) {
                Log::error('DeepL translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['translations'][0]['text'] ?? null;
        } catch (\Exception $e) {
            Log::error('Translation error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Translate markdown content (preserves structure).
     */
    public function translateMarkdown(string $content, string $targetLang, ?string $sourceLang = null): ?string
    {
        // DeepL handles markdown/text formatting automatically
        return $this->translate($content, $targetLang, $sourceLang);
    }

    /**
     * Get usage statistics.
     */
    public function getUsage(): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "DeepL-Auth-Key {$this->apiKey}",
            ])->get("{$this->baseUrl}/usage");

            if (!$response->successful()) {
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('DeepL usage check error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
