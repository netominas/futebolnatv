<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'active_provider',
        'openai_api_key',
        'openai_model',
        'google_api_key',
        'google_model',
        'updated_by',
    ];

    protected $hidden = [
        'openai_api_key',
        'google_api_key',
    ];

    protected function casts(): array
    {
        return [
            'openai_api_key' => 'encrypted',
            'google_api_key' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate(['id' => 1], [
            'active_provider' => 'openai',
            'openai_model' => 'gpt-image-2.5-flare',
            'google_model' => 'gemini-3.1-flash-image',
        ]);
    }

    public function activeApiKey(): ?string
    {
        return $this->active_provider === 'google'
            ? $this->google_api_key
            : $this->openai_api_key;
    }

    public function activeModel(): string
    {
        return $this->active_provider === 'google'
            ? $this->google_model
            : $this->openai_model;
    }
}
