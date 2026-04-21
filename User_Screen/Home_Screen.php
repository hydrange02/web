<?php
session_start();
include '../Config/Database.php';
include '../Compoment/Menu.php';

$db = Database::getInstance()->getConnection();

// --- LOGIC PHP GIỮ NGUYÊN ---
$catQuery = $db->query("SELECT DISTINCT category FROM items ORDER BY category ASC");
$categoriesList = [];
while ($catRow = $catQuery->fetch_assoc()) $categoriesList[] = $catRow['category'];

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? intval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? intval($_GET['max_price']) : '';
$sort = $_GET['sort'] ?? 'newest';

$whereSQL = [];
$whereSQL[] = "i.stock > 0";
$params = [];
$types = '';
if ($category) {
    $whereSQL[] = "i.category = ?";
    $params[] = $category;
    $types .= 's';
}
if ($search) {
    $whereSQL[] = "i.name LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}
if ($min_price !== '') {
    $whereSQL[] = "i.price >= ?";
    $params[] = $min_price;
    $types .= 'i';
}
if ($max_price !== '') {
    $whereSQL[] = "i.price <= ?";
    $params[] = $max_price;
    $types .= 'i';
}
$whereClause = count($whereSQL) > 0 ? 'WHERE ' . implode(' AND ', $whereSQL) : '';

$orderSQL = "ORDER BY i.id DESC";
switch ($sort) {
    case 'price_asc':
        $orderSQL = "ORDER BY i.price ASC";
        break;
    case 'price_desc':
        $orderSQL = "ORDER BY i.price DESC";
        break;
    case 'best_selling':
        $orderSQL = "ORDER BY i.sold_count DESC";
        break;
}

$countSql = "SELECT COUNT(*) AS total FROM items i $whereClause";
$countStmt = $db->prepare($countSql);
if (!empty($types)) $countStmt->bind_param($types, ...$params);
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();

$select_params = $params;
$select_params[] = $limit;
$select_params[] = $offset;
$select_types = $types . 'ii';

$sql = "SELECT i.*, COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as review_count 
        FROM items i LEFT JOIN reviews r ON i.id = r.item_id 
        $whereClause GROUP BY i.id $orderSQL LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param($select_types, ...$select_params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$extraParam = '';
if ($category) $extraParam .= '&category=' . urlencode($category);
if ($search) $extraParam .= '&search=' . urlencode($search);
if ($min_price !== '') $extraParam .= '&min_price=' . $min_price;
if ($max_price !== '') $extraParam .= '&max_price=' . $max_price;
if ($sort) $extraParam .= '&sort=' . $sort;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <title>Trang Chủ - Hydrange Shop</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .swiper-pagination-bullet {
            background: white;
            opacity: 0.5;
            width: 10px;
            height: 10px;
        }

        .swiper-pagination-bullet-active {
            background: #2563eb;
            opacity: 1;
            width: 20px;
            border-radius: 5px;
        }
    </style>
</head>

<body class="bg-[#F8F9FA] text-gray-800">

    <div class="relative w-full h-[400px] md:h-[500px] overflow-hidden group">
        <div class="swiper mySwiper w-full h-full">
            <div class="swiper-wrapper">

                <div class="swiper-slide relative">
                    <img src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=1974&auto=format&fit=crop"
                        class="w-full h-full object-cover brightness-[0.6]" alt="Siêu thị">
                    <div class="absolute inset-0 flex flex-col justify-center items-center text-center text-white px-4 z-10">
                        <span class="bg-blue-600 text-white font-bold px-4 py-1 rounded-full text-xs uppercase tracking-widest mb-4 animate-pulse">
                            🎉 Chào mừng đến Hydrange Shop
                        </span>
                        <h1 class="text-4xl md:text-6xl font-black mb-4 drop-shadow-xl">
                            Thế Giới Tiện Lợi <br> <span class="text-blue-400">Trong Tầm Tay</span>
                        </h1>
                        <p class="text-gray-200 text-lg md:text-xl font-medium max-w-2xl mb-8">
                            Từ thực phẩm, đồ uống đến nhu yếu phẩm hàng ngày.
                        </p>
                        <a href="Home_Screen.php" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-full font-bold transition transform hover:scale-105 shadow-lg flex items-center gap-2 relative z-20">
                            Mua Sắm Ngay <i class="fas fa-shopping-cart"></i>
                        </a>
                    </div>
                </div>

                <div class="swiper-slide relative">
                    <img src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=1974&auto=format&fit=cropssss"
                        class="w-full h-full object-cover brightness-[0.6]" alt="">
                    <div class="absolute inset-0 flex flex-col justify-center items-start text-left text-white px-10 md:px-24 z-10">
                        <h2 class="text-4xl md:text-7xl font-black mb-4 leading-tight">
                            Bữa Phụ <br> <span class="text-yellow-400">Cực Đã</span>
                        </h2>
                        <p class="text-gray-200 text-lg mb-8 max-w-lg">
                            Nạp năng lượng với hàng trăm loại bánh kẹo.
                        </p>
                        <a href="?category=Đồ ăn vặt#product-list" class="bg-yellow-400 hover:bg-yellow-500 text-black px-8 py-3 rounded-full font-bold transition shadow-lg relative z-20">
                            Xem Menu Ăn Vặt <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>

                <div class="swiper-slide relative">
                    <img src="https://images.unsplash.com/photo-1578916171728-46686eac8d58?q=80&w=1974&auto=format&fit=crop"
                        class="w-full h-full object-cover brightness-[0.5]" alt="">
                    <div class="absolute inset-0 flex flex-col justify-center items-end text-right text-white px-10 md:px-24 z-10">
                        <h2 class="text-4xl md:text-6xl font-black mb-4">
                            Giao Hàng <br> <span class="text-green-400">Siêu Tốc 2H</span>
                        </h2>
                        <p class="text-gray-200 text-lg mb-8 max-w-lg">
                            Miễn phí vận chuyển cho đơn hàng từ 500k.
                        </p>
                        <a href="?sort=best_selling#product-list" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-full font-bold transition shadow-lg relative z-20">
                            Xem Sản Phẩm HOT
                        </a>
                    </div>
                </div>

            </div>
            <div class="swiper-button-next text-white/70 hover:text-white transition hidden md:flex"></div>
            <div class="swiper-button-prev text-white/70 hover:text-white transition hidden md:flex"></div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
        
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-15 relative z-10 pb-20">

        <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100 mb-12 mt-8">
            <form method="GET" class="flex flex-col gap-5">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-1 group">
                        <i class="fas fa-search absolute left-4 top-4 text-gray-400 group-focus-within:text-blue-500 transition"></i>
                        <input name="search" type="text" value="<?= htmlspecialchars($search) ?>" placeholder="Bạn muốn tìm gì? (Ví dụ: Mì gói, Snack, Nước ngọt...)"
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white outline-none transition font-medium">
                    </div>
                    <select name="category" class="w-full md:w-1/4 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3.5 focus:ring-blue-500 outline-none cursor-pointer font-bold text-gray-600">
                        <option value="">📂 Tất cả danh mục</option>
                        <?php foreach ($categoriesList as $catName): ?>
                            <option value="<?= htmlspecialchars($catName) ?>" <?= $category === $catName ? 'selected' : '' ?>><?= htmlspecialchars($catName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-4 justify-between pt-2 border-t border-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
                            <span class="text-sm font-bold text-gray-500">Giá:</span>
                            <input type="number" name="min_price" value="<?= $min_price ?>" placeholder="Min" class="w-20 bg-transparent text-sm outline-none text-center font-bold">
                            <span class="text-gray-400">-</span>
                            <input type="number" name="max_price" value="<?= $max_price ?>" placeholder="Max" class="w-20 bg-transparent text-sm outline-none text-center font-bold">
                        </div>
                    </div>

                    <div class="flex items-center gap-3 ml-auto w-full md:w-auto">
                        <select name="sort" class="px-4 py-2.5 bg-white border border-gray-200 rounded-lg text-sm font-bold text-gray-600 focus:ring-blue-500 outline-none cursor-pointer shadow-sm hover:border-blue-300 transition">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>✨ Mới nhất</option>
                            <option value="best_selling" <?= $sort === 'best_selling' ? 'selected' : '' ?>>🔥 Bán chạy</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>💰 Giá tăng dần</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>💎 Giá giảm dần</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg font-bold hover:bg-blue-700 transition shadow-md text-sm flex items-center gap-2">
                            <i class="fas fa-filter"></i> Lọc
                        </button>
                        <?php if ($search || $category || $min_price || $max_price || $sort !== 'newest'): ?>
                            <a href="Home_Screen.php" class="text-red-500 hover:text-red-700 text-sm font-bold px-2 underline decoration-dashed">Xóa lọc</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div id="product-list">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-2 bg-gradient-to-b from-blue-500 to-indigo-600 rounded-full"></div>
                    <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight">
                        <?php echo $search || $category ? 'Kết quả tìm kiếm' : 'Sản Phẩm Đang Bán'; ?>
                    </h2>
                </div>
                <span class="text-gray-500 font-medium bg-white px-3 py-1 rounded-full border border-gray-200 text-sm"><?= $totalRows ?> sản phẩm</span>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                    <?php
                    while ($row = $result->fetch_assoc()):
                        $originalPrice = $row['original_price'] ?? $row['price'];
                        $isSale = ($originalPrice > $row['price']);
                        $discountPercent = $isSale ? round((($originalPrice - $row['price']) / $originalPrice) * 100) : 0;
                        $rating = floatval($row['avg_rating']);
                    ?>
                        <div class="group relative bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 overflow-hidden flex flex-col 
                                    transform transition-all duration-300 hover:-translate-y-2 hover:shadow-2xl hover:border-blue-200">

                            <?php if ($isSale): ?>
                                <span class="absolute top-3 left-3 bg-red-500 text-white text-[10px] font-black px-2.5 py-1 rounded-md shadow-md z-10 tracking-wide">
                                    -<?= $discountPercent ?>%
                                </span>
                            <?php endif; ?>

                            <div class="relative h-52 p-6 bg-gray-50 flex items-center justify-center overflow-hidden">
                                <a href="Detail.php?id=<?= $row['id'] ?>" class="block w-full h-full">
                                    <img src="<?= (strpos($row['image'], 'http') === 0 ? $row['image'] : '../' . htmlspecialchars($row['image'])) ?>"
                                        onerror="this.src='https://placehold.co/200x200?text=No+Image'"
                                        class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-110"
                                        alt="<?= htmlspecialchars($row['name']) ?>">
                                </a>
                                <button onclick="addToCart(<?= $row['id'] ?>)"
                                    class="absolute bottom-3 right-3 bg-blue-600 text-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hover:bg-blue-700 hover:scale-110 z-20">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>

                            <div class="p-5 flex flex-col flex-1">
                                <div class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mb-1 truncate">
                                    <?= htmlspecialchars($row['category'] ?? 'Khác') ?>
                                </div>
                                <a href="Detail.php?id=<?= $row['id'] ?>">
                                    <h3 class="font-bold text-gray-800 text-sm line-clamp-2 h-10 mb-2 group-hover:text-blue-600 transition-colors leading-relaxed" title="<?= htmlspecialchars($row['name']) ?>">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </h3>
                                </a>

                                <div class="mt-auto pt-3 border-t border-gray-50 flex items-end justify-between">
                                    <div class="flex flex-col">
                                        <?php if ($isSale): ?>
                                            <span class="text-xs text-gray-400 line-through font-medium"><?= number_format($originalPrice) ?>₫</span>
                                        <?php endif; ?>
                                        <span class="text-lg font-extrabold text-red-600"><?= number_format($row['price']) ?>₫</span>
                                    </div>
                                    <div class="flex items-center bg-yellow-50 px-1.5 py-0.5 rounded text-yellow-500 text-xs font-bold gap-1">
                                        <span><?= number_format($rating, 1) ?></span>
                                        <i class="fas fa-star text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center mt-14">
                        <nav class="flex items-center gap-1 bg-white px-3 py-2 rounded-full shadow-lg border border-gray-100 select-none">

                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 . $extraParam ?>"
                                    class="w-10 h-10 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition"
                                    title="Trang trước">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $range = 2; // Số trang hiển thị xung quanh trang hiện tại (Ví dụ: ... 4 5 [6] 7 8 ...)

                            for ($i = 1; $i <= $totalPages; $i++) {
                                // Điều kiện hiển thị: Trang đầu (1), Trang cuối, hoặc nằm trong khoảng range của trang hiện tại
                                if ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range)) {
                            ?>
                                    <a href="?page=<?= $i . $extraParam ?>"
                                        class="w-10 h-10 flex items-center justify-center rounded-full text-sm font-bold transition-all
                                   <?= $i == $page
                                        ? 'bg-blue-600 text-white shadow-md scale-110'
                                        : 'text-gray-500 hover:bg-gray-100 hover:text-blue-600' ?>">
                                        <?= $i ?>
                                    </a>
                            <?php
                                }
                                // Logic hiển thị dấu ...
                                elseif ($i == $page - $range - 1 || $i == $page + $range + 1) {
                                    echo '<span class="w-10 h-10 flex items-center justify-center text-gray-400 font-bold">...</span>';
                                }
                            }
                            ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 . $extraParam ?>"
                                    class="w-10 h-10 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition"
                                    title="Trang sau">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>

                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-300">
                    <div class="bg-gray-50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-box-open text-3xl text-gray-300"></i>
                    </div>
                    <p class="text-gray-500 font-medium text-lg">Chưa có sản phẩm nào.</p>
                    <a href="Home_Screen.php" class="text-blue-600 hover:underline mt-2 inline-block font-bold">Tải lại trang</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        var swiper = new Swiper(".mySwiper", {
            spaceBetween: 0,
            effect: "fade",
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });

        async function addToCart(itemId) {
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('quantity', 1); // Mặc định mua 1 cái ở trang chủ

            try {
                // Gọi API thêm vào giỏ
                const response = await fetch('../Config/AddCart.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    // Hiện thông báo đẹp (Toast) ở góc trên
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã thêm vào giỏ!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });

                    // Cập nhật số lượng trên icon giỏ hàng ngay lập tức
                    const badge = document.getElementById('cart-count-badge');
                    if (badge && data.total_count > 0) {
                        badge.innerText = data.total_count > 9 ? '9+' : data.total_count;
                        badge.classList.remove('hidden');
                    }
                } else {
                    if (data.message && data.message.includes('đăng nhập')) {
                        // Nếu chưa đăng nhập thì chuyển hướng
                        window.location.href = '../index.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: data.message
                        });
                    }
                }
            } catch (e) {
                console.error(e);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: 'Vui lòng thử lại sau.'
                });
            }
        }
    </script>
    <?php include '../Compoment/Footer.php'; ?>
</body>

</html>