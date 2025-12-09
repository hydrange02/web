<?php
session_start();
// Include file cấu hình và menu
include '../Config/Database.php';
include '../Compoment/Menu.php';

$db = Database::getInstance()->getConnection();

// --- 1. LẤY DANH SÁCH DANH MỤC (Cho tìm kiếm nâng cao) ---
$catQuery = $db->query("SELECT DISTINCT category FROM items ORDER BY category ASC");
$categoriesList = [];
while($catRow = $catQuery->fetch_assoc()) {
    $categoriesList[] = $catRow['category'];
}

// --- 2. LOGIC LỌC VÀ TÌM KIẾM NÂNG CAO ---
$limit = 10; // Số sản phẩm mỗi trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Nhận các tham số từ URL
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? intval($_GET['max_price']) : '';
$sort = $_GET['sort'] ?? 'newest';

$whereSQL = [];
$params = [];
$types = '';

// Xây dựng điều kiện WHERE
if ($category) {
    $whereSQL[] = "category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($search) {
    $whereSQL[] = "name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($min_price !== '') {
    $whereSQL[] = "price >= ?";
    $params[] = $min_price;
    $types .= 'i';
}
if ($max_price !== '') {
    $whereSQL[] = "price <= ?";
    $params[] = $max_price;
    $types .= 'i';
}

$whereClause = count($whereSQL) > 0 ? 'WHERE ' . implode(' AND ', $whereSQL) : '';

// Xử lý Sắp xếp (ORDER BY)
$orderSQL = "ORDER BY id DESC"; // Mặc định: Mới nhất
switch ($sort) {
    case 'price_asc':
        $orderSQL = "ORDER BY price ASC";
        break;
    case 'price_desc':
        $orderSQL = "ORDER BY price DESC";
        break;
    case 'best_selling':
        // Sắp xếp theo tổng số lượng bán được (Subquery tính tổng từ order_details)
        $orderSQL = "ORDER BY (SELECT COALESCE(SUM(quantity), 0) FROM order_details WHERE item_id = items.id) DESC";
        break;
    default: // 'newest'
        $orderSQL = "ORDER BY id DESC";
        break;
}

// --- 3. ĐẾM TỔNG SỐ SẢN PHẨM (Để phân trang) ---
$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM items $whereClause");
if (!empty($types)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();

// --- 4. LẤY DANH SÁCH SẢN PHẨM ---
$select_params = $params;
$select_params[] = $limit;
$select_params[] = $offset;
$select_types = $types . 'ii';

// Câu query chính
$sql = "SELECT * FROM items $whereClause $orderSQL LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param($select_types, ...$select_params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

// Tạo chuỗi tham số cho link phân trang (giữ lại bộ lọc)
$extraParam = '';
if($category) $extraParam .= '&category=' . urlencode($category);
if($search) $extraParam .= '&search=' . urlencode($search);
if($min_price !== '') $extraParam .= '&min_price=' . $min_price;
if($max_price !== '') $extraParam .= '&max_price=' . $max_price;
if($sort) $extraParam .= '&sort=' . $sort;
?>

<script>
    async function buyNow(itemId, price) {
        const formData = new FormData();
        formData.append('item_id', itemId);
        formData.append('quantity', 1); 
        formData.append('price', price);
        formData.append('total', price);

        try {
            const response = await fetch('../Config/AddCart.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = `../User_Screen/Checkout_Screen.php?cart_ids=${data.cart_id}`;
            } else {
                alert(data.message);
                if (data.message.includes('đăng nhập')) {
                    window.location.href = '../index.php';
                }
            }
        } catch (error) {
            console.error('Lỗi:', error);
            alert('Có lỗi xảy ra, vui lòng thử lại.');
        }
    }
</script>

<!DOCTYPE html>
<html lang="vi">

<head>
    <title>Trang Chủ - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-[#F8F9FA] font-sans text-gray-800">
    <style>
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }
        .animate-bounce-slow { animation: bounce-slow 3s infinite; }
        /* Tùy chỉnh thanh cuộn cho dropdown */
        select { 
            -webkit-appearance: none; -moz-appearance: none; appearance: none; 
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); 
            background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 1em; 
        }
    </style>

    <div>
        <header class="bg-gradient-to-r from-blue-600 to-teal-500 pb-12 shadow-lg rounded-b-[2rem] relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                <div class="swiper mySwiper w-full h-[200px] md:h-[320px] rounded-xl shadow-xl overflow-hidden border-2 border-white/30 relative group">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide relative">
                            <img src="../assets/web/banner.png" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-black/70 via-black/20 to-transparent flex flex-col justify-center px-8 md:px-20 text-white">
                                <span class="bg-yellow-400 text-black font-extrabold px-3 py-1 rounded-full w-fit mb-2 text-[10px] md:text-xs uppercase tracking-wider animate-pulse shadow-lg">⚡ Khuyến Mãi Sốc</span>
                                <h2 class="text-2xl md:text-5xl font-extrabold mb-2 leading-tight drop-shadow-lg">Siêu Sale <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-400">Mùa Hè Này</span></h2>
                                <p class="mb-4 text-sm md:text-lg text-gray-200 max-w-lg font-light">Giảm giá lên đến 50% cho tất cả các mặt hàng.</p>
                                <a href="#product-list" class="bg-white text-blue-700 px-6 py-2 rounded-full font-bold w-fit text-sm hover:bg-blue-50 transition shadow-xl transform hover:scale-105 duration-300">Mua Ngay</a>
                            </div>
                        </div>
                        <div class="swiper-slide relative">
                            <img src="https://img.freepik.com/free-photo/assortment-various-barbecue-food-grill-meat-vegetables_1150-37727.jpg" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50 flex flex-col justify-center items-center text-white text-center px-4">
                                <h2 class="text-2xl md:text-5xl font-extrabold mb-2 drop-shadow-md">Đồ Ăn Ngon - Giá Hợp Lý</h2>
                                <p class="mb-4 text-sm md:text-lg font-light">Thưởng thức hương vị tuyệt vời tại nhà.</p>
                                <a href="#product-list" class="border-2 border-white text-white px-6 py-2 rounded-full font-bold text-sm hover:bg-white hover:text-black transition duration-300">Khám Phá Menu</a>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-button-next text-white/80 hover:text-white transition after:text-xl"></div>
                    <div class="swiper-button-prev text-white/80 hover:text-white transition after:text-xl"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>

            <script>
                var swiper = new Swiper(".mySwiper", {
                    spaceBetween: 0, effect: "fade", centeredSlides: true, loop: true,
                    autoplay: { delay: 4000, disableOnInteraction: false },
                    pagination: { el: ".swiper-pagination", clickable: true, dynamicBullets: true },
                    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
                });
            </script>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20 mb-10">
            <div class="bg-white p-5 rounded-xl shadow-lg border border-gray-100">
                <form method="GET" class="flex flex-col gap-4">
                    <div class="flex flex-col md:flex-row gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                            <input name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Bạn muốn tìm gì hôm nay?" 
                                   class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-400 focus:bg-white outline-none transition text-sm">
                        </div>
                        
                        <div class="relative w-full md:w-1/4">
                            <select name="category" class="w-full h-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-lg focus:ring-blue-500 block p-3 pr-8 cursor-pointer font-medium">
                                <option value="">📂 Tất cả danh mục</option>
                                <?php foreach($categoriesList as $catName): ?>
                                    <option value="<?= htmlspecialchars($catName) ?>" <?= $category === $catName ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($catName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-3 items-center">
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <span class="text-sm font-bold text-gray-500 whitespace-nowrap">Giá từ:</span>
                            <input type="number" name="min_price" value="<?= $min_price ?>" placeholder="0" class="w-24 p-2 text-sm border rounded-lg bg-gray-50 focus:ring-blue-400 outline-none">
                            <span class="text-gray-400">-</span>
                            <input type="number" name="max_price" value="<?= $max_price ?>" placeholder="Đến..." class="w-24 p-2 text-sm border rounded-lg bg-gray-50 focus:ring-blue-400 outline-none">
                        </div>

                        <div class="flex items-center gap-2 w-full md:w-auto ml-0 md:ml-auto">
                            <span class="text-sm font-bold text-gray-500 whitespace-nowrap">Sắp xếp:</span>
                            <select name="sort" class="p-2 text-sm border rounded-lg bg-gray-50 focus:ring-blue-400 outline-none cursor-pointer w-full md:w-40">
                                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>✨ Mới nhất</option>
                                <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>🔥 Bán chạy nhất</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>💰 Giá thấp đến cao</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💎 Giá cao đến thấp</option>
                            </select>
                        </div>

                        <button type="submit" class="w-full md:w-auto bg-blue-600 text-white px-8 py-2.5 rounded-lg hover:bg-blue-700 transition font-bold shadow-md text-sm flex items-center justify-center gap-2">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        
                        <?php if($search || $category || $min_price || $max_price || $sort !== 'newest'): ?>
                            <a href="Home_Screen.php" class="w-full md:w-auto text-center text-red-500 hover:text-red-700 text-sm font-bold hover:underline px-2">
                                Xóa bộ lọc
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div id="product-list" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800 border-l-4 border-blue-600 pl-3">
                    <?php 
                        if($search) echo 'Kết quả cho: "' . htmlspecialchars($search) . '"';
                        else echo 'Danh Sách Sản Phẩm';
                    ?>
                    <span class="text-sm font-normal text-gray-500 ml-2">(<?= $totalRows ?> sản phẩm)</span>
                </h2>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6 justify-items-center">
                    <?php while ($row = $result->fetch_assoc()) { include '../Compoment/Card.php'; } ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                    <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" class="w-24 mx-auto mb-4 opacity-50">
                    <p class="text-lg text-gray-500 font-medium">Không tìm thấy sản phẩm nào phù hợp!</p>
                    <p class="text-sm text-gray-400 mt-1">Thử thay đổi mức giá hoặc từ khóa.</p>
                    <a href="Home_Screen.php" class="text-blue-600 hover:underline mt-4 inline-block font-bold">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
            <div class="flex justify-center items-center py-12">
                <nav class="flex items-center gap-2 bg-white p-2 rounded-xl shadow-sm border border-gray-100">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 . $extraParam ?>" class="w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    for ($i = 1; $i <= $totalPages; $i++):
                        if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)):
                    ?>
                            <a href="?page=<?= $i . $extraParam ?>"
                                class="w-10 h-10 flex items-center justify-center rounded-lg font-bold text-sm transition-all
                       <?= $i == $page ? 'bg-blue-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-100' ?>">
                                <?= $i ?>
                            </a>
                        <?php elseif ($i == $page - $range - 1 || $i == $page + $range + 1): ?>
                            <span class="w-10 h-10 flex items-center justify-center text-gray-400">...</span>
                    <?php endif; endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 . $extraParam ?>" class="w-10 h-10 flex items-center justify-center rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php include '../Compoment/Footer.php'; ?>
</body>
</html>