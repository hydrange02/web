<?php
// File: Web php/User_Screen/Checkout_Screen.php
session_start();
include '../Config/Database.php';

$user_id = $_SESSION['user_id'] ?? null;

// --- 1. NHẬN DỮ LIỆU ---
$cart_ids_str = $_POST['cart_ids'] ?? '';

if (!$user_id || empty($cart_ids_str)) {
    header('Location: Cart_Screen.php');
    exit;
}

// --- 2. CSRF TOKEN ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$db = Database::getInstance()->getConnection();

// Lấy thông tin user mặc định
$userQuery = $db->prepare("SELECT username, phone FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userInfo = $userQuery->get_result()->fetch_assoc();

// Lấy sản phẩm thanh toán
$cart_ids_array = array_map('intval', explode(',', $cart_ids_str));
$placeholders = str_repeat('?,', count($cart_ids_array) - 1) . '?';
$types = str_repeat('i', count($cart_ids_array));
$params = $cart_ids_array;
$params[] = $user_id;
$types .= 'i';

$sql = "SELECT c.quantity, c.total, i.name, i.price, i.image 
        FROM cart c 
        JOIN items i ON c.item_id = i.id 
        WHERE c.id IN ($placeholders) AND c.user_id = ?";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$grand_total = 0;
foreach ($items as $item) {
    $grand_total += $item['total'];
}

// --- 3. LẤY DANH SÁCH VOUCHER CỦA TÔI (SỬA LOGIC DATE) ---
// Lấy các voucher chưa dùng, còn hạn (hoặc vĩnh viễn), và đã phát hành
$v_sql = "SELECT uv.id, v.code, v.discount_type, v.discount_amount, v.min_order_amount, v.end_date 
          FROM user_vouchers uv
          JOIN vouchers v ON uv.voucher_id = v.id
          WHERE uv.user_id = ? AND uv.is_used = 0 
          AND (v.start_date IS NOT NULL AND v.start_date <= CURDATE())
          AND (v.end_date IS NULL OR v.end_date >= CURDATE())
          ORDER BY v.discount_amount DESC";
$v_stmt = $db->prepare($v_sql);
$v_stmt->bind_param("i", $user_id);
$v_stmt->execute();
$my_vouchers = $v_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <title>Thanh Toán - Hydrange Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .input-group:focus-within label, .input-group:focus-within i { color: #2563eb; }
        .voucher-ticket {
            background-image: radial-gradient(circle at 0 50%, transparent 6px, #fff 6px), radial-gradient(circle at 100% 50%, transparent 6px, #fff 6px);
            background-size: 50% 100%;
            background-repeat: no-repeat;
            background-position: 0 0, 100% 0;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <?php include '../Compoment/Menu.php'; ?>

    <div class="p-4 md:p-8 max-w-7xl mx-auto">
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 mb-8 border-b pb-4 flex items-center gap-3">
            <i class="fas fa-file-invoice-dollar text-blue-600"></i> Xác Nhận Đơn Hàng
        </h1>

        <form id="checkout-form" action="../Config/checkout.php" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="cart_ids" value="<?= htmlspecialchars($cart_ids_str) ?>">

            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-red-500"></i> Địa chỉ nhận hàng
                        </h2>
                        <button type="button" onclick="openAddressModal()" class="text-blue-600 bg-blue-50 px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-100 transition flex items-center gap-2">
                            <i class="fas fa-address-book"></i> Chọn từ sổ địa chỉ
                        </button>
                    </div>

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="input-group">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Họ và tên</label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-3 top-3 text-gray-400"></i>
                                    <input type="text" id="r_name" name="receiver_name" value="<?= htmlspecialchars($userInfo['username']) ?>" required
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white font-medium">
                                </div>
                            </div>
                            <div class="input-group">
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Số điện thoại</label>
                                <div class="relative">
                                    <i class="fas fa-phone absolute left-3 top-3 text-gray-400"></i>
                                    <input type="text" id="r_phone" name="receiver_phone" value="<?= htmlspecialchars($userInfo['phone']) ?>" required pattern="[0-9]{10,11}"
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white font-medium">
                                </div>
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Địa chỉ chi tiết</label>
                            <div class="relative">
                                <i class="fas fa-home absolute left-3 top-3.5 text-gray-400"></i>
                                <textarea id="r_address" name="shipping_address" rows="3" required placeholder="Số nhà, tên đường, phường/xã, quận/huyện..."
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white font-medium resize-none"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-credit-card text-blue-500"></i> Thanh toán
                    </h2>

                    <div class="space-y-3">
                        <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:shadow-sm">
                            <input type="radio" name="payment_method" value="COD" checked class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500" onchange="toggleBankInfo(false)">
                            <div class="ml-4">
                                <span class="block font-bold text-gray-800">Thanh toán khi nhận hàng (COD)</span>
                                <span class="text-xs text-gray-500">Thanh toán tiền mặt cho shipper khi nhận được hàng</span>
                            </div>
                            <i class="fas fa-money-bill-wave ml-auto text-green-500 text-xl"></i>
                        </label>

                        <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 has-[:checked]:shadow-sm">
                            <input type="radio" name="payment_method" value="Banking" class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500" onchange="toggleBankInfo(true)">
                            <div class="ml-4">
                                <span class="block font-bold text-gray-800">Chuyển khoản ngân hàng (QR Code)</span>
                                <span class="text-xs text-gray-500">Quét mã VietQR để thanh toán nhanh chóng</span>
                            </div>
                            <i class="fas fa-university ml-auto text-blue-500 text-xl"></i>
                        </label>
                    </div>

                    <div id="bank-info" class="hidden mt-6 p-6 bg-blue-50 rounded-xl border border-blue-100 text-center animate-pulse">
                        <div class="inline-block bg-white p-2 rounded-lg shadow-sm mb-3">
                            <img src="https://img.vietqr.io/image/MB-0898341746-compact.png?amount=<?= $grand_total ?>&addInfo=Thanh toan don hang&time=<?= time() ?>" alt="QR Code" class="w-40 h-40 object-contain">
                        </div>
                        <p class="font-bold text-blue-800 text-sm">Quét mã để thanh toán</p>
                        <p class="text-xs text-gray-600 mt-1">Nội dung: <b>Thanh toan don hang</b></p>
                        <p class="text-[10px] text-red-500 italic mt-2">*Sau khi chuyển khoản thành công, vui lòng ấn Đặt Hàng</p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-5">
                <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 sticky top-24">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Đơn hàng (<?= count($items) ?> món)</h2>

                    <div class="max-h-[300px] overflow-y-auto custom-scrollbar pr-2 mb-6 space-y-4 p-2 bg-gray-50 rounded-xl">
                        <?php foreach ($items as $item): ?>
                            <div class="flex items-center gap-4 bg-white p-2 rounded-lg border border-gray-100">
                                <div class="relative w-14 h-14 flex-shrink-0">
                                    <img src="../<?= htmlspecialchars($item['image']) ?>" class="w-full h-full object-contain">
                                    <span class="absolute -top-1 -right-1 bg-gray-800 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full font-bold"><?= $item['quantity'] ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-gray-800 truncate"><?= htmlspecialchars($item['name']) ?></p>
                                </div>
                                <span class="font-bold text-gray-800 text-sm"><?= number_format($item['total']) ?>đ</span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-4 bg-orange-50 p-4 rounded-xl border border-orange-100">
                        <div class="flex justify-between items-center mb-2">
                            <label class="text-xs font-bold text-orange-600 uppercase flex items-center gap-1">
                                <i class="fas fa-ticket-alt"></i> Voucher Giảm Giá
                            </label>
                            <button type="button" onclick="openVoucherModal()" class="text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                Chọn Voucher có sẵn
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <input type="text" id="voucher_code_input" name="voucher_code" placeholder="Nhập mã voucher"
                                class="flex-1 p-2 border border-orange-200 rounded-lg text-sm uppercase font-bold text-gray-700 focus:ring-2 focus:ring-orange-400 outline-none">
                            <button type="button" onclick="checkVoucher()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm whitespace-nowrap">
                                Áp dụng
                            </button>
                        </div>
                        <p id="voucher_message" class="text-xs mt-2 h-4 font-medium transition-all duration-300"></p>
                    </div>

                    <div class="border-t border-dashed border-gray-300 pt-4 space-y-2">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Tạm tính</span>
                            <span id="sub-total" data-value="<?= $grand_total ?>"><?= number_format($grand_total) ?>đ</span>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Phí vận chuyển</span>
                            <span id="shipping-fee" class="text-green-600 font-bold">0đ</span>
                            <input type="hidden" name="shipping_fee" id="input_shipping_fee" value="0">
                        </div>

                        <div class="flex justify-between text-sm text-green-600 font-bold">
                            <span>Voucher giảm giá</span>
                            <span id="discount-display">-0đ</span>
                        </div>

                        <div class="flex justify-between items-center pt-3 mt-2 border-t border-gray-100">
                            <span class="text-base font-bold text-gray-800">Tổng cộng</span>
                            <span id="final-total" class="text-2xl font-extrabold text-red-600"><?= number_format($grand_total) ?>đ</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-300 flex items-center justify-center gap-2 group">
                        <span>ĐẶT HÀNG NGAY</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="address-modal" class="fixed inset-0 bg-gray-900/60 hidden z-50 flex justify-center items-center backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white w-[600px] max-w-[95%] h-[550px] rounded-2xl shadow-2xl flex flex-col overflow-hidden relative">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-extrabold text-gray-800">Sổ Địa Chỉ</h3>
                <button onclick="closeAddressModal()" class="text-gray-400 hover:text-red-500 transition"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="flex-1 overflow-hidden relative">
                <div id="view-list" class="h-full flex flex-col p-6 absolute inset-0 transition-transform duration-300 translate-x-0">
                    <div id="address-list-container" class="flex-1 overflow-y-auto custom-scrollbar space-y-3 pr-2"></div>
                    <button onclick="showAddForm()" class="mt-4 w-full bg-blue-100 text-blue-700 py-3 rounded-xl font-bold hover:bg-blue-200 transition border border-blue-200 flex items-center justify-center gap-2"><i class="fas fa-plus-circle"></i> Thêm địa chỉ mới</button>
                </div>
                <div id="view-form" class="h-full flex flex-col p-6 absolute inset-0 bg-white transition-transform duration-300 translate-x-full">
                    <h4 id="form-title" class="text-sm font-bold text-gray-500 uppercase mb-4">Thêm địa chỉ mới</h4>
                    <input type="hidden" id="edit_id" value="0">
                    <div class="space-y-4 flex-1">
                        <div><label class="block text-xs font-bold text-gray-600 mb-1">Tên</label><input type="text" id="input_name" class="w-full p-3 border rounded-lg bg-gray-50"></div>
                        <div><label class="block text-xs font-bold text-gray-600 mb-1">SĐT</label><input type="text" id="input_phone" class="w-full p-3 border rounded-lg bg-gray-50"></div>
                        <div><label class="block text-xs font-bold text-gray-600 mb-1">Địa chỉ</label><textarea id="input_address" rows="3" class="w-full p-3 border rounded-lg bg-gray-50 resize-none"></textarea></div>
                    </div>
                    <div class="flex gap-3 mt-4 pt-4 border-t border-gray-100">
                        <button onclick="showList()" class="flex-1 bg-gray-100 text-gray-600 py-2.5 rounded-lg font-bold">Quay lại</button>
                        <button onclick="saveAddress()" class="flex-1 bg-blue-600 text-white py-2.5 rounded-lg font-bold">Lưu địa chỉ</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="voucher-modal" class="fixed inset-0 bg-gray-900/60 hidden z-50 flex justify-center items-center backdrop-blur-sm">
        <div class="bg-white w-[500px] max-w-[95%] rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[600px]">
            <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-ticket-alt"></i> Kho Voucher Của Bạn</h3>
                <button onclick="closeVoucherModal()" class="text-white/80 hover:text-white transition"><i class="fas fa-times text-xl"></i></button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 bg-gray-50 custom-scrollbar space-y-3">
                <?php if (empty($my_vouchers)): ?>
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-box-open text-4xl mb-2"></i>
                        <p>Bạn chưa có voucher nào.</p>
                        <a href="VoucherWallet_Screen.php" class="text-blue-500 text-sm hover:underline">Đổi điểm lấy voucher ngay</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_vouchers as $v):
                        // Kiểm tra điều kiện đơn tối thiểu ngay tại đây để hiển thị UI
                        $is_eligible = $grand_total >= $v['min_order_amount'];
                        $opacity = $is_eligible ? 'opacity-100' : 'opacity-60 grayscale';
                        $cursor = $is_eligible ? 'cursor-pointer hover:shadow-md' : 'cursor-not-allowed';
                        $click_action = $is_eligible ? "selectVoucher('{$v['code']}')" : "";
                        $status_text = $is_eligible ? '<span class="text-green-600 text-xs font-bold">Có thể dùng</span>' : '<span class="text-red-500 text-xs font-bold">Chưa đủ điều kiện</span>';
                        $date_text = !empty($v['end_date']) ? 'HSD: '.date('d/m/Y', strtotime($v['end_date'])) : 'Vĩnh viễn';
                    ?>
                        <div class="bg-white border border-gray-200 rounded-lg flex overflow-hidden relative transition <?= $opacity ?> <?= $cursor ?>" onclick="<?= $click_action ?>">
                            <div class="w-24 bg-gray-800 text-white flex flex-col items-center justify-center p-2 relative voucher-ticket">
                                <span class="text-lg font-bold text-yellow-400">
                                    <?= $v['discount_type'] == 'percent' ? $v['discount_amount'] . '%' : number_format($v['discount_amount'] / 1000) . 'K' ?>
                                </span>
                                <span class="text-[10px] uppercase">GIẢM</span>
                            </div>
                            <div class="flex-1 p-3 flex flex-col justify-center">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-gray-800 text-sm">Mã: <?= $v['code'] ?></span>
                                    <?= $status_text ?>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Đơn tối thiểu: <?= number_format($v['min_order_amount']) ?>đ</p>
                                <p class="text-[10px] text-gray-400 mt-1"><?= $date_text ?></p>
                            </div>
                            <?php if ($is_eligible): ?>
                                <div class="absolute right-0 bottom-0 p-2">
                                    <button class="bg-blue-100 text-blue-600 text-xs font-bold px-3 py-1 rounded-full hover:bg-blue-600 hover:text-white transition">Dùng</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const name = document.getElementById('r_name').value.trim();
            const phone = document.getElementById('r_phone').value.trim();
            const address = document.getElementById('r_address').value.trim();

            if (!name || !phone || !address) {
                e.preventDefault(); 
                Swal.fire({ icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng nhập đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!' });
                return;
            }

            const phoneRegex = /^(0|\+84)[0-9]{9,10}$/; 
            if (!phoneRegex.test(phone)) {
                e.preventDefault(); 
                Swal.fire({ icon: 'error', title: 'Số điện thoại không hợp lệ', text: 'Vui lòng nhập đúng định dạng số điện thoại (10-11 số).' });
                document.getElementById('r_phone').focus();
                document.getElementById('r_phone').classList.add('ring-2', 'ring-red-500');
                return;
            }
        });

        document.getElementById('r_phone').addEventListener('input', function() {
            this.classList.remove('ring-2', 'ring-red-500');
        });

        // --- Logic Voucher & Address Modal giữ nguyên ---
        async function checkVoucher() {
            const code = document.getElementById('voucher_code_input').value.trim();
            const subTotal = parseInt(document.getElementById('sub-total').dataset.value);
            const msg = document.getElementById('voucher_message');

            if (!code) { msg.className = "text-xs mt-2 text-red-500 font-bold"; msg.innerText = "Vui lòng nhập mã voucher!"; return; }
            msg.className = "text-xs mt-2 text-gray-500 italic"; msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';

            try {
                const formData = new FormData();
                formData.append('code', code);
                formData.append('total_order', subTotal);
                const res = await fetch('../Config/check_voucher.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    msg.className = "text-xs mt-2 text-green-600 font-bold";
                    msg.innerHTML = `<i class="fas fa-check-circle"></i> ${data.message}`;
                    document.getElementById('discount-display').innerText = `-${new Intl.NumberFormat('vi-VN').format(data.discount)}đ`;
                    updateFinalTotal(data.discount);
                } else {
                    msg.className = "text-xs mt-2 text-red-600 font-bold";
                    msg.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${data.message}`;
                    document.getElementById('discount-display').innerText = `-0đ`;
                    updateFinalTotal(0);
                }
            } catch (e) { console.error(e); }
        }

        function updateFinalTotal(discount) {
            const subTotal = parseInt(document.getElementById('sub-total').dataset.value);
            const shipping = parseInt(document.getElementById('input_shipping_fee').value) || 0;
            let final = subTotal + shipping - discount;
            if (final < 0) final = 0;
            document.getElementById('final-total').innerText = new Intl.NumberFormat('vi-VN').format(final) + 'đ';
        }

        function toggleBankInfo(show) { document.getElementById('bank-info').classList.toggle('hidden', !show); }
        
        const modal = document.getElementById('address-modal');
        const viewList = document.getElementById('view-list');
        const viewForm = document.getElementById('view-form');
        const listContainer = document.getElementById('address-list-container');

        function openAddressModal() { modal.classList.remove('hidden'); showList(); loadAddresses(); }
        function closeAddressModal() { modal.classList.add('hidden'); }
        function showList() { viewList.classList.remove('-translate-x-full'); viewForm.classList.add('translate-x-full'); }
        
        function showAddForm() { 
            document.getElementById('form-title').innerText = 'THÊM ĐỊA CHỈ MỚI'; document.getElementById('edit_id').value = '0';
            document.getElementById('input_name').value = ''; document.getElementById('input_phone').value = ''; document.getElementById('input_address').value = '';
            viewList.classList.add('-translate-x-full'); viewForm.classList.remove('translate-x-full'); 
        }
        function showEditForm(id, name, phone, addr) {
            document.getElementById('form-title').innerText = 'CẬP NHẬT ĐỊA CHỈ'; document.getElementById('edit_id').value = id;
            document.getElementById('input_name').value = name; document.getElementById('input_phone').value = phone; document.getElementById('input_address').value = addr;
            viewList.classList.add('-translate-x-full'); viewForm.classList.remove('translate-x-full');
        }

        async function loadAddresses() {
            try {
                listContainer.innerHTML = '<p class="text-center text-gray-400 py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải...</p>';
                const res = await fetch('../Config/get_addresses.php');
                const data = await res.json();
                if (data.length === 0) { listContainer.innerHTML = '<div class="text-center text-gray-400 italic py-10"><p>Chưa có địa chỉ nào</p></div>'; return; }
                listContainer.innerHTML = data.map(addr => `
                    <div class="group p-4 border border-gray-200 rounded-xl hover:border-blue-400 hover:bg-blue-50/30 transition relative bg-white shadow-sm">
                        <div class="cursor-pointer" onclick="selectAddress('${addr.recipient_name}', '${addr.phone}', '${addr.address}')">
                            <div class="flex items-center justify-between mb-1">
                                <p class="font-bold text-gray-800 text-sm flex items-center gap-2"><i class="fas fa-user-circle text-gray-400"></i> ${addr.recipient_name}</p>
                                ${addr.is_default == 1 ? '<span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold">Mặc định</span>' : ''}
                            </div>
                            <p class="text-xs text-gray-500 mb-1"><i class="fas fa-phone-alt mr-1"></i> ${addr.phone}</p>
                            <p class="text-xs text-gray-600 line-clamp-2">${addr.address}</p>
                        </div>
                        <div class="absolute top-3 right-3 flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onclick="showEditForm(${addr.id}, '${addr.recipient_name}', '${addr.phone}', '${addr.address}')" class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-blue-500 hover:text-white rounded-full text-gray-500 text-xs transition"><i class="fas fa-pen"></i></button>
                            <button onclick="deleteAddress(${addr.id})" class="w-7 h-7 flex items-center justify-center bg-gray-100 hover:bg-red-500 hover:text-white rounded-full text-gray-500 text-xs transition"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>`).join('');
            } catch (e) { console.error(e); }
        }

        async function saveAddress() {
            const id = document.getElementById('edit_id').value, name = document.getElementById('input_name').value.trim(),
                  phone = document.getElementById('input_phone').value.trim(), address = document.getElementById('input_address').value.trim();
            if (!name || !phone || !address) return Swal.fire('Thiếu thông tin', '', 'warning');
            const formData = new FormData(); formData.append('id', id); formData.append('name', name); formData.append('phone', phone); formData.append('address', address);
            const apiUrl = (id === '0') ? '../Config/add_address.php' : '../Config/edit_address.php';
            try {
                const res = await fetch(apiUrl, { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) { selectAddress(name, phone, address); loadAddresses(); showList(); } 
                else Swal.fire('Lỗi', data.message, 'error');
            } catch (e) { console.error(e); }
        }

        async function deleteAddress(id) {
            if (await Swal.fire({title: 'Xóa địa chỉ?', icon: 'warning', showCancelButton: true}).then(r => r.isConfirmed)) {
                const formData = new FormData(); formData.append('id', id);
                const res = await fetch('../Config/delete_address.php', { method: 'POST', body: formData });
                if ((await res.json()).success) loadAddresses();
            }
        }

        async function calculateShipping(address) {
            const shippingEl = document.getElementById('shipping-fee'), finalEl = document.getElementById('final-total'), inputShip = document.getElementById('input_shipping_fee'), subTotal = parseInt(document.getElementById('sub-total').dataset.value);
            shippingEl.innerText = 'Đang tính...';
            try {
                const res = await fetch(`../Config/get_shipping_fee.php?address=${encodeURIComponent(address)}`);
                const data = await res.json();
                const fee = data.success ? data.fee : 30000;
                shippingEl.innerText = new Intl.NumberFormat('vi-VN').format(fee) + 'đ';
                shippingEl.className = 'text-gray-800 font-bold';
                inputShip.value = fee;
                let discountText = document.getElementById('discount-display').innerText.replace(/[^\d]/g, '');
                let discount = parseInt(discountText) || 0;
                const finalTotal = Math.max(0, subTotal + fee - discount);
                finalEl.innerText = new Intl.NumberFormat('vi-VN').format(finalTotal) + 'đ';
            } catch (e) { shippingEl.innerText = '30.000đ'; }
        }

        function selectAddress(name, phone, addr) {
            document.getElementById('r_name').value = name; document.getElementById('r_phone').value = phone; document.getElementById('r_address').value = addr;
            closeAddressModal(); calculateShipping(addr);
        }
        
        function openVoucherModal() { document.getElementById('voucher-modal').classList.remove('hidden'); }
        function closeVoucherModal() { document.getElementById('voucher-modal').classList.add('hidden'); }
        function selectVoucher(code) { document.getElementById('voucher_code_input').value = code; closeVoucherModal(); checkVoucher(); }

        document.addEventListener('DOMContentLoaded', () => { const addr = document.getElementById('r_address').value; if(addr) calculateShipping(addr); });
    </script>

    <?php include '../Compoment/Footer.php'; ?>
</body>
</html>