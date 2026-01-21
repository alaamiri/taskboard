<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('causer', fn() => [
                'id' => $this->causer?->id,
                'name' => $this->causer?->name,
            ]),
            'action' => $this->description,
            'model_type' => class_basename($this->subject_type ?? 'N/A'),
            'model_id' => $this->subject_id,
            'old_values' => $this->properties['old'] ?? null,
            'new_values' => $this->properties['attributes'] ?? null,
            'ip_address' => $this->properties['ip_address'] ?? null,
            'user_agent' => $this->properties['user_agent'] ?? null,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
