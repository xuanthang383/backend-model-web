<?php

namespace App\Http\Controllers;

use App\Models\ProductErrorReport;
use App\Models\ErrorReason;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{
    // Gửi báo cáo lỗi (user)
    // Gửi báo cáo lỗi (user)
    public function store(Request $request, $product_id)
    {
        // Validate request
        $validated = $request->validate([
            'reason' => 'required|string',
            'details' => 'nullable|string|max:1000',
        ]);


        // Create the error report
        ProductErrorReport::create([
            'product_id' => $product_id,
            'reason' => $validated['reason'] ?? null,
            'message' => $validated['details'] ?? null,
        ]);

        return $this->successResponse('Báo lỗi đã được gửi thành công!');
    }

}
