<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Column extends Model
{
    use HasFactory, ClearsCache;

    protected $fillable = ['name', 'position', 'board_id'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }

    public function clearRelatedCache(): void
    {
        Cache::forget("board.{$this->board_id}");
        Cache::forget("board.{$this->board_id}.with_relations");
    }
}
