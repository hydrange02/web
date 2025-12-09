<?php
// File: Web php/User_Screen/ViewOrder_Screen.php
session_start();
include '../Config/Database.php';
include '../Compoment/Menu.php';

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user'; // Lấy thêm Role
$order_id = $_GET['order_id'] ?? null;

if (!$user_id || !$order_id) {
    echo "<script>window.location.href = '../index.php';</script>";
    exit;
}

$db = Database::getInstance()->getConnection();

// --- SỬA LOGIC KIỂM TRA QUYỀN ---
if ($role === 'admin' || $role === 'manager') {
    // Nếu là Admin/Manager: Cho phép xem mọi đơn hàng theo ID
    $sql_order = "SELECT * FROM orders WHERE id = ?";
    $stmt = $db->prepare($sql_order);
    $stmt->bind_param("i", $order_id);
} else {
    // Nếu là User thường: Chỉ xem đơn của chính mình
    $sql_order = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql_order);
    $stmt->bind_param("ii", $order_id, $user_id);
}
// ---------------------------------

$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<div class=' p-10 text-center text-2xl font-bold text-red-600'>
            Đơn hàng không tồn tại hoặc bạn không có quyền xem!
          </div>";
    exit;
}

$status_class = strtolower(str_replace(' ', '-', $order['status']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chi Tiết Đơn Hàng #<?= htmlspecialchars($order_id) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-processing { background-color: #fca5a5; color: #b91c1c; }
        .status-delivered { background-color: #a7f3d0; color: #047857; }
        .status-canceled { background-color: #d1d5db; color: #4b5563; }
        .status-shipped { background-color: #bfdbfe; color: #1d4ed8; }
        .status-pending { background-color: #fef08a; color: #854d0e; } /* Màu vàng cho trạng thái chờ */
    </style>
</head>

<body class="bg-gray-100">
    <div class="p-10 max-w-6xl mx-auto">

        <div class="mb-6">
            <?php if($role === 'admin' || $role === 'manager'): ?>
                <a href="../Admin_Screen/Admin_Orders.php" class="flex items-center text-gray-600 hover:text-blue-600 font-medium transition w-fit">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại Quản lý đơn hàng
                </a>
            <?php else: ?>
                <a href="History_Screen.php" class="flex items-center text-gray-600 hover:text-blue-600 font-medium transition w-fit">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại Lịch sử mua hàng
                </a>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-xl shadow-2xl p-8">
            <div class="flex justify-between items-start border-b pb-4 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800">Chi Tiết Đơn Hàng #<?= htmlspecialchars($order['id']) ?></h1>
                    <p class="text-gray-500 mt-1">Ngày đặt: <b><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></b></p>
                </div>
                
                <div class="flex flex-col items-end gap-2">
                    <span class="px-4 py-2 inline-flex text-base leading-5 font-bold rounded-full status-<?= $status_class ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>

                    <?php if ($role === 'user' && $order['status'] === 'Đang chờ xác nhận'): ?>
                        <button onclick="cancelOrderFromDetail(<?= $order['id'] ?>)" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition shadow text-sm font-bold">
                            <i class="fas fa-times-circle mr-1"></i> Hủy Đơn Hàng
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <h2 class="text-xl font-bold mb-4 text-gray-700"><i class="fas fa-info-circle"></i> Thông tin nhận hàng:</h2>
            <div class="grid grid-cols-2 gap-4 mb-6 text-sm text-gray-600 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <p class="mb-2"><span class="font-bold text-gray-800">Người nhận:</span> <?= htmlspecialchars($order['receiver_name']) ?></p>
                    <p><span class="font-bold text-gray-800">SĐT:</span> <?= htmlspecialchars($order['receiver_phone']) ?></p>
                </div>
                <div>
                    <p class="mb-2"><span class="font-bold text-gray-800">Địa chỉ:</span> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><span class="font-bold text-gray-800">Thanh toán:</span> <?= htmlspecialchars($order['payment_method']) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto border rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">SẢN PHẨM</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">SỐ LƯỢNG</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">ĐƠN GIÁ</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">THÀNH TIỀN</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // Lấy chi tiết sản phẩm
                        $sql_details = "SELECT od.*, i.name, i.image 
                                        FROM order_details od 
                                        JOIN items i ON od.item_id = i.id 
                                        WHERE od.order_id = ?";
                        $stmt_d = $db->prepare($sql_details);
                        $stmt_d->bind_param("i", $order_id);
                        $stmt_d->execute();
                        $result_details = $stmt_d->get_result();

                        while ($item = $result_details->fetch_assoc()):
                            $subtotal = $item['quantity'] * $item['price_at_purchase'];
                        ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <img src="../<?= htmlspecialchars($item['image']) ?>" class="w-12 h-12 rounded object-contain border bg-white mr-4">
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-gray-700">
                                    x<b><?= htmlspecialchars($item['quantity']) ?></b>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-500">
                                    <?= number_format($item['price_at_purchase']) ?>₫
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-800">
                                    <?= number_format($subtotal) ?>₫
                                </td>
                            </tr>
                        <?php endwhile; $stmt_d->close(); ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-right border border-blue-100">
                <p class="text-xl font-bold text-gray-800">
                    TỔNG THANH TOÁN: <span class="text-red-600 ml-3 text-2xl"><?= number_format($order['total_amount']) ?>₫</span>
                </p>
            </div>

        </div>
    </div>

    <script>
        async function cancelOrderFromDetail(id) {
            const result = await Swal.fire({
                title: 'Hủy đơn hàng?',
                text: "Bạn có chắc chắn muốn hủy đơn hàng này không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Đồng ý hủy',
                cancelButtonText: 'Quay lại'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('order_id', id);
                    const res = await fetch('../Config/user_cancel_order.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    
                    if (data.success) {
                        Swal.fire('Đã hủy!', 'Đơn hàng của bạn đã được hủy.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Lỗi', data.message, 'error');
                    }
                } catch(e) { Swal.fire('Lỗi', 'Không kết nối được server', 'error'); }
            }
        }
    </script>
</body>
</html>