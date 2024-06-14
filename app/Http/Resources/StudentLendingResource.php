<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentLendingResource extends JsonResource
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
            'name' => $this->name,
            'lastname1' => $this->lastname1,
            'lastname2' => $this->lastname2,
            'cohort_id' => $this->cohort_id,
            'cohort' => $this->whenLoaded('cohort', function() {
                return new CohortResource($this->cohort);
            }),
            'lendings' => LendingResource::collection($this->whenLoaded('lendings'))
        ];
    }

    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setStatusCode($this->code);
    }
}
