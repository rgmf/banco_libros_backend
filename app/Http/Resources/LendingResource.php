<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LendingResource extends JsonResource
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
            'student_id' => $this->student_id,
            'book_copy_id' => $this->book_copy_id,
            'academic_year_id' => $this->academic_year_id,
            'lending_date' => $this->lending_date,
            'returned_date' => $this->returned_date,
            'lending_status_id' => $this->lending_status_id,
            'returned_status_id' => $this->returned_status_id,
            'student' => $this->whenLoaded('student', function() {
                return new StudentResource($this->student);
            }),
            'book_copy' => $this->whenLoaded('bookCopy', function() {
                return new BookCopyResource($this->bookCopy);
            }),
            'academic_year' => $this->whenLoaded('academicYear', function() {
                return new AcademicYearResource($this->academicYear);
            })
        ];
    }

    public function withResponse(Request $request, JsonResponse $response)
    {
        $response->setStatusCode($this->code);
    }
}
