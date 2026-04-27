<?php
// File: Web php/Admin_Screen/Admin_Orders.php
session_start();
include '../Config/Database.php';

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    header("Location: ../User_Screen/Home_Screen.php");
    exit;
}

$db = Database::getInstance()->getConnection();

// --- 1. TÌM KIẾM & PHÂN TRANG ---
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$searchTerm = "%$search%";

// Đếm tổng (Join bảng users để tìm theo username)
$countSql = "SELECT COUNT(*) as total 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.id LIKE ? OR u.username LIKE ? OR o.receiver_name LIKE ?";
$stmtC = $db->prepare($countSql);
$stmtC->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmtC->execute();
$totalRows = $stmtC->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$stmtC->close();

// Lấy dữ liệu
$sql = "SELECT o.*, u.username 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id LIKE ? OR u.username LIKE ? OR o.receiver_name LIKE ?
        ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("sssii", $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result();

$status_options = ['Đang chờ xác nhận', 'Đang vận chuyển', 'Đã giao hàng', 'Đã hủy'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quản Lý Đơn Hàng</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <?php include '../Compoment/Admin_Menu.php'; ?>

    <div class="md:ml-64 p-8 transition-all duration-300">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Đơn Hàng 📄</h1>

        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <form method="GET" class="flex gap-2 w-full md:w-1/2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Tìm mã đơn, tên người nhận..." 
                       class="w-full border p-2 rounded-lg text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-search"></i></button>
                <?php if($search): ?><a href="Admin_Orders.php" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center">Hủy</a><?php endif; ?>
            </form>
            <div class="text-sm text-gray-500">Hiển thị <b><?= $orders->num_rows ?></b> / <b><?= $totalRows ?></b> đơn</div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-200 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="p-4">Mã Đơn</th>
                        <th class="p-4">Khách Hàng</th>
                        <th class="p-4">Tổng Tiền</th>
                        <th class="p-4">Ngày Đặt</th>
                        <th class="p-4">Trạng Thái</th>
                        <th class="p-4">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if($orders->num_rows > 0): while($order = $orders->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-600">#<?= $order['id'] ?></td>
                        <td class="p-4 font-medium">
                            <?= htmlspecialchars($order['receiver_name']) ?>
                            <div class="text-xs text-gray-400">User: <?= htmlspecialchars($order['username']) ?></div>
                        </td>
                        <td class="p-4 font-bold text-red-600"><?= number_format($order['total_amount']) ?>đ</td>
                        <td class="p-4 text-sm text-gray-500"><?= date('d/m/Y H:i', strtotime($order['order_date'])) ?></td>
                        
                        <td class="p-4">
                            <?php 
                                $borderClass = 'border-gray-300';
                                if($order['status'] == 'Đã giao hàng') $borderClass = 'border-green-500 bg-green-50 text-green-700';
                                elseif($order['status'] == 'Đã hủy') $borderClass = 'border-red-500 bg-red-50 text-red-700';
                                elseif($order['status'] == 'Đang vận chuyển') $borderClass = 'border-blue-500 bg-blue-50 text-blue-700';
                                elseif($order['status'] == 'Đang chờ xác nhận') $borderClass = 'border-yellow-500 bg-yellow-50 text-yellow-700';
                            ?>
                            <select onchange="updateStatus(<?= $order['id'] ?>, this.value)" 
                                    class="p-2 rounded border-2 text-xs font-bold cursor-pointer outline-none w-full <?= $borderClass ?>">
                                <?php foreach($status_options as $st): ?>
                                    <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td class="p-4 flex gap-2">
                            <a href="../User_Screen/ViewOrder_Screen.php?order_id=<?= $order['id'] ?>" class="bg-blue-100 text-blue-600 p-2 rounded hover:bg-blue-200"><i class="fas fa-eye"></i></a>
                            <?php if($role === 'admin'): ?>
                            <button onclick="deleteOrder(<?= $order['id'] ?>)" class="bg-red-100 text-red-600 p-2 rounded hover:bg-red-200"><i class="fas fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='6' class='p-8 text-center text-gray-500'>Không tìm thấy đơn hàng nào.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="flex justify-center mt-6 gap-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow hover:bg-gray-100"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded shadow font-bold text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow hover:bg-gray-100"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        async function updateStatus(orderId, newStatus) {
            document.body.style.cursor = 'wait';
            try {
                const fd = new FormData(); fd.append('order_id', orderId); fd.append('status', newStatus);
                const res = await fetch('../Config/update_order_status.php', { method: 'POST', body: fd });
                const data = await res.json();
                document.body.style.cursor = 'default';
                if (data.success) {
                    Swal.fire({icon: 'success', title: 'Cập nhật thành công', toast: true, position: 'top-end', showConfirmButton: false, timer: 1500}).then(()=>location.reload());
                } else Swal.fire('Lỗi', data.message, 'error');
            } catch (e) { Swal.fire('Lỗi mạng', '', 'error'); }
        }

        async function deleteOrder(id) {
            if(await Swal.fire({title:'Xóa đơn này?', text:"Không thể hoàn tác!", icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'}).then(r=>r.isConfirmed)) {
                const res = await fetch(`../Config/delete_order.php?id=${id}`);
                const data = await res.json();
                data.success ? location.reload() : Swal.fire('Lỗi', data.message, 'error');
            }
        }
    </script>
</body>
</html>
