<?php

namespace App\Http\Controllers;

use App\Jobs\SyncProductCrawlJob;
use Illuminate\Http\Request;

class SyncProductCrawlController extends BaseController
{
    public function sync(Request $request)
    {
        // Dispatch the job
        SyncProductCrawlJob::dispatch();
        return $this->successResponse(
            null,
            'Sync job dispatched successfully. It may take some time to complete.'
        );
    }
}

