<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\Parser\ParseResultDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ParseResultDTO
 */
class ParseResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => $this->success,
            'data' => $this->when($this->success, [
                'items' => $this->items,
                'metadata' => $this->metadata,
                'total' => $this->total,
                'next_offset' => $this->nextOffset,
            ]),
            'error' => $this->when(! $this->success, $this->error),
        ];
    }
}
