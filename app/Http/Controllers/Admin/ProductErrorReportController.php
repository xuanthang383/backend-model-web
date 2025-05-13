<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\ProductErrorReport;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{

    // Danh sách (cho admin)
    public function index()
    {
        $reports = ProductErrorReport::with(['product'])
            ->latest()
            ->paginate(20);

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
