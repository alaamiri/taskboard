<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;


class Card extends Model
{
    use HasFactory, ClearsCache;

    protected $fillable = ['title', 'description', 'position', 'column_id', 'user_id'];

    public function column(): BelongsTo
    {
        return $this->belongsTo(Column::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clearRelatedCache(): void
    {
        $boardId = $this->column->board_id;
        Cache::forget("board.{$boardId}");
        Cache::forget("board.{$boardId}.with_relations");
    }
}
