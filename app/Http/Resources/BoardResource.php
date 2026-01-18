<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relations conditionnelles
            'user' => new UserResource($this->whenLoaded('user')),
            'columns' => ColumnResource::collection($this->whenLoaded('columns')),

            // Données calculées
            'columns_count' => $this->when(
                $this->columns_count !== null,
                $this->columns_count
            ),
        ];
    }
}
