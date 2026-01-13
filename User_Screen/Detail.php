<?php
// File: Web php/User_Screen/Detail.php
session_start();
include '../Config/Database.php';
include '../Compoment/Menu.php';


$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? null;
$item = null;
$recommended_items = [];
$reviews = [];
$avg_rating = 0;
$count_reviews = 0;

if ($id) {
    // 1. Lấy thông tin sản phẩm
    $stmt = $db->prepare("SELECT * From items where id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item) {
        // 2. Lấy đánh giá (Chuyển lên trên để hiển thị ở phần Header)
        $rv_sql = "SELECT r.*, u.username, u.img FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.item_id = ? ORDER BY r.created_at DESC";
        $rv_stmt = $db->prepare($rv_sql);
        $rv_stmt->bind_param("i", $id);
        $rv_stmt->execute();
        $res_rv = $rv_stmt->get_result();
        $reviews = $res_rv->fetch_all(MYSQLI_ASSOC);
        $count_reviews = count($reviews);

        // Tính điểm trung bình
        if ($count_reviews > 0) {
            $sum = 0;
            foreach ($reviews as $rv) $sum += $rv['rating'];
            $avg_rating = round($sum / $count_reviews, 1);
        }

        // 3. Lấy sản phẩm gợi ý
        $category = $item['category'];
        $sql = "SELECT * FROM items WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 4";
        $stmt_rec = $db->prepare($sql);
        $stmt_rec->bind_param("si", $category, $id);
        $stmt_rec->execute();
        $recommended_items = $stmt_rec->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_rec->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <title><?= htmlspecialchars($item['name'] ?? 'Chi tiết sản phẩm') ?> - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 font-sans">

    <header class="h-[120px] w-full flex flex-col justify-center items-center bg-gradient-to-r from-blue-700 to-cyan-500 shadow-md">
        <h1 class="text-3xl font-bold text-white tracking-wide">Chi Tiết Sản Phẩm</h1>
        <p class="text-white/80 text-sm mt-1">Khám phá chất lượng tuyệt vời</p>
    </header>

    <?php if ($item) : ?>
        <div class="max-w-6xl mx-auto py-10 px-4">

            <div class="bg-white rounded-2xl shadow-xl overflow-hidden flex flex-col md:flex-row">

                <div class="w-full md:w-1/2 p-8 bg-gray-50 flex items-center justify-center border-r border-gray-100 relative group">
                    <div class="w-[400px] h-[400px] bg-white rounded-xl shadow-sm p-4 flex items-center justify-center">
                        <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                            class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110 drop-shadow-lg">
                    </div>
                    <?php if (isset($item['old_price']) && $item['price'] < $item['old_price']) : ?>
                        <span class="absolute top-6 left-6 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">HOT SALE</span>
                    <?php endif; ?>
                </div>

                <div class="w-full md:w-1/2 p-8 flex flex-col">
                    <div class="mb-4">
                        <span class="text-blue-600 font-bold uppercase text-xs tracking-wider"><?= htmlspecialchars($item['category']) ?></span>
                        <h2 class="text-3xl font-extrabold text-gray-900 mt-2 leading-tight"><?= htmlspecialchars($item['name']) ?></h2>

                        <div class="flex items-center mt-2 text-yellow-400 text-sm">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) echo '<i class="fas fa-star"></i>';
                                elseif ($i - 0.5 == $avg_rating) echo '<i class="fas fa-star-half-alt"></i>';
                                else echo '<i class="far fa-star"></i>';
                            }
                            ?>
                            <span class="text-gray-400 ml-2 text-xs">(<?= $count_reviews ?> đánh giá)</span>
                        </div>
                    </div>

                    <div class="text-4xl font-bold text-red-600 mb-6 flex items-end gap-2">
                        <?= number_format($item['price']) ?>₫
                        <?php if (isset($item['old_price']) && $item['old_price'] > $item['price']): ?>
                            <span class="text-gray-400 text-lg font-normal line-through decoration-gray-400 decoration-2">
                                <?= number_format($item['old_price']) ?>₫
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                        <h3 class="font-bold text-gray-700 mb-2 text-sm uppercase">Mô tả sản phẩm:</h3>
                        <p class="text-gray-600 text-sm leading-relaxed max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                            <?= nl2br(htmlspecialchars($item['description'] ?? 'Đang cập nhật...')) ?>
                        </p>
                        <div class="mt-3 text-sm text-gray-500">
                            <p><i class="fas fa-check-circle text-green-500 mr-2"></i>Thương hiệu: <span class="font-semibold text-gray-800"><?= htmlspecialchars($item['brand'] ?? 'Chính hãng') ?></span></p>
                            <p><i class="fas fa-truck text-blue-500 mr-2"></i>Giao hàng: 2-3 ngày làm việc</p>
                            <p><i class="fas fa-undo text-orange-500 mr-2"></i>Đổi trả: Miễn phí trong 7 ngày</p>
                        </div>
                    </div>

                    <div class="flex items-center mb-8">
                        <span class="font-bold text-gray-700 mr-4">Số lượng:</span>
                        <div class="flex items-center border-2 border-gray-200 rounded-lg overflow-hidden">
                            <button id="decrease" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold transition">-</button>
                            <input type="number" id="quantity" value="1" min="1" max="<?= $item['stock'] ?>"
                                class="w-16 h-10 text-center font-bold text-gray-800 bg-white outline-none appearance-none"
                                oninput="validateQuantity(this)">
                            <button id="increase" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold transition">+</button>
                        </div>
                        <span class="ml-4 text-sm text-gray-500"><?= $item['stock'] ?> sản phẩm có sẵn</span>
                    </div>

                    <div class="flex gap-4 mt-auto">
                        <button onclick="addToCart(<?= $item['id'] ?>)"
                            class="flex-1 bg-white border-2 border-blue-600 text-blue-600 py-3 rounded-xl font-bold hover:bg-blue-50 transition shadow-md flex justify-center items-center gap-2">
                            <i class="fas fa-cart-plus"></i> THÊM VÀO GIỎ
                        </button>
                        <button onclick="buyNowDetail(<?= $item['id'] ?>)"
                            class="flex-1 bg-gradient-to-r from-orange-500 to-red-500 text-white py-3 rounded-xl font-bold hover:shadow-lg hover:scale-[1.02] transition transform flex justify-center items-center gap-2">
                            <i class="fas fa-bolt"></i> MUA NGAY
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-12">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 border-l-4 border-blue-600 pl-3">Có thể bạn cũng thích</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <?php foreach ($recommended_items as $r) : ?>
                        <a href="Detail.php?id=<?= $r['id'] ?>" class="bg-white p-4 rounded-xl shadow hover:shadow-xl transition transform hover:-translate-y-1 border border-gray-100 group">
                            <div class="w-full h-40 mb-3 overflow-hidden rounded-lg bg-gray-50 flex items-center justify-center">
                                <img src="../<?= htmlspecialchars($r['image']) ?>" class="w-full h-full object-contain group-hover:scale-110 transition duration-300">
                            </div>
                            <h3 class="font-semibold text-gray-800 truncate mb-1"><?= htmlspecialchars($r['name']) ?></h3>
                            <p class="text-red-600 font-bold"><?= number_format($r['price']) ?>₫</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else : ?>
        <div class="text-center py-20 text-gray-500 text-xl">Không tìm thấy sản phẩm này.</div>
    <?php endif; ?>

    <script>
        const maxStock = <?= $item['stock'] ?>;
        const qtyInput = document.getElementById('quantity');

        // 1. Logic nút Tăng/Giảm
        document.getElementById('increase')?.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            if (current < maxStock) {
                qtyInput.value = current + 1;
            } else {
                showToast(`Chỉ còn ${maxStock} sản phẩm trong kho!`, 'warning');
            }
        });

        document.getElementById('decrease')?.addEventListener('click', () => {
            let current = parseInt(qtyInput.value) || 1;
            if (current > 1) {
                qtyInput.value = current - 1;
            }
        });

        // 2. Logic Validate Input (Khi người dùng nhập tay)
        function validateQuantity(input) {
            let val = parseInt(input.value);
            if (isNaN(val) || val < 1) {
                input.value = 1;
            } else if (val > maxStock) {
                input.value = maxStock;
                showToast(`Chỉ còn ${maxStock} sản phẩm!`, 'warning');
            }
        }

        // 3. Hàm Thêm vào giỏ (AJAX)
        async function addToCart(itemId) {
            const qtyInput = document.getElementById('quantity');
            const quantity = qtyInput ? parseInt(qtyInput.value) : 1;

            // Không gửi price, để Backend tự lấy cho an toàn
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('quantity', quantity);

            try {
                const res = await fetch("../Config/AddCart.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                    if (data.message.includes("đăng nhập")) {
                        setTimeout(() => window.location.href = "../index.php", 1500);
                    }
                }
            } catch (e) {
                console.error(e);
                showToast('Lỗi kết nối server!', 'error');
            }
        }

        // 2. HÀM MUA NGAY (Nút màu cam đỏ)
        function buyNowDetail(itemId) {
            const qtyInput = document.getElementById('quantity');
            const quantity = qtyInput ? (parseInt(qtyInput.value) || 1) : 1;

            // CHUYỂN HƯỚNG NGAY LẬP TỨC
            // Sang trang Checkout với tham số action=buynow
            window.location.href = `Checkout_Screen.php?action=buynow&id=${itemId}&qty=${quantity}`;
        }

        // Hàm hiển thị thông báo nhỏ (Custom Toast)
        function showToast(message, type = 'success') {
            const color = type === 'success' ? 'bg-green-500' : (type === 'warning' ? 'bg-yellow-500' : 'bg-red-500');
            const toast = document.createElement('div');
            toast.className = `fixed top-5 right-5 ${color} text-white px-6 py-3 rounded shadow-lg z-50 animate-bounce-slow font-bold`;
            toast.innerText = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
    <?php
    // Lấy danh sách đánh giá
    $rv_sql = "SELECT r.*, u.username, u.img FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.item_id = ? ORDER BY r.created_at DESC";
    $rv_stmt = $db->prepare($rv_sql);
    $rv_stmt->bind_param("i", $id);
    $rv_stmt->execute();
    $reviews = $rv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Tính điểm trung bình
    $avg_rating = 0;
    if (count($reviews) > 0) {
        $sum = 0;
        foreach ($reviews as $rv) $sum += $rv['rating'];
        $avg_rating = round($sum / count($reviews), 1);
    }
    ?>

    <div class="max-w-6xl mx-auto px-4 pb-12">
        <div class="bg-white rounded-2xl shadow-lg p-8 mt-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-l-4 border-yellow-400 pl-3">
                Đánh Giá & Nhận Xét
                <span class="text-sm font-normal text-gray-500 ml-2">(<?= count($reviews) ?> đánh giá)</span>
            </h2>

            <div class="flex items-center mb-8 bg-gray-50 p-6 rounded-xl">
                <div class="text-center mr-8">
                    <div class="text-5xl font-bold text-yellow-500"><?= $avg_rating ?>/5</div>
                    <div class="text-yellow-400 text-sm mt-1">
                        <?php for ($i = 1; $i <= 5; $i++) echo $i <= $avg_rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-gray-600">Bạn nghĩ gì về sản phẩm này?</p>
                    <button onclick="toggleReviewForm()" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition">Viết đánh giá</button>
                </div>
            </div>

            <form id="review-form" class="hidden mb-8 border-b pb-8" onsubmit="submitReview(event)">
                <input type="hidden" name="item_id" value="<?= $id ?>">
                <div class="mb-4">
                    <label class="font-bold text-gray-700 block mb-2">Đánh giá của bạn:</label>
                    <input type="hidden" name="rating" id="rating-value" value="5">

                    <div class="flex items-center gap-2" id="star-rating-group">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star text-3xl cursor-pointer transition-colors duration-200 text-yellow-400 star-icon"
                                data-value="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-1" id="rating-text">Tuyệt vời</p>
                </div>
                <textarea name="comment" rows="3" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none" placeholder="Chia sẻ cảm nhận của bạn..." required></textarea>
                <button type="submit" class="mt-3 bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">Gửi Đánh Giá</button>
            </form>

            <div class="space-y-6">
                <?php foreach ($reviews as $rv): ?>
                    <div class="flex gap-4 border-b border-gray-100 pb-4 last:border-0">
                        <img src="<?= $rv['img'] ? $rv['img'] : '../assets/web/logo-removebg.png' ?>" class="w-12 h-12 rounded-full object-cover border">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-800"><?= htmlspecialchars($rv['username']) ?></span>
                                <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></span>
                            </div>
                            <div class="text-yellow-400 text-xs my-1">
                                <?php for ($i = 1; $i <= 5; $i++) echo $i <= $rv['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </div>
                            <p class="text-gray-600 text-sm"><?= htmlspecialchars($rv['comment']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($reviews) == 0): ?>
                    <p class="text-gray-400 text-center italic">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleReviewForm() {
            document.getElementById('review-form').classList.toggle('hidden');
            // --- LOGIC XỬ LÝ CHỌN SAO (STAR RATING) ---
            const stars = document.querySelectorAll('.star-icon');
            const ratingInput = document.getElementById('rating-value');
            const ratingText = document.getElementById('rating-text');

            // Mảng text hiển thị trạng thái tương ứng 1-5 sao
            const ratingLabels = [
                "Rất tệ", // 1 sao
                "Tệ", // 2 sao
                "Bình thường", // 3 sao
                "Tốt", // 4 sao
                "Tuyệt vời" // 5 sao
            ];

            if (stars.length > 0) {
                stars.forEach((star, index) => {
                    // 1. Khi di chuột vào (Hover)
                    star.addEventListener('mouseenter', () => {
                        updateStarVisuals(index + 1);
                        ratingText.innerText = ratingLabels[index];
                    });

                    // 2. Khi click chọn
                    star.addEventListener('click', () => {
                        ratingInput.value = index + 1; // Cập nhật input ẩn
                        // Hiệu ứng nhún nhẹ khi click
                        star.classList.add('scale-125');
                        setTimeout(() => star.classList.remove('scale-125'), 200);
                    });
                });

                // 3. Khi di chuột ra khỏi vùng chọn sao (Mouse Leave)
                // Nó sẽ reset lại trạng thái hiển thị theo giá trị đã chọn trong input
                document.getElementById('star-rating-group').addEventListener('mouseleave', () => {
                    const currentValue = parseInt(ratingInput.value);
                    updateStarVisuals(currentValue);
                    ratingText.innerText = ratingLabels[currentValue - 1];
                });
            }

            // Hàm cập nhật màu sắc các ngôi sao
            function updateStarVisuals(value) {
                stars.forEach((s, i) => {
                    if (i < value) {
                        // Sáng (Vàng)
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        // Tối (Xám)
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });
            }
        }

        async function submitReview(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const res = await fetch('../Config/post_review.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi kết nối');
            }
        }
    </script>

    <?php include '../Compoment/Footer.php'; ?>
</body>

</html>