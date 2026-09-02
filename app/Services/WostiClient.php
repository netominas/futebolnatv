<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WostiClient
{
    /** @return list<array<string, mixed>> */
    public function events(): array
    {
        $payload = $this->request()->get('/api/Events')->throw()->json();

        if (! is_array($payload)) {
            throw new RuntimeException('A Wosti retornou uma resposta inválida.');
        }

        return array_values($payload);
    }

    private function request(): PendingRequest
    {
        $key = config('services.wosti.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('WOSTI_API_KEY não está configurada.');
        }

        return Http::baseUrl((string) config('services.wosti.base_url'))
            ->acceptJson()
            ->withHeaders([
                'x-rapidapi-host' => (string) config('services.wosti.host'),
                'x-rapidapi-key' => $key,
            ])
            ->connectTimeout(5)
            ->timeout(30)
            ->retry(2, 750);
    }
}
