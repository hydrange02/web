<?php
// File: Web php/User_Screen/Detail.php
session_start();
include '../Compoment/Menu.php';
include '../Config/Database.php';

$db = Database::getInstance()->getConnection();
$id = $_GET['id'] ?? null;
$item = null;
$recommended_items = [];

if ($id) {
    // Lấy thông tin sản phẩm
    $stmt = $db->prepare("SELECT * From items where id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    // Lấy sản phẩm gợi ý
    if ($item) {
        $category = $item['category'];
        $sql = "SELECT * FROM items WHERE category = ? AND id != ? ORDER BY RAND() LIMIT 4";
        $stmt_rec = $db->prepare($sql);
        $stmt_rec->bind_param("si", $category, $id);
        $stmt_rec->execute();
        $res_rec = $stmt_rec->get_result();
        $recommended_items = $res_rec->fetch_all(MYSQLI_ASSOC);
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
                    <img src="../<?= htmlspecialchars($item['image']) ?>" 
                         alt="<?= htmlspecialchars($item['name']) ?>" 
                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110 drop-shadow-lg">
                </div>
                <span class="absolute top-6 left-6 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md">HOT SALE</span>
            </div>

            <div class="w-full md:w-1/2 p-8 flex flex-col">
                <div class="mb-4">
                    <span class="text-blue-600 font-bold uppercase text-xs tracking-wider"><?= htmlspecialchars($item['category']) ?></span>
                    <h2 class="text-3xl font-extrabold text-gray-900 mt-2 leading-tight"><?= htmlspecialchars($item['name']) ?></h2>
                    <div class="flex items-center mt-2 text-yellow-400 text-sm">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                        <span class="text-gray-400 ml-2 text-xs">(128 đánh giá)</span>
                    </div>
                </div>

                <div class="text-4xl font-bold text-red-600 mb-6 flex items-end gap-2">
                    <?= number_format($item['price']) ?>₫
                    <span class="text-gray-400 text-lg font-normal line-through decoration-gray-400 decoration-2">
                        <?= number_format($item['price'] * 1.2) ?>₫
                    </span>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                    <h3 class="font-bold text-gray-700 mb-2 text-sm uppercase">Mô tả sản phẩm:</h3>
                    <p class="text-gray-600 text-sm leading-relaxed max-h-32 overflow-y-auto pr-2 custom-scrollbar">
                        <?= htmlspecialchars($item['description'] ?? 'Sản phẩm chất lượng cao từ Hydrange Shop.') ?>
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
                        <span id="quantity" class="w-12 h-10 flex items-center justify-center font-bold text-gray-800 bg-white">1</span>
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
        // LOGIC TĂNG GIẢM SỐ LƯỢNG UI
        const qtyEl = document.getElementById('quantity');
        let count = 1;
        
        document.getElementById('increase')?.addEventListener('click', () => {
            count++; qtyEl.textContent = count;
        });
        document.getElementById('decrease')?.addEventListener('click', () => {
            if (count > 1) { count--; qtyEl.textContent = count; }
        });

        // 2. Hàm thêm vào giỏ
        async function addToCart(itemId) {
            const price = <?= $item ? $item['price'] : 0 ?>;
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('quantity', count);
            formData.append('price', price);

            try {
                const res = await fetch("../Config/AddCart.php", { method: "POST", body: formData });
                const data = await res.json();
                
                if (data.success) {
                    // Dùng Swal nếu có thư viện, không thì alert
                    if(typeof Swal !== 'undefined') {
                        Swal.fire({icon: 'success', title: 'Đã thêm vào giỏ!', timer: 1500, showConfirmButton: false});
                    } else {
                        alert(data.message);
                    }
                } else {
                    alert(data.message);
                    if(data.message.includes("đăng nhập")) window.location.href = "../index.php";
                }
            } catch(e) { console.error(e); }
        }

        // 3. Hàm Mua Ngay
        async function buyNowDetail(itemId) {
            const price = <?= $item ? $item['price'] : 0 ?>;
            const formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('quantity', count);
            formData.append('price', price);

            try {
                const res = await fetch("../Config/AddCart.php", { method: "POST", body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.href = `Checkout_Screen.php?cart_ids=${data.cart_id}`;
                } else {
                    alert(data.message);
                    if(data.message.includes("đăng nhập")) window.location.href = "../index.php";
                }
            } catch(e) { console.error(e); }
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
    foreach($reviews as $rv) $sum += $rv['rating'];
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
                    <?php for($i=1;$i<=5;$i++) echo $i <= $avg_rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
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
                <label class="font-bold text-gray-700">Chọn số sao:</label>
                <select name="rating" class="ml-2 p-2 border rounded">
                    <option value="5">⭐⭐⭐⭐⭐ (Tuyệt vời)</option>
                    <option value="4">⭐⭐⭐⭐ (Tốt)</option>
                    <option value="3">⭐⭐⭐ (Bình thường)</option>
                    <option value="2">⭐⭐ (Tệ)</option>
                    <option value="1">⭐ (Rất tệ)</option>
                </select>
            </div>
            <textarea name="comment" rows="3" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none" placeholder="Chia sẻ cảm nhận của bạn..." required></textarea>
            <button type="submit" class="mt-3 bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700">Gửi Đánh Giá</button>
        </form>

        <div class="space-y-6">
            <?php foreach($reviews as $rv): ?>
                <div class="flex gap-4 border-b border-gray-100 pb-4 last:border-0">
                    <img src="<?= $rv['img'] ? $rv['img'] : '../assets/web/logo-removebg.png' ?>" class="w-12 h-12 rounded-full object-cover border">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-800"><?= htmlspecialchars($rv['username']) ?></span>
                            <span class="text-xs text-gray-400"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></span>
                        </div>
                        <div class="text-yellow-400 text-xs my-1">
                            <?php for($i=1;$i<=5;$i++) echo $i <= $rv['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        <p class="text-gray-600 text-sm"><?= htmlspecialchars($rv['comment']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(count($reviews) == 0): ?>
                <p class="text-gray-400 text-center italic">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function toggleReviewForm() {
        document.getElementById('review-form').classList.toggle('hidden');
    }

    async function submitReview(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        try {
            const res = await fetch('../Config/post_review.php', { method: 'POST', body: formData });
            const data = await res.json();
            
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        } catch(err) { console.error(err); alert('Lỗi kết nối'); }
    }
</script>

<?php include '../Compoment/Footer.php'; ?>
</body>
</html>