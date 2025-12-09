<?php
// File: Web php/User_Screen/Account_Screen.php
session_start();
include '../Config/Database.php';

$id = $_SESSION['user_id'] ?? null;
if (!$id) { echo "<script>window.location.href = '../index.php';</script>"; exit; }

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$csrf_token = $_SESSION['csrf_token'];

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT username, email, phone, img, role, current_points FROM users WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    
    <header class="h-[150px] w-full flex flex-col justify-center items-center bg-gradient-to-r from-blue-600 to-teal-400 relative overflow-hidden rounded-b-xl shadow-lg">
        <h1 class="text-3xl font-extrabold text-white">Xin chào, <?= htmlspecialchars($user['username']) ?>!</h1>
        <p class="text-white/90 mt-1">Điểm tích lũy: <b class="text-yellow-300"><?= number_format($user['current_points']) ?></b> pts</p>
        <a href="VoucherWallet_Screen.php" class="mt-3 bg-white/20 hover:bg-white/30 text-white px-5 py-1.5 rounded-full text-sm font-bold transition border border-white/40 flex items-center gap-2 backdrop-blur-sm">
            <i class="fas fa-ticket-alt"></i> Vào Kho Voucher & Đổi Điểm
        </a>
    </header>

    <?php include '../Compoment/Menu.php'; ?>

    <div class="flex justify-center py-10 px-4">
        <div class="max-w-6xl w-full flex flex-col md:flex-row gap-8 items-start">

            <div class="flex-1 w-full bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-2">Thông Tin Tài Khoản</h2>
                <div class="flex flex-col items-center mb-10">
                    <div class="relative w-32 h-32 mb-4 group">
                        <img id="preview" src="<?= $user['img'] ? htmlspecialchars($user['img']) : '../assets/web/logo-removebg.png' ?>" class="rounded-full w-full h-full object-cover border-4 border-blue-500 shadow-lg">
                        <button id="choose-avatar" class="absolute bottom-0 right-0 bg-blue-500 text-white p-2 rounded-full shadow hover:bg-blue-600 transition"><i class="fas fa-camera"></i></button>
                    </div>
                    <input type="file" id="avatar" accept="image/*" class="hidden">
                </div>
                <div class="flex flex-col gap-5">
                    <input type="hidden" id="csrf_token" value="<?= $csrf_token ?>">
                    <div><label class="text-xs font-bold text-gray-500">Tên hiển thị</label><input type="text" id="username" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none" value="<?= htmlspecialchars($user['username']) ?>"></div>
                    <div><label class="text-xs font-bold text-gray-500">Email</label><input type="email" id="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none" value="<?= htmlspecialchars($user['email']) ?>"></div>
                    <div><label class="text-xs font-bold text-gray-500">Số điện thoại</label><input type="text" id="phone" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-400 outline-none" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                    <button id="button" class="bg-blue-600 text-white p-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg mt-2">Lưu Thay Đổi</button>
                </div>
            </div>

            <div class="w-full md:w-1/3 flex flex-col gap-6">
                
                <div class="bg-white rounded-2xl shadow-lg p-6 relative">
                    <div class="flex justify-between items-center mb-4">
                        <h1 class="text-lg font-bold text-gray-700 flex items-center gap-2"><i class="fas fa-map-marked-alt text-red-500"></i> Sổ Địa Chỉ</h1>
                        <button onclick="openAddressModal()" class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded hover:bg-blue-200 font-bold"><i class="fas fa-plus"></i> Thêm</button>
                    </div>
                    <div id="address-list" class="space-y-3 max-h-60 overflow-y-auto custom-scrollbar pr-1">
                        </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h1 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2"><i class="fas fa-key text-orange-500"></i> Bảo Mật</h1>
                    <div class="space-y-3">
                        <input type="password" id="old_password" placeholder="Mật khẩu cũ" class="w-full p-2 border rounded text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                        <input type="password" id="new_password" placeholder="Mật khẩu mới" class="w-full p-2 border rounded text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                        <input type="password" id="confirm_password" placeholder="Xác nhận mật khẩu" class="w-full p-2 border rounded text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                        <div class="flex gap-2">
                            <input type="text" id="otp_code" placeholder="Mã OTP" class="w-full p-2 border rounded text-sm focus:ring-2 focus:ring-orange-400 outline-none">
                            <button id="get_otp_btn" class="bg-gray-200 text-gray-700 px-3 py-1 rounded text-xs font-bold hover:bg-gray-300 whitespace-nowrap">Lấy mã</button>
                        </div>
                        <button id="change_password_btn" class="w-full bg-orange-500 text-white p-2 rounded font-bold hover:bg-orange-600 transition text-sm">Đổi Mật Khẩu</button>
                    </div>
                </div>

                <a href="../Config/logout.php" onclick="return confirm('Đăng xuất?')" class="bg-red-100 text-red-600 p-4 rounded-xl font-bold text-center hover:bg-red-200 transition">Đăng Xuất</a>
            </div>
        </div>
    </div>

    <div id="addr-modal" class="fixed inset-0 bg-gray-900/50 hidden z-50 flex justify-center items-center backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-[400px] p-6 relative animate-bounce-slow">
            <h3 id="modal-title" class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Thêm Địa Chỉ</h3>
            
            <input type="hidden" id="addr_id" value="0"> <div class="space-y-4">
                <div>
                    <label class="text-xs font-bold text-gray-500">Tên người nhận</label>
                    <input type="text" id="addr_name" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500">Số điện thoại</label>
                    <input type="text" id="addr_phone" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500">Địa chỉ chi tiết</label>
                    <textarea id="addr_detail" rows="3" class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-500 outline-none resize-none"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="closeAddressModal()" class="px-4 py-2 bg-gray-200 rounded text-gray-700 font-bold hover:bg-gray-300">Hủy</button>
                <button onclick="saveAddress()" class="px-4 py-2 bg-blue-600 text-white rounded font-bold hover:bg-blue-700 shadow">Lưu</button>
            </div>
        </div>
    </div>
    
    <script>
        const csrfToken = document.getElementById('csrf_token').value;

        // --- 1. LOGIC QUẢN LÝ ĐỊA CHỈ (Load, Add, Edit, Delete) ---
        
        async function loadAddresses() {
            const list = document.getElementById('address-list');
            list.innerHTML = '<p class="text-center text-gray-400 text-xs"><i class="fas fa-spinner fa-spin"></i> Đang tải...</p>';
            try {
                const res = await fetch('../Config/get_addresses.php');
                const data = await res.json();
                if (data.length === 0) { list.innerHTML = '<p class="text-center text-gray-400 text-xs italic">Chưa có địa chỉ nào.</p>'; return; }
                
                list.innerHTML = data.map(addr => {
                    // Chuẩn bị dữ liệu an toàn để truyền vào hàm Edit
                    const safeName = addr.recipient_name.replace(/'/g, "&apos;");
                    const safeAddr = addr.address.replace(/'/g, "&apos;");
                    return `
                    <div class="bg-gray-50 p-3 rounded border border-gray-200 relative group hover:border-blue-300 transition">
                        <div class="flex justify-between">
                            <p class="font-bold text-gray-800 text-sm">${addr.recipient_name}</p>
                            <span class="text-[10px] text-gray-500 bg-white border px-1 rounded">${addr.phone}</span>
                        </div>
                        <p class="text-xs text-gray-600 mt-1 line-clamp-2">${addr.address}</p>
                        
                        <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition">
                            <button onclick="openAddressModal(${addr.id}, '${safeName}', '${addr.phone}', '${safeAddr}')" class="w-6 h-6 flex items-center justify-center bg-white text-blue-500 rounded border hover:bg-blue-500 hover:text-white transition"><i class="fas fa-pen text-[10px]"></i></button>
                            <button onclick="deleteAddress(${addr.id})" class="w-6 h-6 flex items-center justify-center bg-white text-red-500 rounded border hover:bg-red-500 hover:text-white transition"><i class="fas fa-trash text-[10px]"></i></button>
                        </div>
                    </div>`;
                }).join('');
            } catch (e) { console.error(e); }
        }
        loadAddresses();

        // Mở Modal (Dùng chung cho Thêm và Sửa)
        function openAddressModal(id = 0, name = '', phone = '', detail = '') {
            document.getElementById('addr_modal').classList.remove('hidden');
            document.getElementById('addr_id').value = id;
            document.getElementById('addr_name').value = name;
            document.getElementById('addr_phone').value = phone;
            document.getElementById('addr_detail').value = detail;
            document.getElementById('modal-title').innerText = id === 0 ? 'Thêm Địa Chỉ Mới' : 'Cập Nhật Địa Chỉ';
        }

        function closeAddressModal() {
            document.getElementById('addr_modal').classList.add('hidden');
        }

        // Lưu địa chỉ (Validate kỹ càng)
        async function saveAddress() {
            const id = document.getElementById('addr_id').value;
            const name = document.getElementById('addr_name').value.trim();
            const phone = document.getElementById('addr_phone').value.trim();
            const address = document.getElementById('addr_detail').value.trim();

            // VALIDATE FORM
            if (!name || !phone || !address) {
                return Swal.fire('Thiếu thông tin', 'Vui lòng điền đầy đủ các trường!', 'warning');
            }
            // Regex check số điện thoại VN (10-11 số)
            if (!/^[0-9]{10,11}$/.test(phone)) {
                return Swal.fire('SĐT không hợp lệ', 'Số điện thoại phải có 10-11 chữ số!', 'error');
            }

            const formData = new FormData();
            formData.append('id', id);
            formData.append('name', name);
            formData.append('phone', phone);
            formData.append('address', address);

            const apiUrl = (id == 0) ? '../Config/add_address.php' : '../Config/edit_address.php';

            try {
                const res = await fetch(apiUrl, { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Thành công', text: data.message, timer: 1500, showConfirmButton: false });
                    closeAddressModal();
                    loadAddresses();
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            } catch(e) { Swal.fire('Lỗi', 'Không kết nối được server', 'error'); }
        }
        
        async function deleteAddress(id) {
             if(!confirm('Xóa địa chỉ này?')) return;
            try {
                const formData = new FormData(); formData.append('id', id);
                const res = await fetch('../Config/delete_address.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) { loadAddresses(); } else Swal.fire('Lỗi', data.message, 'error');
            } catch(e) {}
        }

        // --- 2. CÁC LOGIC KHÁC (Update Info, Avatar, Password...) GIỮ NGUYÊN ---
        document.getElementById('button').addEventListener('click', async () => { /* ... Logic Update Info cũ ... */ });
        /* ... Logic Avatar, Change Password cũ ... */
        // Bạn copy lại phần JS xử lý đổi mật khẩu và avatar từ file cũ vào đây nhé
    </script>
    
    <script>document.getElementById('addr-modal').id = 'addr_modal';</script> 
    <?php include '../Compoment/Footer.php'; ?>
</body>
</html>