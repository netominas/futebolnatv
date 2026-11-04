<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\NewsArticle;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NewsImageGenerator
{
    /** @return array{bytes: string, extension: string, provider_label: string, prompt: string} */
    public function generate(NewsArticle $article, AiSetting $settings): array
    {
        $prompt = $this->prompt($article);

        return $settings->active_provider === 'google'
            ? $this->generateWithGoogle($settings, $prompt)
            : $this->generateWithOpenAi($settings, $prompt, $article);
    }

    /** @return array{bytes: string, extension: string, provider_label: string, prompt: string} */
    private function generateWithOpenAi(AiSetting $settings, string $prompt, NewsArticle $article): array
    {
        $response = $this->request()
            ->withToken((string) $settings->openai_api_key)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $settings->openai_model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1536x864',
                'quality' => 'medium',
                'background' => 'opaque',
                'output_format' => 'webp',
                'output_compression' => 82,
                'moderation' => 'auto',
                'safety_identifier' => hash('sha256', 'futebol-na-tv-news-'.$article->created_by),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('A OpenAI não conseguiu gerar a imagem (HTTP '.$response->status().').');
        }

        $bytes = $this->decode((string) data_get($response->json(), 'data.0.b64_json'));

        return $this->validatedImage($bytes, 'OpenAI', $prompt);
    }

    /** @return array{bytes: string, extension: string, provider_label: string, prompt: string} */
    private function generateWithGoogle(AiSetting $settings, string $prompt): array
    {
        $model = rawurlencode($settings->google_model);
        $response = $this->request()
            ->withHeaders(['x-goog-api-key' => (string) $settings->google_api_key])
            ->post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent", [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'responseModalities' => ['IMAGE'],
                    'responseFormat' => [
                        'image' => [
                            'aspectRatio' => '16:9',
                            'imageSize' => '1K',
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('O Google Gemini não conseguiu gerar a imagem (HTTP '.$response->status().').');
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $encoded = collect(is_array($parts) ? $parts : [])->firstWhere('inlineData.data');
        $bytes = $this->decode((string) data_get($encoded, 'inlineData.data'));

        return $this->validatedImage($bytes, 'Google Gemini', $prompt);
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(240);
    }

    private function decode(string $encoded): string
    {
        $bytes = base64_decode($encoded, true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('A IA respondeu sem uma imagem válida.');
        }

        return $bytes;
    }

    /** @return array{bytes: string, extension: string, provider_label: string, prompt: string} */
    private function validatedImage(string $bytes, string $provider, string $prompt): array
    {
        $info = @getimagesizefromstring($bytes);
        if ($info === false || ($info[0] ?? 0) < 1200 || ($info[1] ?? 0) < 675) {
            throw new RuntimeException('A IA retornou uma imagem abaixo do mínimo de 1200 × 675 pixels.');
        }
        if (strlen($bytes) > 5 * 1024 * 1024) {
            throw new RuntimeException('A imagem gerada ultrapassou o limite de 5 MB.');
        }

        $extension = match ($info['mime'] ?? null) {
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => throw new RuntimeException('A IA retornou um formato de imagem não aceito.'),
        };

        return [
            'bytes' => $bytes,
            'extension' => $extension,
            'provider_label' => $provider,
            'prompt' => $prompt,
        ];
    }

    private function prompt(NewsArticle $article): string
    {
        $angle = match ($article->type) {
            'match_focus' => 'uma partida em destaque e sua transmissão',
            'channel_focus' => 'a distribuição dos jogos entre canais e plataformas',
            'competition_focus' => 'um campeonato em destaque na programação',
            'time_slot' => 'a concentração de partidas em uma faixa de horário',
            'weekend_guide' => 'a agenda de futebol do fim de semana',
            default => 'a programação diária de futebol na televisão e no streaming',
        };

        return Str::limit(<<<PROMPT
Crie uma imagem editorial original, premium e jornalística em proporção 16:9 para uma notícia de um portal brasileiro sobre futebol na TV.

Tema: {$angle}.
Título usado apenas como contexto: {$article->title}
Resumo usado apenas como contexto: {$article->excerpt}

Direção visual: atmosfera de futebol e transmissão esportiva, estádio contemporâneo, tela ou elementos sutis de televisão, iluminação elegante, composição limpa, cores azul, cinza e branco, alto contraste e ponto focal forte. A imagem deve funcionar como capa no Google Discover e em redes sociais.

Regras obrigatórias: não inserir palavras, letras, números, placares ou manchetes; não usar marcas, logotipos, escudos oficiais ou uniformes protegidos; não retratar jogadores, celebridades ou pessoas reais identificáveis; não simular uma fotografia documental de um acontecimento real; não inventar resultado ou escalação; manter margens seguras para recortes. Produza apenas uma ilustração editorial, sem moldura e sem marca-d'água adicionada.
PROMPT, 3000, '');
    }
}
