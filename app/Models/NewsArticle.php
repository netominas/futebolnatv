<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    protected $fillable = [
        'type',
        'status',
        'coverage_date',
        'title',
        'slug',
        'subtitle',
        'excerpt',
        'body',
        'meta_title',
        'meta_description',
        'hero_image_path',
        'hero_image_alt',
        'hero_image_caption',
        'hero_image_credit',
        'source_snapshot',
        'created_by',
        'reviewed_by',
        'scheduled_for',
        'published_at',
        'last_fact_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'coverage_date' => 'immutable_date',
            'source_snapshot' => 'array',
            'scheduled_for' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
            'last_fact_checked_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fixtures(): BelongsToMany
    {
        return $this->belongsToMany(Fixture::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where(function (Builder $query): void {
                $query->where('status', 'published')->where('published_at', '<=', now());
            })->orWhere(function (Builder $query): void {
                $query->where('status', 'scheduled')->where('scheduled_for', '<=', now());
            });
        });
    }

    public function publicUrl(): string
    {
        return route('news.show', ['article' => $this->slug]);
    }

    public function heroImageUrl(): ?string
    {
        return $this->hero_image_path ? asset('storage/'.$this->hero_image_path) : null;
    }

    public function bodyHtml(): HtmlString
    {
        return new HtmlString(Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function publicationDate(): mixed
    {
        return $this->published_at ?? $this->scheduled_for;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'published' => 'Publicada',
            'scheduled' => 'Agendada',
            default => 'Rascunho',
        };
    }
}
