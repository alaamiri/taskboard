<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsCache
{
    protected static function bootClearsCache(): void
    {
        static::created(function ($model) {
            $model->clearRelatedCache();
        });

        static::updated(function ($model) {
            $model->clearRelatedCache();
        });

        static::deleted(function ($model) {
            $model->clearRelatedCache();
        });
    }

    abstract public function clearRelatedCache(): void;
}
