<?php

namespace App\Http\Requests;

// PUT sends the full attraction, so the rules are the same as create.
// Status is not changed here. Use the retire and activate endpoints for that.
class UpdateAttractionRequest extends CreateAttractionRequest
{
}
