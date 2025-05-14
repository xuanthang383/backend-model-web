<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\ProductNameChangeRequest;
use Illuminate\Http\Request;

class ProductNameChangeRequestController extends BaseController
{
    public function index(Request $request)
    {
        try {
            $query = ProductNameChangeRequest::with(['product', 'user']);

            // Filtering by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtering by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Sorting
            $sortField = $request->input('sort_field', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->input('limit', 10);
            $nameChangeRequests = $query->paginate($perPage);

            // Create response without using successResponse directly
            return response()->json([
                'r' => 0,
                'msg' => 'Success',
                'data' => $nameChangeRequests->items(),
                'meta' => [
                    'current_page' => $nameChangeRequests->currentPage(),
                    'last_page' => $nameChangeRequests->lastPage(),
                    'per_page' => $nameChangeRequests->perPage(),
                    'total' => $nameChangeRequests->total()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve product name change requests: ' . $e->getMessage(),
                500
            );
        }
    }

    public function updateStatus(Request $request, ProductNameChangeRequest $nameChangeRequest)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'admin_note' => 'nullable|string'
            ]);

            $nameChangeRequest->status = $validated['status'];
            $nameChangeRequest->admin_note = $validated['admin_note'] ?? null;
            $nameChangeRequest->save();

            // If approved, update the product name
            if ($validated['status'] === 'approved') {
                $product = $nameChangeRequest->product;
                $product->name = $nameChangeRequest->suggested_name;
                $product->save();
            }

            return $this->successResponse(
                $nameChangeRequest,
                'Name change request status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update name change request status: ' . $e->getMessage(),
                500
            );
        }
    }
}
