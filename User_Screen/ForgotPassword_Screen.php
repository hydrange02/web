<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    
    <div class="bg-white p-8 rounded-2xl shadow-xl w-[400px] relative overflow-hidden">
        <a href="../index.php" class="absolute top-4 left-4 text-gray-400 hover:text-blue-600">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-2">Khôi Phục Tài Khoản</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Nhập email để nhận mã xác thực</p>

        <form id="form-step-1" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Email đăng ký</label>
                <input type="email" id="email" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="example@gmail.com" required>
            </div>
            <button type="submit" id="btn-send" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 transition shadow-lg">
                Gửi Mã OTP
            </button>
        </form>

        <form id="form-step-2" class="hidden space-y-4 animate-fade-in-up">
            <div class="bg-blue-50 p-3 rounded-lg text-sm text-blue-800 mb-2">
                Mã OTP đã được gửi tới: <b id="sent-email">...</b>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Mã OTP</label>
                <input type="text" id="otp" class="w-full p-3 border rounded-lg text-center font-bold tracking-widest text-xl focus:ring-2 focus:ring-green-500 outline-none" placeholder="######" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Mật khẩu mới</label>
                <input type="password" id="new_pass" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="******" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">Xác nhận mật khẩu</label>
                <input type="password" id="confirm_pass" class="w-full p-3 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="******" required>
            </div>
            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-xl font-bold hover:bg-green-700 transition shadow-lg">
                Đổi Mật Khẩu
            </button>
        </form>

    </div>

    <script>
        const form1 = document.getElementById('form-step-1');
        const form2 = document.getElementById('form-step-2');
        const btnSend = document.getElementById('btn-send');
        let currentEmail = '';

        // Xử lý Gửi OTP
        form1.addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            currentEmail = email;
            
            btnSend.disabled = true;
            btnSend.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';

            try {
                const formData = new FormData();
                formData.append('action', 'send_otp');
                formData.append('email', email);

                const res = await fetch('../Config/forgot_password.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Đã gửi mã!', text: 'Vui lòng kiểm tra email của bạn.', timer: 1500, showConfirmButton: false });
                    // Chuyển sang bước 2
                    form1.classList.add('hidden');
                    form2.classList.remove('hidden');
                    document.getElementById('sent-email').innerText = email;
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                    btnSend.disabled = false;
                    btnSend.innerText = 'Gửi Mã OTP';
                }
            } catch (err) {
                Swal.fire('Lỗi kết nối', 'Không thể kết nối server', 'error');
                btnSend.disabled = false;
                btnSend.innerText = 'Gửi Mã OTP';
            }
        });

        // Xử lý Đổi Mật Khẩu
        form2.addEventListener('submit', async (e) => {
            e.preventDefault();
            const otp = document.getElementById('otp').value.trim();
            const pass = document.getElementById('new_pass').value;
            const confirm = document.getElementById('confirm_pass').value;

            if (pass !== confirm) return Swal.fire('Lỗi', 'Mật khẩu xác nhận không khớp', 'warning');

            try {
                const formData = new FormData();
                formData.append('action', 'reset_pass');
                formData.append('email', currentEmail);
                formData.append('otp', otp);
                formData.append('password', pass);
                formData.append('confirm', confirm);

                const res = await fetch('../Config/forgot_password.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire({ icon: 'success', title: 'Thành công!', text: 'Mật khẩu đã được thay đổi.', confirmButtonText: 'Đăng Nhập Ngay' });
                    window.location.href = '../index.php';
                } else {
                    Swal.fire('Thất bại', data.message, 'error');
                }
            } catch (err) { Swal.fire('Lỗi', 'Lỗi hệ thống', 'error'); }
        });
    </script>
</body>
</html>