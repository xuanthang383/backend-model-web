<?php

require __DIR__ . '/vendor/autoload.php'; // 🛠 Đảm bảo bạn đã `composer require firebase/php-jwt`

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ======== CẤU HÌNH ==========
$productId = 31; // 🔁 Đổi ID thực có trong DB
$apiUrl = 'http://localhost:8000/api/products/test-download-model'; // 🔁 Đổi URL đúng
$privateKeyPath = __DIR__ . '/private.pem';
// ============================

// 1. Tạo payload JWT
$payload = [
    'product_id' => $productId,
    'timestamp' => time()
    // Bạn có thể thêm 'exp' => time() + 30 nếu muốn dùng exp chuẩn
];

// 2. Load private key
$privateKey = file_get_contents($privateKeyPath);
if (!$privateKey) {
    die("❌ Không tìm thấy private key: $privateKeyPath\n");
}

// 3. Ký JWT bằng RS256
$jwt = JWT::encode($payload, $privateKey, 'RS256');

// 4. Gửi request đến API Laravel
$requestPayload = json_encode([
    'token' => $jwt
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $requestPayload);

// 5. Thực thi request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// 6. Xử lý kết quả
echo "📥 HTTP $httpCode\n";

if (strpos($contentType, 'application/json') !== false) {
    echo "Response JSON:\n$response\n";
} else {
    // Nếu là file (ví dụ zip/pdf/3D model), lưu lại
    $fileName = "model_download_" . time();
    file_put_contents("$fileName.bin", $response);
    echo "✅ File tải thành công: $fileName.bin\n";
}
