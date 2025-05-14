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

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by product_id
        if ($request->has('product_id') && $request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        // Search by message content
        if ($request->has('search') && $request->search) {
            $query->where('message', 'like', '%' . $request->search . '%')
                ->orWhere('reason', 'like', '%' . $request->search . '%');
        }

        // Order by (default: latest first)
        $orderBy = $request->order_by ?? 'created_at';
        $orderDirection = $request->order_direction ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // Paginate results
        $perPage = $request->limit ?? 20;
        $reports = $query->paginate($perPage);

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
