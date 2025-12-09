<?php
// File: Web php/Admin_Screen/Admin_Vouchers.php
session_start();
include '../Config/Database.php';

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) { header("Location: ../User_Screen/Home_Screen.php"); exit; }

$db = Database::getInstance()->getConnection();

// (Phần xử lý POST Save/Delete giữ nguyên như phiên bản trước, không đổi)
// ... (Chèn đoạn xử lý POST ở đây nếu cần, nhưng tôi sẽ tập trung vào phần hiển thị tìm kiếm)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Giữ nguyên logic Save/Delete của phiên bản trước bạn đã có) ...
    $action = $_POST['action'];
    if ($action === 'delete') {
        $id = $_POST['id'];
        $db->query("DELETE FROM vouchers WHERE id = $id");
    } elseif ($action === 'save') {
        // ... (Copy lại logic Save của bản trước) ...
        $code = strtoupper(trim($_POST['code']));
        $type = $_POST['discount_type'];
        $amount = $_POST['discount_amount']; 
        $max_disc = $_POST['max_discount']; 
        $points = $_POST['points_cost'];
        $qty = $_POST['qty'];
        $limit = $_POST['limit_per_user'];
        $min_order = $_POST['min_order'];
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date   = !empty($_POST['end_date'])   ? $_POST['end_date']   : null;
        $target_user = !empty($_POST['target_user_id']) ? $_POST['target_user_id'] : null;
        $cd_val = isset($_POST['cooldown_val']) ? intval($_POST['cooldown_val']) : 0;
        $cd_unit = isset($_POST['cooldown_unit']) ? intval($_POST['cooldown_unit']) : 1;
        $cooldown_seconds = $cd_val * $cd_unit;
        $id = $_POST['id'] ?? null;

        if ($id) {
            $stmt = $db->prepare("UPDATE vouchers SET code=?, discount_type=?, discount_amount=?, max_discount=?, points_cost=?, quantity=?, limit_per_user=?, min_order_amount=?, start_date=?, end_date=?, target_user_id=?, cooldown_seconds=? WHERE id=?");
            $stmt->bind_param("ssiiiiisssiii", $code, $type, $amount, $max_disc, $points, $qty, $limit, $min_order, $start_date, $end_date, $target_user, $cooldown_seconds, $id);
        } else {
            $stmt = $db->prepare("INSERT INTO vouchers (code, discount_type, discount_amount, max_discount, points_cost, quantity, limit_per_user, min_order_amount, start_date, end_date, target_user_id, cooldown_seconds) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiiiiisssii", $code, $type, $amount, $max_disc, $points, $qty, $limit, $min_order, $start_date, $end_date, $target_user, $cooldown_seconds);
        }
        $stmt->execute();
    }
    header("Location: Admin_Vouchers.php");
    exit;
}

// --- TÌM KIẾM & PHÂN TRANG ---
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$searchTerm = "%$search%";

$countSql = "SELECT COUNT(*) as total FROM vouchers WHERE code LIKE ?";
$stmtC = $db->prepare($countSql);
$stmtC->bind_param("s", $searchTerm);
$stmtC->execute();
$totalRows = $stmtC->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$stmtC->close();

$sql = "SELECT * FROM vouchers WHERE code LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("sii", $searchTerm, $limit, $offset);
$stmt->execute();
$vouchers = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Quản Lý Voucher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <?php include '../Compoment/Menu.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Quản Lý Voucher 🎫</h1>
            <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 shadow flex gap-2 items-center"><i class="fas fa-plus"></i> Phát Hành Voucher</button>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <form method="GET" class="flex gap-2 w-full md:w-1/2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Tìm mã voucher..." class="w-full border p-2 rounded-lg text-sm outline-none focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"><i class="fas fa-search"></i></button>
                <?php if($search): ?><a href="Admin_Vouchers.php" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-300 flex items-center">Hủy</a><?php endif; ?>
            </form>
            <div class="text-sm text-gray-500"><b><?= $vouchers->num_rows ?></b> / <b><?= $totalRows ?></b> voucher</div>
        </div>

        <div class="bg-white rounded-xl shadow overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-200 uppercase text-xs text-gray-600">
                    <tr>
                        <th class="p-4">Code</th>
                        <th class="p-4">Giảm Giá</th>
                        <th class="p-4">Thời Gian</th>
                        <th class="p-4">Đối Tượng</th>
                        <th class="p-4">SL / Đã Dùng</th>
                        <th class="p-4">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if($vouchers->num_rows > 0): while($v = $vouchers->fetch_assoc()): ?>
                    <tr class="hover:bg-blue-50 transition">
                        <td class="p-4 font-bold text-blue-600"><?= htmlspecialchars($v['code']) ?></td>
                        <td class="p-4">
                            <div class="font-bold text-gray-800">
                                <?= $v['discount_type'] == 'percent' ? $v['discount_amount'].'%' : number_format($v['discount_amount']).'đ' ?>
                            </div>
                            <div class="text-xs text-gray-500">Đơn từ: <?= number_format($v['min_order_amount']) ?>đ</div>
                            <?php if($v['cooldown_seconds'] > 0): 
                                $s = $v['cooldown_seconds'];
                                $wait_str = ($s >= 86400) ? round($s/86400,1).' ngày' : (($s >= 3600) ? round($s/3600,1).' giờ' : round($s/60).' phút');
                            ?>
                                <div class="text-[10px] text-orange-500 font-bold mt-1"><i class="fas fa-history"></i> Chờ: <?= $wait_str ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-xs">
                            <?php if(empty($v['start_date'])): ?>
                                <span class="bg-gray-200 text-gray-500 px-2 py-1 rounded font-bold">Chưa phát hành</span>
                            <?php else: ?>
                                <div class="text-green-600">BĐ: <?= date('d/m/Y', strtotime($v['start_date'])) ?></div>
                                <div class="<?= empty($v['end_date']) ? 'text-blue-500' : 'text-red-500' ?>">
                                    KT: <?= empty($v['end_date']) ? 'Vĩnh viễn' : date('d/m/Y', strtotime($v['end_date'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-xs">
                            <?= empty($v['target_user_id']) ? 
                                '<span class="bg-green-100 text-green-700 px-2 py-1 rounded font-bold">Toàn bộ</span>' : 
                                '<span class="bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold"><i class="fas fa-user mr-1"></i>ID: '.$v['target_user_id'].'</span>' 
                            ?>
                        </td>
                        <td class="p-4 text-sm"><b><?= $v['redeemed_count'] ?></b> / <?= $v['quantity'] ?></td>
                        <td class="p-4 flex gap-2">
                            <button onclick='openModal(<?= json_encode($v) ?>)' class="text-yellow-500 hover:bg-yellow-100 p-2 rounded"><i class="fas fa-edit"></i></button>
                            <form method="POST" onsubmit="return confirm('Xóa voucher này?')" style="display:inline;"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $v['id'] ?>"><button class="text-red-500 hover:bg-red-100 p-2 rounded"><i class="fas fa-trash"></i></button></form>
                        </td>
                    </tr>
                    <?php endwhile; else: echo "<tr><td colspan='6' class='p-8 text-center text-gray-500'>Không tìm thấy voucher nào.</td></tr>"; endif; ?>
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

    <div id="voucherModal" class="fixed inset-0 bg-black/50 hidden z-50 flex justify-center items-center backdrop-blur-sm">
        <div class="bg-white p-6 rounded-xl w-[600px] shadow-2xl relative">
            <h2 class="text-xl font-bold mb-4 text-gray-800 border-b pb-2" id="modalTitle">Thiết Lập Voucher</h2>
            <form method="POST" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="vid">
                
                <div class="col-span-2">
                    <label class="text-xs font-bold text-gray-500">Mã Code</label>
                    <input type="text" name="code" id="vcode" class="w-full border p-2 rounded uppercase font-bold bg-gray-50" placeholder="VD: SALE50" required>
                </div>

                <div><label class="text-xs font-bold text-gray-500">Ngày bắt đầu</label><input type="date" name="start_date" id="vstart" class="w-full border p-2 rounded"><p class="text-[10px] text-gray-400">Để trống = Chưa phát hành</p></div>
                <div><label class="text-xs font-bold text-gray-500">Ngày kết thúc</label><input type="date" name="end_date" id="vend" class="w-full border p-2 rounded"><p class="text-[10px] text-gray-400">Để trống = Vĩnh viễn</p></div>

                <div>
                    <label class="text-xs font-bold text-gray-500">Loại giảm giá</label>
                    <select name="discount_type" id="vtype" class="w-full border p-2 rounded" onchange="toggleMaxDiscount()">
                        <option value="fixed">Tiền mặt (VNĐ)</option>
                        <option value="percent">Phần trăm (%)</option>
                    </select>
                </div>
                <div><label class="text-xs font-bold text-gray-500">Giá trị giảm</label><input type="number" name="discount_amount" id="vamount" class="w-full border p-2 rounded font-bold" required></div>

                <div id="max_disc_group" class="hidden"><label class="text-xs font-bold text-gray-500">Giảm tối đa (VNĐ)</label><input type="number" name="max_discount" id="vmax" class="w-full border p-2 rounded" value="0"></div>
                <div><label class="text-xs font-bold text-gray-500">Đơn tối thiểu (VNĐ)</label><input type="number" name="min_order" id="vmin" class="w-full border p-2 rounded" value="0"></div>

                <div><label class="text-xs font-bold text-gray-500">Điểm đổi quà</label><input type="number" name="points_cost" id="vpoints" class="w-full border p-2 rounded" value="0"></div>
                
                <div class="col-span-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
                    <label class="text-xs font-bold text-blue-600 flex items-center gap-1"><i class="fas fa-gift"></i> Tặng riêng cho (User ID)</label>
                    <input type="number" name="target_user_id" id="vtarget" class="w-full border p-2 rounded bg-white mt-1" placeholder="Để trống = Công khai cho tất cả">
                </div>

                <div class="col-span-2 grid grid-cols-3 gap-2 bg-yellow-50 p-2 rounded border border-yellow-100">
                    <div><label class="text-xs font-bold text-gray-500">Tổng SL</label><input type="number" name="qty" id="vqty" class="w-full border p-2 rounded bg-white" value="100"></div>
                    <div><label class="text-xs font-bold text-gray-500">Giới hạn/User</label><input type="number" name="limit_per_user" id="vlimit" class="w-full border p-2 rounded bg-white" value="1"></div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 flex items-center gap-1"><i class="fas fa-hourglass-half"></i> Chờ đổi lại</label>
                        <div class="flex gap-1">
                            <input type="number" name="cooldown_val" id="vcooldown_val" class="w-full border p-1 rounded bg-white text-center" value="0">
                            <select name="cooldown_unit" id="vcooldown_unit" class="border p-1 rounded bg-white text-xs w-16">
                                <option value="60">Phút</option>
                                <option value="3600">Giờ</option>
                                <option value="86400">Ngày</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-span-2 flex justify-end gap-2 mt-4 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('voucherModal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 rounded text-sm font-bold">Hủy</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm font-bold hover:bg-blue-700">Lưu Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMaxDiscount() {
            const type = document.getElementById('vtype').value;
            document.getElementById('max_disc_group').classList.toggle('hidden', type !== 'percent');
        }

        function openModal(data = null) {
            document.getElementById('voucherModal').classList.remove('hidden');
            if(data) {
                document.getElementById('modalTitle').innerText = 'Sửa Voucher: ' + data.code;
                document.getElementById('vid').value = data.id;
                document.getElementById('vcode').value = data.code;
                document.getElementById('vstart').value = data.start_date; 
                document.getElementById('vend').value = data.end_date;
                document.getElementById('vtype').value = data.discount_type;
                document.getElementById('vamount').value = data.discount_amount;
                document.getElementById('vmax').value = data.max_discount;
                document.getElementById('vmin').value = data.min_order_amount;
                document.getElementById('vpoints').value = data.points_cost;
                document.getElementById('vqty').value = data.quantity;
                document.getElementById('vlimit').value = data.limit_per_user;
                document.getElementById('vtarget').value = data.target_user_id; 

                // Quy đổi giây ra đơn vị
                let sec = parseInt(data.cooldown_seconds || 0);
                let val = 0, unit = 60; 
                if (sec > 0) {
                    if (sec % 86400 === 0) { val = sec/86400; unit = 86400; }
                    else if (sec % 3600 === 0) { val = sec/3600; unit = 3600; }
                    else { val = Math.ceil(sec/60); unit = 60; }
                }
                document.getElementById('vcooldown_val').value = val;
                document.getElementById('vcooldown_unit').value = unit;
            } else {
                document.getElementById('modalTitle').innerText = 'Thêm Voucher Mới';
                document.getElementById('vid').value = '';
                document.getElementById('vcode').value = '';
                document.getElementById('vstart').value = ''; 
                document.getElementById('vend').value = '';
                document.getElementById('vmin').value = 0;
                document.getElementById('vtarget').value = '';
                document.getElementById('vcooldown_val').value = 0;
            }
            toggleMaxDiscount();
        }
    </script>
</body>
</html>