<?php

namespace App\Data\Column;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ColumnData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string|Optional $name,
        
        #[Nullable, IntegerType, Min(0)]
        public ?int $position = null,
    ) {}

    public static function forCreate(string $name): self
    {
        return new self(name: $name);
    }

    public static function forUpdate(?string $name = null, ?int $position = null): self
    {
        return new self(
            name: $name ?? Optional::create(),
            position: $position,
        );
    }
}
