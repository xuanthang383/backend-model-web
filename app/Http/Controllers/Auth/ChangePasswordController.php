<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class ChangePasswordController extends BaseController
{
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect!'
            ], 400);
        }

        // Update the new password
        $user->password = Hash::make($request->new_password);
        $user->save();

       return $this->successResponse('Model added to library successfully');
    }
}
