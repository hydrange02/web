<?php
// File: Web php/Compoment/Cart.php
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    // Lấy dữ liệu giỏ hàng
    $sql = "SELECT c.*, i.name, i.image, i.price as unit_price 
            FROM cart c
            JOIN items i ON c.item_id = i.id
            WHERE c.user_id = ? ORDER BY c.id DESC";

    $stml = $conn->prepare($sql);
    $stml->bind_param('i', $user_id);
    $stml->execute();
    $result = $stml->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);

    if (count($cart_items) > 0) {
        foreach ($cart_items as $row):
?>
            <div class="group bg-white rounded-xl shadow-sm border border-gray-100 mb-3 grid grid-cols-1 md:grid-cols-12 gap-4 p-4 items-center hover:shadow-md hover:border-blue-200 transition-all duration-300">
                <div class="col-span-1 md:col-span-5 flex items-center gap-4">
                    <input type="checkbox"
                        id="cb-<?= $row['id'] ?>"
                        data-price="<?= $row['unit_price'] ?>"
                        data-qty="<?= $row['quantity'] ?>"
                        data-total="<?= $row['total'] ?>"
                        value="<?= $row['id'] ?>"
                        class="item-checkbox w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer transition">
                    <div class="w-20 h-20 flex-shrink-0 bg-gray-50 rounded-lg border border-gray-200 overflow-hidden p-1">
                        <img src="../<?= htmlspecialchars($row['image']) ?>" class="w-full h-full object-contain mix-blend-multiply">
                    </div>
                    <div class="flex flex-col">
                        <a href="../User_Screen/Detail.php?id=<?= $row['item_id'] ?>" class="text-gray-800 font-semibold text-sm line-clamp-2 hover:text-blue-600 transition"><?= htmlspecialchars($row['name']) ?></a>
                        <span class="text-xs text-gray-400 mt-1">Mã SP: #<?= $row['item_id'] ?></span>
                    </div>
                </div>
                <div class="col-span-1 md:col-span-2 text-center">
                    <span class="text-gray-500 text-sm font-medium hidden md:block">Đơn giá</span>
                    <span class="text-gray-700 font-medium"><?= number_format($row['unit_price']) ?>₫</span>
                </div>
                <div class="col-span-1 md:col-span-2 flex justify-center">
                    <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden h-9 shadow-sm">
                        <button onclick="updateCartQty(<?= $row['id'] ?>, 'decrease')" class="w-8 h-full bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold">-</button>
                        <input type="text" id="qty-<?= $row['id'] ?>" value="<?= $row['quantity'] ?>" readonly class="w-10 h-full text-center text-sm font-bold bg-white outline-none">
                        <button onclick="updateCartQty(<?= $row['id'] ?>, 'increase')" class="w-8 h-full bg-gray-50 hover:bg-gray-200 text-gray-600 font-bold">+</button>
                    </div>
                </div>
                <div class="col-span-1 md:col-span-2 text-center">
                    <span class="text-gray-500 text-sm font-medium hidden md:block">Thành tiền</span>
                    <span id="total-<?= $row['id'] ?>" class="font-bold text-red-600 text-lg"><?= number_format($row['total']) ?>₫</span>
                </div>
                <div class="col-span-1 md:col-span-1 text-center flex justify-center">
                    <a href="javascript:void(0)"
                        onclick="confirmDelete(<?= $row['id'] ?>)"
                        class="w-9 h-9 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center hover:bg-red-100 hover:text-red-500 transition shadow-sm"
                        title="Xóa sản phẩm">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </a>
                </div>
            </div>
        <?php
        endforeach;
        $stml->close();
        ?>
        <div class="sticky bottom-4 z-20 mt-6">
            <div class="bg-white/95 backdrop-blur-sm p-4 md:p-6 rounded-2xl shadow-[0_0_20px_rgba(0,0,0,0.1)] border border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="hidden md:flex items-center gap-2 text-gray-500 text-sm">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <span>Chọn sản phẩm để thanh toán</span>
                </div>
                <div class="flex flex-col md:flex-row items-center gap-6 w-full md:w-auto">
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Tổng thanh toán (<span id="selected-count" class="font-bold text-gray-800">0</span> sản phẩm):</p>
                        <p class="text-2xl font-extrabold text-red-600 leading-none mt-1" id="grand-total">0₫</p>
                    </div>
                    <button id="checkout-btn" disabled class="w-full md:w-64 bg-gradient-to-r from-orange-500 to-red-500 text-white py-3.5 rounded-xl font-bold shadow-lg hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 flex items-center justify-center gap-2">
                        <span>MUA HÀNG</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="flex flex-col items-center justify-center py-16 bg-white rounded-2xl shadow-sm border border-gray-100 text-center">
            <div class="w-40 h-40 bg-gray-50 rounded-full flex items-center justify-center mb-6">
                <i class="fas fa-shopping-basket text-6xl text-gray-300"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Giỏ hàng trống trơn!</h2>
            <a href="Home_Screen.php" class="px-8 py-3 bg-blue-600 text-white rounded-full font-bold shadow-lg hover:bg-blue-700 hover:shadow-xl transition transform hover:-translate-y-1">Tiếp tục mua sắm</a>
        </div>
<?php }
} ?>

<script>
    // 1. Update Qty AJAX
    async function updateCartQty(cartId, action) {
        const qtyInput = document.getElementById(`qty-${cartId}`);
        const currentQty = parseInt(qtyInput.value);
        if (action === 'decrease' && currentQty <= 1) return;

        try {
            qtyInput.style.opacity = '0.5';
            const formData = new FormData();
            formData.append('cart_id', cartId);
            formData.append('action', action);

            const res = await fetch('../Config/UpdateCart.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                qtyInput.value = data.new_qty;
                qtyInput.style.opacity = '1';
                document.getElementById(`total-${cartId}`).textContent = new Intl.NumberFormat('vi-VN').format(data.new_total) + '₫';
                const checkbox = document.getElementById(`cb-${cartId}`);
                checkbox.dataset.qty = data.new_qty;
                checkbox.dataset.total = data.new_total;
                if (checkbox.checked) calculateTotal();
            } else {
                alert(data.message);
                qtyInput.style.opacity = '1';
            }
        } catch (error) {
            console.error(error);
            alert('Lỗi kết nối server');
        }
    }

    // 2. Logic Tính Tổng & Chọn Tất Cả
    const selectAllCb = document.getElementById('select-all'); // Nút chọn tất cả
    const checkboxes = document.querySelectorAll('.item-checkbox'); // Các nút con
    const totalSpan = document.getElementById('grand-total');
    const countSpan = document.getElementById('selected-count');
    const btnCheckout = document.getElementById('checkout-btn');

    // Hàm tính tổng tiền
    function calculateTotal() {
        let total = 0;
        let count = 0;
        let selectedIds = [];

        // Kiểm tra xem tất cả có đang được chọn không để update nút Select All
        let allChecked = true;

        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseFloat(cb.dataset.total);
                count++;
                selectedIds.push(cb.value);
            } else {
                allChecked = false;
            }
        });

        // Cập nhật trạng thái nút "Chọn tất cả" dựa trên các nút con
        if (selectAllCb) {
            selectAllCb.checked = (checkboxes.length > 0 && allChecked);
        }

        totalSpan.textContent = new Intl.NumberFormat('vi-VN').format(total) + '₫';
        countSpan.textContent = count;
        btnCheckout.disabled = count === 0;
        btnCheckout.dataset.ids = selectedIds.join(',');
    }

    // Sự kiện cho nút "Chọn tất cả"
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function() {
            const isChecked = this.checked;
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            calculateTotal();
        });
    }

    // Sự kiện cho từng checkbox con
    checkboxes.forEach(cb => cb.addEventListener('change', calculateTotal));

    // 3. CHECKOUT (POST Form)
    btnCheckout.addEventListener('click', () => {
        const ids = btnCheckout.dataset.ids;
        if (ids) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../User_Screen/Checkout_Screen.php';

            const inputIds = document.createElement('input');
            inputIds.type = 'hidden';
            inputIds.name = 'cart_ids';
            inputIds.value = ids;

            form.appendChild(inputIds);
            document.body.appendChild(form);
            form.submit();
        }
    });
    // Thêm hàm này vào trong thẻ <script> ở cuối file Cart.php
    function confirmDelete(cartId) {
        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Sản phẩm này sẽ bị xóa khỏi giỏ hàng!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Đúng, xóa nó!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                // Chuyển hướng đến file xử lý xóa
                window.location.href = `../Config/DeleteCart.php?id=${cartId}`;
            }
        })
    }
    function confirmDelete(id) {
        Swal.fire({
            title: 'Bạn chắc chắn muốn xóa?',
            text: "Hành động này không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Xóa ngay',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                // Nếu người dùng đồng ý, mới chuyển hướng sang trang xử lý xóa
                window.location.href = `../Config/DeleteCart.php?id=${id}`;
            }
        })
    }
</script>