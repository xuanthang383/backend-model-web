<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Storage;

class JWTService
{
    /**
     * Tạo JWT token với Private Key
     */
    public static function generateToken($product_id)
    {
        $privateKey = file_get_contents(storage_path('keys/private.key'));

        $payload = [
            'product_id' => $product_id,
            'exp' => time() + 300, // Token hết hạn sau 5 phút
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    /**
     * Giải mã token bằng Public Key
     */
    public static function decodeToken($token)
    {
        try {
            $publicKey = file_get_contents(storage_path('keys/public.key'));

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            return $decoded->product_id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
