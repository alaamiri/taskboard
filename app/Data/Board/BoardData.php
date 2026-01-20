<?php

namespace App\Data\Board;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class BoardData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string|Optional $name,
        
        #[Nullable, StringType]
        public ?string $description = null,
    ) {}

    public static function forCreate(string $name, ?string $description = null): self
    {
        return new self(
            name: $name,
            description: $description,
        );
    }

    public static function forUpdate(?string $name = null, ?string $description = null): self
    {
        return new self(
            name: $name ?? Optional::create(),
            description: $description,
        );
    }
}
