<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    private int $code;
    private string $message;
    private array $errors;

    public function __construct(int $code, string $message, mixed $resource = null, array $errors = [])
    {
        parent::__construct($resource);
        $this->code = $code;
        $this->message = $message;
        $this->errors = $errors;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'message' => $this->message,
            'code' => $this->code,
            'errors' => $this->errors
        ];

        if ($this->resource !== null) {
            $data['exception'] = [
                'message' => $this->getMessage(),
                'code' => $this->getCode()
            ];
        }

        return $data;
    }

    public function withResponse(Request $request, JsonResponse$response): void
    {
        $response->setStatusCode($this->code);
    }
}
