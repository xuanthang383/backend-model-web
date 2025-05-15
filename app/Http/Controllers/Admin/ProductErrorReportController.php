<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\ProductErrorReport;
use App\Models\ErrorReason;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = ProductErrorReport::with(['product', 'user', 'errorReason']);

            // Lọc theo ID báo cáo
            if ($request->has('id') && !empty($request->id)) {
                $query->where('id', $request->id);
            }

            // Lọc theo product_id
            if ($request->has('product_id') && !empty($request->product_id)) {
                $query->where('product_id', $request->product_id);
            }

            // Lọc theo reason_id
            if ($request->has('reason_id') && !empty($request->reason_id)) {
                $query->where('reason_id', $request->reason_id);
            }

            // Lọc theo reason (tên của loại lỗi)
            if ($request->has('reason') && !empty($request->reason)) {
                $query->whereHas('errorReason', function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->reason}%");
                });
            }

            // Lọc theo status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Tìm kiếm theo từ khóa
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    // Tìm theo message của báo cáo
                    $q->where('message', 'like', "%{$search}%")
                        // Tìm theo value của báo cáo
                        ->orWhere('value', 'like', "%{$search}%")
                        // Tìm theo tên sản phẩm
                        ->orWhereHas('product', function($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        })
                        // Tìm theo tên của loại lỗi
                        ->orWhereHas('errorReason', function($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Lọc theo ngày tạo
            if ($request->has('created_at') && !empty($request->created_at)) {
                $dateRange = explode(',', $request->created_at);
                if (count($dateRange) === 2) {
                    $startDate = $dateRange[0];
                    $endDate = $dateRange[1];
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            }

            // Lọc theo ngày cập nhật
            if ($request->has('updated_at') && !empty($request->updated_at)) {
                $dateRange = explode(',', $request->updated_at);
                if (count($dateRange) === 2) {
                    $startDate = $dateRange[0];
                    $endDate = $dateRange[1];
                    $query->whereBetween('updated_at', [$startDate, $endDate]);
                }
            }

            // Sắp xếp
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Phân trang
            $perPage = $request->input('per_page', 10);
            $errorReports = $query->paginate($perPage);

            return response()->json([
                'r' => 0,
                'msg' => 'Success',
                'data' => $errorReports->items(),
                'meta' => [
                    'current_page' => $errorReports->currentPage(),
                    'last_page' => $errorReports->lastPage(),
                    'per_page' => $errorReports->perPage(),
                    'total' => $errorReports->total()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Không thể lấy danh sách báo cáo lỗi: ' . $e->getMessage(),
                500
            );
        }
    }

    public function updateStatus(Request $request, ProductErrorReport $report)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,confirmed,not_a_bug,fixed,rejected',
            ]);

            // Chuyển đổi thành string để đảm bảo nó được truyền đúng cách
            $report->status = (string)$validated['status'];
            $report->save();

            // Kiểm tra xem có phải là lỗi tên không
            $errorReason = ErrorReason::where('value', ErrorReason::ERROR_NAME)->first();

            // Nếu được chấp thuận và là lỗi về tên, cập nhật tên sản phẩm
            if ($validated['status'] === 'approved' && $report->reason_id === $errorReason->id) {
                $product = $report->product;
                $product->name = $report->value;
                $product->save();
            }

            return $this->successResponse(
                $report,
                'Trạng thái báo cáo lỗi đã được cập nhật thành công'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Không thể cập nhật trạng thái báo cáo lỗi: ' . $e->getMessage(),
                500
            );
        }
    }
}
