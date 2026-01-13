<?php
// File: Web php/Admin_Screen/Admin_Users.php
session_start();
include '../Config/Database.php';

$current_role = $_SESSION['role'] ?? 'user';
if (!in_array($current_role, ['admin', 'manager'])) { header("Location: ../User_Screen/Home_Screen.php"); exit; }

$db = Database::getInstance()->getConnection();
$my_id = $_SESSION['user_id'];

// TÌM KIẾM & PHÂN TRANG
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$searchTerm = "%$search%";

$countSql = "SELECT COUNT(*) as total FROM users WHERE id != ? AND (username LIKE ? OR email LIKE ?)";
$stmtC = $db->prepare($countSql);
$stmtC->bind_param("iss", $my_id, $searchTerm, $searchTerm);
$stmtC->execute();
$totalRows = $stmtC->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$stmtC->close();

$sql = "SELECT * FROM users WHERE id != ? AND (username LIKE ? OR email LIKE ?) ORDER BY role ASC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("issii", $my_id, $searchTerm, $searchTerm, $limit, $offset);
$stmt->execute();
$users = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quản Lý Nhân Sự</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen font-sans">
    <?php include '../Compoment/Admin_Menu.php'; ?>

    <div class="ml-64 p-8 transition-all duration-300">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Quản Lý Nhân Sự 👥</h1>

        <?php if($current_role === 'manager'): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Chế độ xem (View Only)</p>
                <p class="text-sm">Bạn là Manager. Bạn chỉ có thể xem danh sách.</p>
            </div>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <form method="GET" class="flex gap-2 w-full md:w-1/2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm username, email..." class="w-full border p-2 rounded-lg text-sm outline-none focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-search"></i></button>
                <?php if($search): ?><a href="Admin_Users.php" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center">Hủy</a><?php endif; ?>
            </form>
            <div class="text-sm text-gray-500"><b><?= $users->num_rows ?></b> / <b><?= $totalRows ?></b> users</div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-200 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="p-4">ID</th>
                        <th class="p-4">Username</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">Vai Trò</th>
                        <th class="p-4">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if($users->num_rows > 0): while($u = $users->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 text-gray-500">#<?= $u['id'] ?></td>
                        <td class="p-4 font-bold"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="p-4"><?= htmlspecialchars($u['email']) ?></td>
                        <td class="p-4">
                            <?php 
                                $color = 'bg-gray-200 text-gray-700';
                                if($u['role'] == 'manager') $color = 'bg-purple-100 text-purple-700';
                                if($u['role'] == 'admin') $color = 'bg-red-100 text-red-700 border border-red-200';
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?= $color ?>"><?= ucfirst($u['role']) ?></span>
                        </td>
                        <td class="p-4">
                            <?php if ($current_role === 'admin'): ?>
                                <div class="flex gap-2">
                                    <?php if ($u['role'] !== 'user'): ?><button onclick="updateRole(<?= $u['id'] ?>, 'user')" class="bg-gray-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-gray-600">⬇️ User</button><?php endif; ?>
                                    <?php if ($u['role'] !== 'manager'): ?><button onclick="updateRole(<?= $u['id'] ?>, 'manager')" class="bg-purple-500 text-white px-2 py-1 rounded text-xs font-bold hover:bg-purple-600">Manager</button><?php endif; ?>
                                    <?php if ($u['role'] !== 'admin'): ?><button onclick="updateRole(<?= $u['id'] ?>, 'admin')" class="bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold hover:bg-blue-700">Admin</button><?php endif; ?>
                                </div>
                                <button onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="mt-2 w-full bg-red-50 text-red-600 border border-red-200 px-2 py-1 rounded text-xs font-bold hover:bg-red-100"><i class="fas fa-trash"></i> Xóa</button>
                            <?php else: ?>
                                <span class="text-gray-400 italic text-xs"><i class="fas fa-lock"></i> View Only</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='5' class='p-8 text-center text-gray-500'>Không tìm thấy user nào.</td></tr>"; endif; ?>
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
        async function updateRole(uid, role) {
            if(!await Swal.fire({title: 'Xác nhận?', text: `Set quyền thành ${role}?`, icon: 'question', showCancelButton: true}).then(r=>r.isConfirmed)) return;
            try {
                const res = await fetch('../Config/update_role.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `user_id=${uid}&role=${role}` });
                const data = await res.json();
                data.success ? Swal.fire('Xong', data.message, 'success').then(()=>location.reload()) : Swal.fire('Lỗi', data.message, 'error');
            } catch(e) { Swal.fire('Lỗi', '', 'error'); }
        }
        async function deleteUser(uid, name) {
            if(!await Swal.fire({title: 'Xóa user?', text: `Xóa vĩnh viễn ${name}?`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33'}).then(r=>r.isConfirmed)) return;
            try {
                const res = await fetch('../Config/delete_user.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: `user_id=${uid}` });
                const data = await res.json();
                data.success ? Swal.fire('Đã xóa', '', 'success').then(()=>location.reload()) : Swal.fire('Lỗi', data.message, 'error');
            } catch(e) { Swal.fire('Lỗi', '', 'error'); }
        }
    </script>
</body>
</html>