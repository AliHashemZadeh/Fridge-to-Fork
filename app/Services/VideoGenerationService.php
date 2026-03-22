<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VideoGenerationService
{
    private const string BASE_URL = 'https://queue.fal.run/fal-ai/wan/v2.2-5b/image-to-video';

    public function __construct(
        private string $apiKey = ''
    ) {
        $this->apiKey = config('services.fal.key');
    }

    /**
     * @throws RequestException|RuntimeException|ConnectionException
     */
    public function generateFromImage(string $imageUrl, string $prompt): string
    {
        [$statusUrl, $resultUrl] = $this->submitJob($imageUrl, $prompt);
        return $this->pollForResult($statusUrl, $resultUrl);
    }

    /**
     * @throws RequestException|ConnectionException|RuntimeException
     */
    private function submitJob(string $imageUrl, string $prompt): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Key ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post(self::BASE_URL, [
            'image_url' => $imageUrl,
            'prompt'    => "{$prompt}, professional food photography, cinematic, appetizing",
        ])->throw();

        $full      = $response->json();
        $requestId = $full['request_id'] ?? null;
        $statusUrl = $full['status_url'] ?? null;

        if (!$requestId || !$statusUrl) {
            throw new RuntimeException('Failed to submit video job to fal.ai.');
        }

        $resultUrl = str_replace('/status', '', $statusUrl);

        return [$statusUrl, $resultUrl];
    }

    /**
     * @throws ConnectionException|RuntimeException
     */
    private function pollForResult(string $statusUrl, string $resultUrl): string
    {
        $maxAttempts = 60;
        $headers     = ['Authorization' => 'Key ' . $this->apiKey];

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            sleep(3);

            $status = Http::withHeaders($headers)
                ->timeout(10)
                ->get($statusUrl)
                ->json('status');

            if ($status === 'COMPLETED') {
                $videoUrl = Http::withHeaders($headers)
                    ->timeout(10)
                    ->get($resultUrl)
                    ->json('video.url');

                if (!$videoUrl) {
                    throw new RuntimeException('Video URL missing from fal.ai response.');
                }

                return $videoUrl;
            }

            if ($status === 'FAILED') {
                throw new RuntimeException('Video generation failed on fal.ai.');
            }
        }

        throw new RuntimeException('Video generation timed out after 120 seconds.');
    }
}
