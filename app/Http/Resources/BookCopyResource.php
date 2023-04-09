<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookCopyResource extends JsonResource
{
    private int $code;

    public function __construct(mixed $resource, int $code=200)
    {
        parent::__construct($resource);
        $this->code = $code;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'comment' => $this->comment,
            'book_id' => $this->book_id,
            'status_id' => $this->status_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_ata
        ];
    }

    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setStatusCode($this->code);
    }
}
