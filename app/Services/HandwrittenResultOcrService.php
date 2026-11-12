<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class HandwrittenResultOcrService
{
    public function extract(string $imagePath): array
    {
        if (!is_file($imagePath) || !is_readable($imagePath)) {
            throw new RuntimeException('Result image is not readable.');
        }

        $mime = mime_content_type($imagePath);

        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('Only JPEG and PNG images are supported.');
        }

        if (filesize($imagePath) > 5 * 1024 * 1024) {
            throw new RuntimeException('Image exceeds the 5 MB limit.');
        }

        $key = config('services.openai.key')
            ?: env('OPENAI_API_KEY');

        if (!$key) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $image = base64_encode(file_get_contents($imagePath));

        $instructions = <<<'PROMPT'
You are extracting handwritten election results from a
SIMULATED DEMONSTRATION RESULT SHEET.

Read only what is visibly present in the image.

Never invent candidate names or vote figures.
If a value is missing, unclear, crossed out, or unreadable,
return null and explain the uncertainty.

Return ONLY valid JSON with this structure:

{
  "station_name": null,
  "station_code": null,
  "candidates": [
    {
      "name": "Candidate name",
      "votes": 0,
      "uncertain": false
    }
  ],
  "valid_votes": null,
  "rejected_votes": null,
  "warnings": []
}

Candidate vote counts must be integers or null.

Do not calculate missing figures and present them as
values read from the document.

Do not claim the result is verified or authentic.
PROMPT;

        $response = Http::withToken($key)
            ->timeout(60)
            ->retry(2, 500)
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-4.1-mini',
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $instructions,
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => 'data:' . $mime
                                    . ';base64,' . $image,
                            ],
                        ],
                    ],
                ],
                'max_output_tokens' => 1200,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'OCR provider request failed with HTTP '
                . $response->status()
            );
        }

        $text = collect($response->json('output', []))
            ->flatMap(fn ($item) => $item['content'] ?? [])
            ->filter(fn ($item) =>
                ($item['type'] ?? '') === 'output_text'
            )
            ->pluck('text')
            ->implode("\n");

        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace(
                '/^```(?:json)?\s*|\s*```$/',
                '',
                $text
            );
        }

        $data = json_decode($text, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'OCR provider returned invalid JSON.'
            );
        }

        return $data;
    }
}
