<?php
// File: Web php/User_Screen/VoucherWallet_Screen.php
session_start();
include '../Config/Database.php';
include '../Compoment/Menu.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo "<script>window.location.href = '../index.php';</script>"; exit; }

$db = Database::getInstance()->getConnection();

$u_res = $db->query("SELECT current_points FROM users WHERE id = $user_id");
$points = $u_res->fetch_assoc()['current_points'] ?? 0;

// Lấy lịch sử đổi
$history_map = []; 
$check_owned = $db->query("SELECT voucher_id, COUNT(*) as qty, MAX(created_at) as last_time FROM user_vouchers WHERE user_id = $user_id GROUP BY voucher_id");
while($r = $check_owned->fetch_assoc()) { $history_map[$r['voucher_id']] = $r; }

// Lấy voucher khả dụng (Đã phát hành & (Vĩnh viễn hoặc còn hạn))
$sql_available = "SELECT * FROM vouchers 
                  WHERE (target_user_id IS NULL OR target_user_id = 0 OR target_user_id = $user_id)
                  AND (start_date IS NOT NULL AND start_date <= CURDATE()) 
                  AND (end_date IS NULL OR end_date >= CURDATE())
                  AND quantity > redeemed_count 
                  ORDER BY target_user_id DESC, points_cost ASC"; 
$v_avail = $db->query($sql_available);

$sql_my = "SELECT uv.id as user_voucher_id, uv.is_used, v.* FROM user_vouchers uv JOIN vouchers v ON uv.voucher_id = v.id WHERE uv.user_id = $user_id ORDER BY uv.is_used ASC, v.end_date DESC";
$v_my = $db->query($sql_my);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Ví Voucher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .ticket-sawtooth { background-image: radial-gradient(circle, transparent 6px, #fff 6px); background-size: 16px 16px; background-position: -8px -8px; }
        .grayscale-card { filter: grayscale(100%); opacity: 0.7; }
        .private-badge { position: absolute; top: 0; left: 0; background: linear-gradient(45deg, #F59E0B, #D97706); color: #fff; font-size: 10px; font-weight: bold; padding: 4px 8px; border-bottom-right-radius: 10px; z-index: 20; }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    
    <header class="h-[200px] w-full flex flex-col justify-center items-center bg-gradient-to-r from-orange-500 to-red-500 shadow-lg rounded-b-[40px] text-white">
        <p class="text-sm opacity-90 uppercase tracking-widest">Điểm tích lũy</p>
        <h1 class="text-5xl font-extrabold mt-2"><?= number_format($points) ?> <span class="text-xl">pts</span></h1>
        <div class="mt-4 text-xs bg-white/20 px-3 py-1 rounded-full border border-white/20">User ID: <?= $user_id ?></div>
    </header>

    <div class="max-w-6xl mx-auto px-4 py-8 -mt-10 relative z-10">
        
        <div class="bg-white p-1.5 rounded-full shadow-md flex max-w-md mx-auto mb-8 border border-gray-100">
            <button onclick="switchTab('redeem')" id="tab-redeem" class="flex-1 py-2.5 rounded-full font-bold text-sm transition-all bg-orange-500 text-white shadow">Đổi Voucher</button>
            <button onclick="switchTab('my-wallet')" id="tab-my-wallet" class="flex-1 py-2.5 rounded-full font-bold text-sm transition-all text-gray-500 hover:bg-gray-50">Ví Của Tôi</button>
        </div>

        <div id="content-redeem" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php if($v_avail && $v_avail->num_rows > 0): while($v = $v_avail->fetch_assoc()): 
                $hist = $history_map[$v['id']] ?? null;
                $my_qty = $hist ? $hist['qty'] : 0;
                $last_time = $hist ? strtotime($hist['last_time']) : 0;
                
                $limit = $v['limit_per_user'];
                $is_maxed = ($my_qty >= $limit);
                $is_private = (!empty($v['target_user_id']) && $v['target_user_id'] == $user_id);
                $min_order = isset($v['min_order_amount']) ? $v['min_order_amount'] : 0;
                
                // Check Cooldown
                $is_cooldown = false;
                $wait_msg = "";
                $cd_sec = intval($v['cooldown_seconds']);

                if ($cd_sec > 0 && $last_time > 0) {
                    $next_time = $last_time + $cd_sec;
                    if (time() < $next_time) {
                        $is_cooldown = true;
                        $diff = $next_time - time();
                        if ($diff >= 86400) $wait_msg = "Chờ ".ceil($diff/86400)." ngày";
                        elseif ($diff >= 3600) $wait_msg = "Chờ ".ceil($diff/3600)." giờ";
                        else $wait_msg = "Chờ ".ceil($diff/60)." phút";
                    }
                }

                $btn_text = "Đổi với " . number_format($v['points_cost']) . " pts";
                $btn_class = "bg-orange-100 text-orange-600 hover:bg-orange-600 hover:text-white";
                $btn_action = "onclick=\"redeemVoucher({$v['id']})\"";

                if ($is_maxed) {
                    $btn_text = "Đã sở hữu ($my_qty/$limit)";
                    $btn_class = "bg-green-100 text-green-700 cursor-not-allowed border border-green-200";
                    $btn_action = "disabled";
                } elseif ($is_cooldown) {
                    $btn_text = "<i class='fas fa-clock mr-1'></i> $wait_msg";
                    $btn_class = "bg-gray-200 text-gray-500 cursor-not-allowed";
                    $btn_action = "disabled";
                } elseif ($points < $v['points_cost']) {
                    $btn_class = "bg-gray-100 text-gray-400 cursor-not-allowed";
                    $btn_action = "disabled title='Không đủ điểm'";
                }
            ?>
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden flex flex-col hover:shadow-lg transition duration-300 relative group <?= $is_private ? 'ring-2 ring-orange-300' : '' ?>">
                <?php if($is_private): ?><div class="private-badge"><i class="fas fa-gift mr-1"></i> TẶNG RIÊNG BẠN</div><?php endif; ?>
                <div class="h-24 bg-gradient-to-r from-blue-600 to-cyan-500 p-4 text-white relative">
                    <h3 class="text-2xl font-bold"><?= $v['discount_type'] == 'percent' ? $v['discount_amount'].'%' : number_format($v['discount_amount']/1000).'K' ?></h3>
                    <p class="text-xs opacity-90 mt-1">Đơn từ <?= number_format($min_order) ?>đ</p>
                    <div class="absolute bottom-0 left-0 w-full h-2 bg-white ticket-sawtooth"></div>
                </div>
                <div class="p-4 flex-1 flex flex-col justify-between relative z-10">
                    <div class="flex justify-between items-center mb-3">
                        <p class="text-xs text-gray-500"><?= !empty($v['end_date']) ? 'HSD: '.date('d/m/Y', strtotime($v['end_date'])) : 'Vĩnh viễn' ?></p>
                        <?php if($is_maxed): ?>
                            <span class="text-[10px] bg-green-50 text-green-600 px-2 py-0.5 rounded font-bold">Đã có</span>
                        <?php else: ?>
                            <span class="text-[10px] text-gray-400">Còn: <?= $v['quantity'] - $v['redeemed_count'] ?></span>
                        <?php endif; ?>
                    </div>
                    <button <?= $btn_action ?> class="w-full font-bold py-2.5 rounded-lg text-sm transition shadow-sm <?= $btn_class ?>"><?= $btn_text ?></button>
                </div>
            </div>
            <?php endwhile; else: echo "<p class='col-span-full text-center text-gray-400 py-10 italic'>Hiện không có voucher nào.</p>"; endif; ?>
        </div>

        <div id="content-my-wallet" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if($v_my && $v_my->num_rows > 0): while($mv = $v_my->fetch_assoc()): 
                $is_used = $mv['is_used'] == 1;
                $is_expired = !empty($mv['end_date']) && strtotime($mv['end_date']) < time();
                $css_class = ($is_used || $is_expired) ? 'grayscale-card' : '';
                $min_order = isset($mv['min_order_amount']) ? $mv['min_order_amount'] : 0;
                
                $btn_html = "";
                if ($is_used) $btn_html = '<span class="text-xs font-bold text-gray-500 bg-gray-200 px-4 py-2 rounded">ĐÃ DÙNG</span>';
                elseif ($is_expired) $btn_html = '<span class="text-xs font-bold text-red-500 bg-red-100 px-4 py-2 rounded">HẾT HẠN</span>';
                else $btn_html = '<a href="Cart_Screen.php" class="text-xs font-bold text-white bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 shadow">DÙNG NGAY</a>';
            ?>
            <div class="bg-white rounded-xl shadow-md border flex overflow-hidden relative transition <?= $css_class ?>">
                <div class="w-28 bg-gray-800 text-white flex flex-col items-center justify-center p-2 relative">
                    <span class="text-xl font-bold text-yellow-400"><?= $mv['discount_type'] == 'percent' ? $mv['discount_amount'].'%' : number_format($mv['discount_amount']/1000).'K' ?></span>
                    <span class="text-[10px] uppercase mt-1 opacity-75">Voucher</span>
                    <div class="absolute right-0 top-0 h-full border-r-2 border-dashed border-white/30"></div>
                </div>
                <div class="flex-1 p-4 flex flex-col justify-center">
                    <div class="flex justify-between items-start mb-2">
                        <div><p class="text-[10px] font-bold text-gray-400 uppercase">Mã Code</p><p class="text-lg font-mono font-bold text-gray-800 select-all tracking-wider"><?= $mv['code'] ?></p></div>
                        <?php if(!$is_used && !$is_expired): ?><button onclick="navigator.clipboard.writeText('<?= $mv['code'] ?>'); Swal.fire({icon:'success', title:'Đã sao chép', toast:true, position:'top-end', showConfirmButton:false, timer:1500})" class="text-gray-400 hover:text-blue-500 transition p-1"><i class="far fa-copy"></i></button><?php endif; ?>
                    </div>
                    <div class="flex justify-between items-end mt-2">
                        <div class="text-xs text-gray-500"><p class="mb-0.5">Đơn từ: <span class="font-semibold text-gray-700"><?= number_format($min_order) ?>đ</span></p><p class="<?= $is_expired ? 'text-red-500 font-bold' : '' ?>"><?= !empty($mv['end_date']) ? 'HSD: '.date('d/m/Y', strtotime($mv['end_date'])) : 'Vĩnh viễn' ?></p></div>
                        <?= $btn_html ?>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
                <div class="col-span-full text-center py-12"><div class="inline-block p-4 rounded-full bg-gray-100 mb-3"><i class="fas fa-wallet text-3xl text-gray-400"></i></div><p class="text-gray-500 font-medium">Ví voucher trống!</p><button onclick="switchTab('redeem')" class="text-orange-500 text-sm hover:underline mt-2 font-bold">Đổi quà ngay</button></div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            const btnR = document.getElementById('tab-redeem'); const btnW = document.getElementById('tab-my-wallet');
            const divR = document.getElementById('content-redeem'); const divW = document.getElementById('content-my-wallet');
            if(tab === 'redeem') { divR.classList.remove('hidden'); divW.classList.add('hidden'); btnR.className = "flex-1 py-2.5 rounded-full font-bold text-sm transition-all bg-orange-500 text-white shadow"; btnW.className = "flex-1 py-2.5 rounded-full font-bold text-sm transition-all text-gray-500 hover:bg-gray-50"; } 
            else { divR.classList.add('hidden'); divW.classList.remove('hidden'); btnW.className = "flex-1 py-2.5 rounded-full font-bold text-sm transition-all bg-blue-600 text-white shadow"; btnR.className = "flex-1 py-2.5 rounded-full font-bold text-sm transition-all text-gray-500 hover:bg-gray-50"; }
        }
        async function redeemVoucher(vid) {
            if(!await Swal.fire({title: 'Xác nhận đổi?', icon: 'question', showCancelButton: true, confirmButtonText: 'Đổi ngay'}).then(r=>r.isConfirmed)) return;
            try {
                const fd = new FormData(); fd.append('voucher_id', vid);
                const res = await fetch('../Config/redeem_point.php', { method: 'POST', body: fd });
                const data = await res.json();
                if(data.success) Swal.fire('Thành công!', 'Voucher đã vào ví.', 'success').then(()=>location.reload());
                else Swal.fire('Thất bại', data.message, 'error');
            } catch(e) { Swal.fire('Lỗi', 'Lỗi kết nối', 'error'); }
        }
    </script>
    <?php include '../Compoment/Footer.php'; ?>
</body>
</html> 