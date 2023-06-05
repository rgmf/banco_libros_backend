<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'nia' => $this->nia,
            'name' => $this->name,
            'lastname1' => $this->lastname1,
            'lastname2' => $this->lastname2,
            'cohort_id' => $this->cohort_id,
            'picture' => $this->picture,
            'nationality' => $this->nationality,
            'address' => $this->address,
            'city' => $this->city,
            'cp' => $this->cp,
            'phone1' => $this->phone1,
            'phone2' => $this->phone2,
            'phone3' => $this->phone3,
            'name_father' => $this->name_father,
            'lastname1_father' => $this->lastname1_father,
            'lastname2_father' => $this->lastname2_father,
            'email_father' => $this->email_father,
            'name_mother' => $this->name_mother,
            'lastname1_mother' => $this->lastname1_mother,
            'lastname2_mother' => $this->lastname2_mother,
            'email_mother' => $this->email_mother,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'cohort' => $this->cohort,
            'lendings' => $this->lendings
        ];
    }

    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setStatusCode($this->code);
    }
}
