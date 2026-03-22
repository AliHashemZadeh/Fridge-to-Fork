<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class VideoPipelineService
{
    private string $claudeKey;
    private string $runwareKey;

    public function __construct()
    {
        $this->claudeKey = env('CLAUDE_API_KEY');
        $this->runwareKey = env('RUNWARE_API_KEY');
    }

    public function generate(string $productDescription): array
    {
        $script = $this->generateScript($productDescription);
        $imageUrl = $this->generateImage($script['image_prompt']);

        return [
            'title' => $script['title'],
            'scenes' => $script['scenes'],
            'image_url' => $imageUrl,
        ];
    }

    private function generateScript(string $productDescription): array
    {
        $body = [
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 1024,
            'messages' => [[
                'role' => 'user',
                'content' => 'You are a video script writer. Write a short promotional video script about: "' . $productDescription . '".
                Return ONLY a JSON object, no extra text:
                {
                    "title": "video title",
                    "image_prompt": "a detailed image generation prompt for this product",
                    "scenes": [
                        {"scene": 1, "text": "script text", "duration": 5},
                        {"scene": 2, "text": "script text", "duration": 5},
                        {"scene": 3, "text": "script text", "duration": 5}
                    ]
                }'
            ]]
        ];


        
        // $ch = curl_init('https://api.anthropic.com/v1/messages');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: application/json',
        //     'x-api-key: ' . $this->claudeKey,
        //     'anthropic-version: 2023-06-01'
        // ]);

        $response = Http::withHeaders([
            'x-api-key' => $this->claudeKey,
            'anthropic-version' => '2023-06-01'
        ])->post('https://api.anthropic.com/v1/messages', $body);

        $response = json_decode($response->body(), true);

        // $response = json_decode(curl_exec($ch), true);
        // curl_close($ch);

        return json_decode($response['content'][0]['text'], true);
    }

    private function generateImage(string $imagePrompt): string
    {
        $body = [[
            'taskType' => 'imageInference',
            'taskUUID' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
            'positivePrompt' => $imagePrompt,
            'model' => 'runware:100@1',
            'width' => 512,
            'height' => 512,
            'numberResults' => 1,
            'outputFormat' => 'WEBP'
        ]];

        // $ch = curl_init('https://api.runware.ai/v1');
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'Content-Type: application/json',
        //     'Authorization: Bearer ' . $this->runwareKey
        // ]);

        $response = Http::withToken($this->runwareKey)
            ->post('https://api.runware.ai/v1', $body);

        $response = json_decode($response->body(), true);


        // $response = json_decode(curl_exec($ch), true);
        // curl_close($ch);

        return $response['data'][0]['imageURL'];
    }
}