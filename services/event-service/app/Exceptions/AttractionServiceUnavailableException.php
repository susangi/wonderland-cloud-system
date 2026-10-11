<?php

namespace App\Exceptions;

use RuntimeException;

// attraction-service did not answer. This is not shown to staff as an error.
// RideScanService catches it and saves the scan with an "Unknown" ride name (graceful degradation).
class AttractionServiceUnavailableException extends RuntimeException
{
}
