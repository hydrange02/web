<?php
// File: Web php/Admin_Screen/Admin_Reviews.php
session_start();
include '../Config/Database.php';

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) { header("Location: ../User_Screen/Home_Screen.php"); exit; }

$db = Database::getInstance()->getConnection();

$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$searchTerm = "%$search%";

// Đếm tổng
$countSql = "SELECT COUNT(*) as total FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             JOIN items i ON r.item_id = i.id 
             WHERE u.username LIKE ? OR i.name LIKE ?";
$stmtC = $db->prepare($countSql);
$stmtC->bind_param("ss", $searchTerm, $searchTerm);
$stmtC->execute();
$totalRows = $stmtC->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$stmtC->close();

// Lấy dữ liệu
$sql = "SELECT r.*, u.username, i.name as item_name, i.image 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        JOIN items i ON r.item_id = i.id 
        WHERE u.username LIKE ? OR i.name LIKE ? 
        ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quản Lý Đánh Giá</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <?php include '../Compoment/Admin_Menu.php'; ?>

    <div class="ml-64 p-8 transition-all duration-300">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Đánh Giá 💬</h1>

        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <form method="GET" class="flex gap-2 w-full md:w-1/2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm tên khách, tên sản phẩm..." class="w-full border p-2 rounded-lg text-sm outline-none focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-search"></i></button>
                <?php if($search): ?><a href="Admin_Reviews.php" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center">Hủy</a><?php endif; ?>
            </form>
            <div class="text-sm text-gray-500">Hiển thị <b><?= $reviews->num_rows ?></b> / <b><?= $totalRows ?></b></div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-200 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Sản Phẩm</th>
                        <th class="p-4">Khách Hàng</th>
                        <th class="p-4">Sao</th>
                        <th class="p-4 w-1/3">Nội Dung</th>
                        <th class="p-4">Ngày Đăng</th>
                        <th class="p-4 text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if($reviews->num_rows > 0): while($row = $reviews->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-500">#<?= $row['id'] ?></td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <img src="../<?= htmlspecialchars($row['image']) ?>" class="w-10 h-10 object-contain rounded border bg-white">
                                <span class="text-sm font-semibold line-clamp-1 max-w-[150px]" title="<?= htmlspecialchars($row['item_name']) ?>"><?= htmlspecialchars($row['item_name']) ?></span>
                            </div>
                        </td>
                        <td class="p-4 font-medium text-blue-600"><?= htmlspecialchars($row['username']) ?></td>
                        <td class="p-4 text-yellow-400 text-xs"><?php for($i=1; $i<=5; $i++) echo $i <= $row['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?></td>
                        <td class="p-4 text-gray-700 text-sm italic">"<?= htmlspecialchars($row['comment']) ?>"</td>
                        <td class="p-4 text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                        <td class="p-4 text-center">
                            <button onclick="deleteReview(<?= $row['id'] ?>)" class="text-red-500 hover:bg-red-50 p-2 rounded-full transition"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='7' class='p-8 text-center text-gray-500'>Chưa có đánh giá nào.</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="flex justify-center mt-6 gap-2">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded shadow font-bold text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        async function deleteReview(id) {
            if(await Swal.fire({title: 'Xóa đánh giá?', text: "Vĩnh viễn!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33'}).then(r=>r.isConfirmed)) {
                const res = await fetch(`../Config/delete_review.php?id=${id}`);
                const data = await res.json();
                data.success ? location.reload() : Swal.fire('Lỗi', data.message, 'error');
            }
        }
    </script>
</body>
</html>