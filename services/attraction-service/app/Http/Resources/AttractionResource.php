<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Fields of an attraction that are sent to the frontend.
class AttractionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'thrill_level' => $this->thrill_level,
            'min_age' => $this->min_age,
            'min_height_cm' => $this->min_height_cm,
            'status' => $this->status,
        ];
    }
}
