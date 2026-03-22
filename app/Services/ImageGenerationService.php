<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ImageGenerationService
{
    private const string RUNWARE_API_URL = 'https://api.runware.ai/v1';
    private const string MODEL = 'runware:101@1';
    private const int WIDTH              = 512;
    private const int HEIGHT             = 512;

    public function __construct(
        private string $apiKey = ''
    ) {
        $this->apiKey = config('services.runware.key');
    }

    /**
     * @throws RequestException|ConnectionException|RuntimeException
     */
    public function generateForSteps(array $steps): array
    {
        return collect($steps)
            ->map(fn(array $step) => array_merge($step, [
                'image_url' => $this->generate($step['image_prompt']),
            ]))
            ->toArray();
    }

    /**
     * @throws RequestException|ConnectionException|RuntimeException
     */
    public function generate(string $prompt): string
    {
        $response = Http::withToken($this->apiKey)
            ->post(self::RUNWARE_API_URL, [
                [
                    'taskType'        => 'imageInference',
                    'taskUUID'        => $this->generateUUID(),
                    'positivePrompt'  => $prompt,
                    'negativePrompt'  => 'abstract, surreal, cartoon, illustration, blurry, ugly, deformed, non-food, text, watermark',
                    'model'           => self::MODEL,
                    'width'           => self::WIDTH,
                    'height'          => self::HEIGHT,
                    'numberResults'   => 1,
                    'outputFormat'    => 'WEBP',
                ]
            ])->throw();

        $imageUrl = $response->json('data.0.imageURL');

        if (!$imageUrl) {
            throw new RuntimeException('Failed to generate image from Runware.');
        }

        return $imageUrl;
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
