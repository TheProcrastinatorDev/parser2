<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParserDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'name' => $this->resource['name'],
                'description' => $this->resource['description'] ?? "Parser for {$this->resource['name']} sources",
                'config' => $this->resource['config'] ?? [],
            ],
        ];
    }
}
