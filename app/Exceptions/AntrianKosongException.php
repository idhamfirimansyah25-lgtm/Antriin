<?php

namespace App\Exceptions;

use Exception;

class AntrianKosongException extends Exception
{
    /**
     * Report the exception.
     */
    public function report(): void
    {
        // Exception ini murni untuk logic bisnis, tidak perlu di-log sebagai error sistem
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render($request)
    {
        return back()->with('error', $this->getMessage());
    }
}
