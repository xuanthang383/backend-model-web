<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\AppConfig;
use Illuminate\Http\Request;

class AppConfigController extends BaseController
{
    public function getConfig(Request $request)
    {
        $configKey = $request->input('config_key');

        if (!$configKey) {
            return $this->errorResponse('Params config_key is required', 400);
        }

        $appConfig = AppConfig::where('config_key', $configKey)->first();

        // Build query for ProductCrawl
        return $this->successResponse($appConfig, 'Get config successfully');
    }

    public function update(Request $request, string $id)
    {
        // Validate input
        $validated = $request->validate([
            'config_key' => 'required|string',
            'config_value' => 'nullable|string',
            'description' => 'nullable|string',
            'type' => 'required|string',
        ]);

        // Find AppConfig by id
        $appConfig = AppConfig::find($id);
        if (!$appConfig) {
            return $this->errorResponse('Config not found', 404);
        }

        // Update fields
        $appConfig->update($validated);

        return $this->successResponse($appConfig, 'Config updated successfully');
    }
}
