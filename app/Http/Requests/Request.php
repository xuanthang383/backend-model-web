<?php

namespace App\Http\Requests;

use Illuminate\Http\Request as HttpRequest;

class Request extends HttpRequest
{
    public function wantsJson(): bool
    {
        return $this->is('api/*') || parent::wantsJson();
    }
}
