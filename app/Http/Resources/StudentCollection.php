<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StudentCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function($student) {
                return [
                    'id' => $student->id,
                    'nia' => $student->nia,
                    'name' => $student->name,
                    'lastname1' => $student->lastname1,
                    'lastname2' => $student->lastname2,
                    'cohort_id' => $student->cohort_id,
                    'picture' => $student->picture,
                    'nationality' => $student->nationality,
                    'address' => $student->address,
                    'city' => $student->city,
                    'cp' => $student->cp,
                    'phone1' => $student->phone1,
                    'phone2' => $student->phone2,
                    'phone3' => $student->phone3,
                    'name_father' => $student->name_father,
                    'lastname1_father' => $student->lastname1_father,
                    'lastname2_father' => $student->lastname2_father,
                    'email_father' => $student->email_father,
                    'name_mother' => $student->name_mother,
                    'lastname1_mother' => $student->lastname1_mother,
                    'lastname2_mother' => $student->lastname2_mother,
                    'email_mother' => $student->email_mother,
                    'cohort' => $student->cohort,
                    'lendings' => $student->lendings
                ];
            })
        ];
    }
}
