<?php

namespace App\Data\Card;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class CardData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string|Optional $title,
        
        #[Nullable, StringType]
        public ?string $description = null,
    ) {}

    public static function forCreate(string $title, ?string $description = null): self
    {
        return new self(
            title: $title,
            description: $description,
        );
    }

    public static function forUpdate(?string $title = null, ?string $description = null): self
    {
        return new self(
            title: $title ?? Optional::create(),
            description: $description,
        );
    }
}
