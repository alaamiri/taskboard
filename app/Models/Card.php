<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\LaravelCipherSweet\Concerns\UsesCipherSweet;
use Spatie\LaravelCipherSweet\Contracts\CipherSweetEncrypted;
use ParagonIE\CipherSweet\BlindIndex;
use ParagonIE\CipherSweet\EncryptedRow;

class Card extends Model implements CipherSweetEncrypted
{
    use HasFactory, ClearsCache, LogsActivity, UsesCipherSweet;

    protected $fillable = ['title', 'description', 'position', 'column_id', 'user_id'];

    /**
     * Configuration de l'Audit Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'position', 'column_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Card {$eventName}");
    }


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

    public static function configureCipherSweet(EncryptedRow $encryptedRow): void
    {
        $encryptedRow
            ->addField('title')
            ->addBlindIndex('title', new BlindIndex('title_index'))
            ->addOptionalTextField('description');
    }
}
