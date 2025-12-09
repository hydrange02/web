<?php
// File: Web php/Config/get_shipping_fee.php
session_start();
header('Content-Type: application/json');

// 1. CẤU HÌNH TỌA ĐỘ SHOP (Ví dụ: Chợ Bến Thành, TP.HCM)
// Bạn có thể lấy tọa độ shop của bạn trên Google Maps (chuột phải -> chọn số đầu tiên)
const SHOP_LAT = 10.7721; 
const SHOP_LON = 106.6983;

// Nhận địa chỉ từ Frontend
$address = $_GET['address'] ?? '';

if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Chưa có địa chỉ']);
    exit;
}

// 2. HÀM GEOCODING (Đổi địa chỉ -> Tọa độ) dùng OpenStreetMap
function getCoordinates($address) {
    // Nominatim yêu cầu User-Agent
    $opts = [
        "http" => [
            "header" => "User-Agent: HydrangeShopProject/1.0\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    
    // Gọi API miễn phí của OpenStreetMap
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
    
    try {
        $response = file_get_contents($url, false, $context);
        $data = json_decode($response, true);
        
        if (!empty($data) && isset($data[0])) {
            return [
                'lat' => (float)$data[0]['lat'],
                'lon' => (float)$data[0]['lon']
            ];
        }
    } catch (Exception $e) {
        return null;
    }
    return null;
}

// 3. CÔNG THỨC HAVERSINE (Tính khoảng cách km)
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Bán kính trái đất (km)

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earthRadius * $c;

    return round($distance, 1); // Làm tròn 1 số thập phân
}

// --- XỬ LÝ CHÍNH ---

$coords = getCoordinates($address);

if (!$coords) {
    // Nếu không tìm thấy tọa độ, trả về phí mặc định
    echo json_encode([
        'success' => true,
        'distance' => 0,
        'fee' => 30000, // Phí mặc định an toàn
        'message' => 'Không định vị được, tính phí mặc định'
    ]);
    exit;
}

// Tính khoảng cách
$distance = calculateDistance(SHOP_LAT, SHOP_LON, $coords['lat'], $coords['lon']);

// 4. BẢNG GIÁ SHIP (Logic của bạn)
$fee = 0;
if ($distance < 2) {
    $fee = 0; // Freeship dưới 2km
} elseif ($distance < 10) {
    $fee = 15000;
} elseif ($distance < 20) {
    $fee = 30000;
} else {
    $fee = 50000; // Xa quá tính 50k
}

echo json_encode([
    'success' => true,
    'distance' => $distance,
    'fee' => $fee,
    'message' => "Khoảng cách: {$distance}km"
]);
?>