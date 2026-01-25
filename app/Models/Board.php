<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;

class Board extends Model implements CipherSweetEncrypted
{
    use HasFactory, ClearsCache, LogsActivity, UsesCipherSweet;
    protected $fillable = ['name', 'description', 'user_id'];

    /**
     * Configuration de l'Audit Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Board {$eventName}");
    }

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('name')
            ->addBlindIndex('name', new BlindIndex('name_index'))
            ->addOptionalTextField('description');
    }
    protected static function booted(): void
    {
        // Après création
        static::created(function (Board $board) {
            Cache::forget("user.{$board->user_id}.boards");
        });

        // Après mise à jour
        static::updated(function (Board $board) {
            Cache::forget("board.{$board->id}");
            Cache::forget("board.{$board->id}.with_relations");
            Cache::forget("user.{$board->user_id}.boards");
        });

        // Après suppression
        static::deleted(function (Board $board) {
            Cache::forget("board.{$board->id}");
            Cache::forget("board.{$board->id}.with_relations");
            Cache::forget("user.{$board->user_id}.boards");
        });
    }

    public function clearRelatedCache(): void
    {
        Cache::forget("board.{$this->id}");
        Cache::forget("board.{$this->id}.with_relations");
        Cache::forget("user.{$this->user_id}.boards");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(Column::class)->orderBy('position');
    }
}
