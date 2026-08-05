<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Story extends Model
{
    use Prunable;

    protected $fillable = ['user_id', 'media_path', 'media_type', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<=', now());
    }

    protected function pruning(): void
    {
        Storage::disk('public')->delete($this->media_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
