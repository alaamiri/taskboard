<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ColumnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'board_id' => $this->board_id,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relation conditionnelle
            'cards' => CardResource::collection($this->whenLoaded('cards')),

            // Donnée calculée
            'cards_count' => $this->when(
                $this->cards_count !== null,
                $this->cards_count
            ),
        ];
    }
}
