<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\ProductErrorReport;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{

    // Danh sách (cho admin)
    public function index(Request $request)
    {
        $query = ProductErrorReport::with(['product']);

        // Filter by status if provided and not empty
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        // Filter by product_id if provided and not empty
        if ($request->filled('product_id')) {
            $productId = $request->input('product_id');
            $query->where('product_id', $productId);
        }

        // Filter by reason if provided and not empty
        if ($request->filled('reason')) {
            $reason = $request->input('reason');
            $query->where('reason', 'like', '%' . $reason . '%');
        }

        // Filter by message if provided and not empty
        if ($request->filled('message')) {
            $message = $request->input('message');
            $query->where('message', 'like', '%' . $message . '%');
        }

        // Filter by created_at if provided and not empty
        if ($request->filled('created_at')) {
            $createdAt = $request->input('created_at');
            $query->whereDate('created_at', $createdAt);
        }

        // Filter by updated_at if provided and not empty
        if ($request->filled('updated_at')) {
            $updatedAt = $request->input('updated_at');
            $query->whereDate('updated_at', $updatedAt);
        }

        return $this->paginateResponse($query, $request, "Get list product error reports", function ($report) {
            return $report;
        });
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
