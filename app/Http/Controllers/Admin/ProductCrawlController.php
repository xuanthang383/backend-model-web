<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\ProductCrawl;
use Illuminate\Http\Request;

class ProductCrawlController extends BaseController
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        $query = ProductCrawl::query();

        // Filter by name if provided
        if ($request->has('name')) {
            $name = $request->input('name');
            $query->where('name', 'LIKE', "%{$name}%");
        }

        return $this->paginateResponse($query, $request, "Get list product crawl");
    }

    public function getDistinctCategory(Request $request)
    {
        $appConfig = $request->input('app_config');
        $categorySearch = $request->input('category');

        if (!$appConfig) {
            return $this->errorResponse('Params app_config is required', 400);
        }

        // Build query for ProductCrawl
        $query = ProductCrawl::query()
            ->where('app_config', $appConfig);
        if ($categorySearch) {
            $query->where('category', 'like', "%$categorySearch%");
        }

        // Get distinct category values
        $categories = $query->distinct()->pluck('category')->filter()->values();

        return $this->successResponse($categories, 'Get distinct categories successfully');
    }
}
