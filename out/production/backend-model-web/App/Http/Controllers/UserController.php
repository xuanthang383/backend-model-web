<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserToken(Request $request)
    {
        abort_if(!$request->user(), 401, 'Unauthorized');

        return response()->json([
            'r' => 0,
            'msg' => 'User token retrieved successfully',
            'data' => $request->user()
        ]);
    }
}
