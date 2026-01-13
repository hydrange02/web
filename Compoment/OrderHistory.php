<?php
// File: Web php/Compoment/OrderHistory.php
if (!$conn || !$user_id) { return; }

// --- TỰ ĐỘNG ẨN LỊCH SỬ CŨ ---
$auto_delete_date = date('Y-m-d', strtotime('-1 year'));
$conn->query("UPDATE orders SET is_hidden = 1 WHERE order_date < '$auto_delete_date' AND user_id = $user_id");

// --- LẤY DANH SÁCH ---
$sql = "SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? AND is_hidden = 0 ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mã Đơn</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày Đặt</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tổng Tiền</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Trạng Thái</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Thao Tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($order = $result->fetch_assoc()): 
                    $status_class = strtolower(str_replace(' ', '-', $order['status']));
                    if($order['status'] == 'Đang chờ xác nhận') $status_class = 'pending';
                    if($order['status'] == 'Đã hủy') $status_class = 'canceled';
                ?>
                <tr id="order-row-<?= $order['id'] ?>" class="group hover:bg-gray-50 transition duration-200">
                    <td class="px-6 py-4 text-sm font-bold text-gray-700">#<?= $order['id'] ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-red-600"><?= number_format($order['total_amount']) ?>₫</td>
                    
                    <td class="px-6 py-4 text-center">
                        <span id="status-badge-<?= $order['id'] ?>" class="px-3 py-1 text-xs font-bold rounded-full status-badge status-<?= $status_class ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>

                    <td id="action-cell-<?= $order['id'] ?>" class="px-6 py-4 text-center text-sm font-medium flex justify-center gap-3 items-center">
                        <a href="ViewOrder_Screen.php?order_id=<?= $order['id'] ?>" 
                           class="text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1 rounded-md transition">
                           <i class="fas fa-eye mr-1"></i> Xem
                        </a>
                        
                        <?php if($order['status'] === 'Đang chờ xác nhận'): ?>
                            <button onclick="cancelOrder(<?= $order['id'] ?>)" 
                                    class="text-red-500 hover:text-red-700 bg-red-50 px-3 py-1 rounded-md transition font-bold shadow-sm">
                                <i class="fas fa-times mr-1"></i> Hủy
                            </button>
                        <?php endif; ?>

                        <?php if($order['status'] === 'Đã giao hàng' || $order['status'] === 'Đã hủy'): ?>
                            <button onclick="hideOrder(<?= $order['id'] ?>)" 
                                    class="text-gray-400 hover:text-red-500 transition px-2" title="Xóa khỏi lịch sử">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    // --- V2: HỦY ĐƠN HÀNG MƯỢT MÀ ---
    async function cancelOrder(orderId) {
        const result = await Swal.fire({
            title: 'Xác nhận hủy?',
            text: "Bạn có chắc chắn muốn hủy đơn hàng #" + orderId + " không?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Đồng ý hủy',
            cancelButtonText: 'Không'
        });

        if (result.isConfirmed) {
            // Hiển thị loading
            Swal.fire({ title: 'Đang xử lý...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                const res = await fetch('../Config/user_cancel_order.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Thành công!', text: 'Đơn hàng đã được hủy.', timer: 1500, showConfirmButton: false });

                    // --- CẬP NHẬT GIAO DIỆN KHÔNG RELOAD (DOM MANIPULATION) ---
                    
                    // 1. Đổi màu badge trạng thái
                    const badge = document.getElementById(`status-badge-${orderId}`);
                    if(badge) {
                        badge.className = "px-3 py-1 text-xs font-bold rounded-full status-badge status-canceled";
                        badge.innerText = "Đã hủy";
                    }

                    // 2. Thay nút "Hủy" bằng nút "Xóa lịch sử" (Thùng rác)
                    const actionCell = document.getElementById(`action-cell-${orderId}`);
                    if(actionCell) {
                        // Giữ lại nút Xem, thêm nút Xóa
                        actionCell.innerHTML = `
                            <a href="ViewOrder_Screen.php?order_id=${orderId}" class="text-blue-600 hover:text-blue-800 bg-blue-50 px-3 py-1 rounded-md transition"><i class="fas fa-eye mr-1"></i> Xem</a>
                            <button onclick="hideOrder(${orderId})" class="text-gray-400 hover:text-red-500 transition px-2" title="Xóa khỏi lịch sử">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        `;
                    }

                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Lỗi mạng', 'Không thể kết nối đến server.', 'error');
            }
        }
    }

    // --- V2: XÓA (ẨN) LỊCH SỬ ---
    async function hideOrder(orderId) {
        const result = await Swal.fire({
            title: 'Xóa lịch sử?',
            text: "Đơn hàng này sẽ bị ẩn khỏi danh sách của bạn.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Xóa bỏ',
            cancelButtonText: 'Giữ lại'
        });

        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('order_id', orderId);
                const res = await fetch('../Config/hide_order.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    // Xóa dòng đó khỏi bảng với hiệu ứng fade out
                    const row = document.getElementById(`order-row-${orderId}`);
                    row.style.transition = "all 0.5s ease";
                    row.style.opacity = "0";
                    row.style.transform = "translateX(50px)";
                    setTimeout(() => row.remove(), 500);
                    
                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                    Toast.fire({ icon: 'success', title: 'Đã xóa khỏi lịch sử' });
                } else {
                    Swal.fire('Lỗi', 'Không thể xóa.', 'error');
                }
            } catch(e) { console.error(e); }
        }
    }
    </script>
<?php } else { 
    echo "<div class='flex flex-col items-center justify-center p-10 text-gray-400'>
            <i class='fas fa-box-open text-6xl mb-4 text-gray-300'></i>
            <p class='text-lg italic'>Bạn chưa có đơn hàng nào.</p>
            <a href='../index.php' class='mt-4 px-6 py-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition shadow'>Mua sắm ngay</a>
          </div>"; 
} 
$stmt->close(); 
?>