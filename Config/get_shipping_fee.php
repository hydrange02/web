<?php
// File: Web php/Config/get_shipping_fee.php
session_start();
header('Content-Type: application/json');

// 1. CẤU HÌNH
const SHOP_LAT = 10.7721; // Tọa độ Shop (Ví dụ: Chợ Bến Thành)
const SHOP_LON = 106.6983;
const DEFAULT_FEE = 30000; // Phí mặc định nếu lỗi API

$address = trim($_GET['address'] ?? '');

if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Chưa có địa chỉ']);
    exit;
}

// 2. KIỂM TRA CACHE (SESSION) - GIẢM TẢI API
// Tạo key cache dựa trên địa chỉ (viết thường, bỏ khoảng trắng thừa)
$cacheKey = md5(strtolower($address));

if (isset($_SESSION['shipping_cache'][$cacheKey])) {
    // Nếu đã từng tính phí cho địa chỉ này, trả về ngay lập tức
    echo json_encode($_SESSION['shipping_cache'][$cacheKey]);
    exit;
}

// 3. HÀM GEOCODING & TÍNH KHOẢNG CÁCH
function getCoordinates($address) {
    $opts = ["http" => ["header" => "User-Agent: HydrangeShopProject/1.0\r\n"]];
    $context = stream_context_create($opts);
    
    // Thêm timeout để không treo web quá lâu (2 giây)
    $ctx = stream_context_create(['http'=> ['timeout' => 2, 'header' => "User-Agent: HydrangeShopProject/1.0\r\n"]]);
    
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json&limit=1";
    
    try {
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) return null; // Lỗi mạng
        
        $data = json_decode($response, true);
        if (!empty($data) && isset($data[0])) {
            return ['lat' => (float)$data[0]['lat'], 'lon' => (float)$data[0]['lon']];
        }
    } catch (Exception $e) { return null; }
    return null;
}

function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; 
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadius * $c, 1);
}

// --- XỬ LÝ CHÍNH ---
$coords = getCoordinates($address);

// Biến kết quả trả về
$result = [];

if (!$coords) {
    // Fallback: Không tìm thấy hoặc lỗi API -> Trả về phí mặc định
    $result = [
        'success' => true, // Vẫn trả về true để khách đặt được hàng
        'distance' => 0,
        'fee' => DEFAULT_FEE,
        'message' => 'Tính phí mặc định (Không định vị được)'
    ];
} else {
    $distance = calculateDistance(SHOP_LAT, SHOP_LON, $coords['lat'], $coords['lon']);
    
    // Bảng giá
    $fee = 0;
    if ($distance < 2) $fee = 0;
    elseif ($distance < 10) $fee = 15000;
    elseif ($distance < 20) $fee = 30000;
    else $fee = 50000;

    $result = [
        'success' => true,
        'distance' => $distance,
        'fee' => $fee,
        'message' => "Khoảng cách: {$distance}km"
    ];
}

// 4. LƯU VÀO CACHE TRƯỚC KHI TRẢ VỀ
if (!isset($_SESSION['shipping_cache'])) {
    $_SESSION['shipping_cache'] = [];
}
$_SESSION['shipping_cache'][$cacheKey] = $result;

echo json_encode($result);
?>