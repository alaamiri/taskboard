<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Column extends Model
{
    use HasFactory, ClearsCache, LogsActivity;

    protected $fillable = ['name', 'position', 'board_id'];

    /**
     * Configuration de l'Audit Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'position', 'board_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Column {$eventName}");
    }

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
