<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RecipeService
{
    private const string ANTHROPIC_API_URL = 'https://api.anthropic.com/v1/messages';
    private const string MODEL = 'claude-sonnet-4-20250514';
    private const int MAX_TOKENS = 1024;

    public function __construct(
        private string $apiKey = ''
    ) {
        $this->apiKey = config('services.anthropic.key');
    }

    /**
     * @throws RequestException|ConnectionException|RuntimeException
     */
    public function generate(string $ingredients, string $country): array
    {
        $response = Http::withHeaders($this->headers())
            ->post(self::ANTHROPIC_API_URL, [
                'model'      => self::MODEL,
                'max_tokens' => self::MAX_TOKENS,
                'messages'   => [
                    ['role' => 'user', 'content' => $this->buildPrompt($ingredients, $country)]
                ],
            ])
            ->throw();

        $text   = $response->json('content.0.text');
        $recipe = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse recipe response from AI.');
        }

        return $recipe;
    }

    private function buildPrompt(string $ingredients, string $country): string
    {
        $style = 'realistic appetizing food photo, natural colors, professional food photography, warm lighting, rustic kitchen, high detail, photorealistic';

        return <<<PROMPT
        You are a professional chef. The user has these ingredients: "{$ingredients}" and wants a {$country} dish.
        Create a recipe using ONLY the provided ingredients.
        Return ONLY a valid JSON object, no extra text, no markdown backticks.
        The steps array must contain EXACTLY 4 steps, no more, no less.

        {
            "dish_name": "name of the dish",
            "country": "{$country}",
            "description": "one sentence description",
            "total_time": "estimated cooking time",
            "difficulty": "easy | medium | hard",
            "steps": [
                {
                    "step": 1,
                    "time": "5 min",
                    "instruction": "what to do",
                    "image_prompt": "specific cooking action for this step, {$style}"
                }
            ]
        }
        PROMPT;
    }

    private function headers(): array
    {
        return [
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ];
    }
}
