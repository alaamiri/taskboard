<?php

namespace App\Data\Card;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Data;

class MoveCardData extends Data
{
    public function __construct(
        #[Required, IntegerType, Exists('columns', 'id')]
        public int $column_id,
        
        #[Required, IntegerType, Min(0)]
        public int $position,
    ) {}
}
