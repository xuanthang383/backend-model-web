<?php

namespace App\Http\Controllers;

use App\Models\ProductErrorReport;
use App\Models\ErrorReason;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{
    // Gửi báo cáo lỗi (user)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'reason_id' => 'required|exists:error_reasons,id',
            'message' => 'nullable|string|max:1000',
        ]);

        ProductErrorReport::create($validated);

        return $this->successResponse('Báo lỗi đã được gửi thành công!');
    }

    // Danh sách (cho admin)
    public function index()
    {
        $reports = ProductErrorReport::with(['product', 'reason'])->latest()->paginate(20);

        return $this->successResponse($reports);
    }

    // Cập nhật trạng thái (admin)
    public function updateStatus(Request $request, ProductErrorReport $report)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,not_a_bug,fixed,rejected',
        ]);

        $report->update(['status' => $validated['status']]);

        return $this->successResponse('Cập nhật trạng thái thành công!');
    }
}
