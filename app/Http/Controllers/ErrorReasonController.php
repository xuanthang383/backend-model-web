<?php

namespace App\Http\Controllers;

use App\Models\ErrorReason;

class ErrorReasonController extends BaseController
{
    public function index()
    {
        $reasons = ErrorReason::where('is_active', true)->get(['id', 'name']);

        return $this->successResponse($reasons);
    }
}
