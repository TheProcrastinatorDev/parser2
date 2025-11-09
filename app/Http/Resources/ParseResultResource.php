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
     * Disable automatic wrapping of the resource in a data key.
     *
     * @var string|null
     */
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->success) {
            return [
                'success' => false,
                'error' => $this->error,
            ];
        }

        // Merge pagination info into metadata
        $metadata = array_merge($this->metadata ?? [], [
            'total' => $this->total,
            'next_offset' => $this->nextOffset,
        ]);

        return [
            'success' => true,
            'data' => [
                'items' => $this->items,
                'metadata' => $metadata,
            ],
        ];
    }
}
