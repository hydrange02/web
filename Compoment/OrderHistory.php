<?php
// File: Web php/Compoment/OrderHistory.php
if (!$conn || !$user_id) { return; }

// --- 1. TỰ ĐỘNG XÓA (ẨN) LỊCH SỬ CŨ HƠN 1 NĂM ---
// Bạn có thể chỉnh '1 year' thành '6 month' hoặc số ngày tùy ý
$auto_delete_date = date('Y-m-d', strtotime('-1 year'));
$conn->query("UPDATE orders SET is_hidden = 1 WHERE order_date < '$auto_delete_date' AND user_id = $user_id");

// --- 2. LẤY DANH SÁCH (CHỈ LẤY ĐƠN CHƯA BỊ ẨN) ---
$sql = "SELECT id, order_date, total_amount, status FROM orders WHERE user_id = ? AND is_hidden = 0 ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
?>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã Đơn</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày Đặt</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tổng Tiền</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng Thái</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao Tác</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php while ($order = $result->fetch_assoc()): 
                $status_class = strtolower(str_replace(' ', '-', $order['status']));
            ?>
            <tr id="order-row-<?= $order['id'] ?>" class="group hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">#<?= $order['id'] ?></td>
                <td class="px-6 py-4 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                <td class="px-6 py-4 text-sm font-semibold text-red-600"><?= number_format($order['total_amount']) ?>₫</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full status-<?= $status_class ?>">
                        <?= $order['status'] ?>
                    </span>
                </td>
                <td class="px-6 py-4 text-center text-sm font-medium flex justify-center gap-3">
                    <a href="ViewOrder_Screen.php?order_id=<?= $order['id'] ?>" class="text-blue-600 hover:text-blue-900">Xem</a>
                    
                    <?php if($order['status'] === 'Đang chờ xác nhận'): ?>
                        <button onclick="cancelOrder(<?= $order['id'] ?>)" class="text-red-500 hover:text-red-700 font-bold">Hủy</button>
                    <?php endif; ?>

                    <?php if($order['status'] === 'Đã giao hàng' || $order['status'] === 'Đã hủy'): ?>
                        <button onclick="hideOrder(<?= $order['id'] ?>)" class="text-gray-400 hover:text-gray-600" title="Xóa khỏi lịch sử">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
    // Hàm ẩn đơn hàng
    async function hideOrder(orderId) {
        if (!confirm('Bạn muốn xóa đơn hàng này khỏi lịch sử?')) return;
        try {
            const formData = new FormData();
            formData.append('order_id', orderId);
            const res = await fetch('../Config/hide_order.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                document.getElementById(`order-row-${orderId}`).remove();
                // Swal.fire('Đã xóa', '', 'success');
            } else alert('Lỗi');
        } catch(e) { console.error(e); }
    }

    async function cancelOrder(orderId) {
        if (!confirm('Hủy đơn hàng này?')) return;
        try {
            const formData = new FormData(); formData.append('order_id', orderId);
            const res = await fetch('../Config/user_cancel_order.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) location.reload(); else alert(data.message);
        } catch (e) { alert('Lỗi kết nối'); }
    }
    </script>
<?php } else { echo "<div class='text-center p-10 text-gray-400 italic'>Không có đơn hàng nào hiển thị.</div>"; } $stmt->close(); ?>