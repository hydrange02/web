<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <title>Đăng Ký - Hydrange Shop</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #1e3a8a, #3b82f6, #06b6d4, #0ea5e9);
            background-size: 400% 400%;
            animation: gradientBG 60s ease infinite;
            position: relative;
            overflow: hidden;
            height: 100vh;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding-right: 5rem;
        }
        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }
        body::after { content: ''; position: absolute; inset: 0; background-color: rgba(0, 0, 0, 0.2); z-index: 0; }
        .shine-btn { position: relative; overflow: hidden; }
        .shine-btn::after { content: ''; position: absolute; top: -50%; left: -75%; width: 50%; height: 200%; background: rgba(255,255,255,0.3); transform: rotate(25deg); transition: all 0.5s; }
        .shine-btn:hover::after { left: 125%; }
        .shop-name-container { position: absolute; top: 50%; left: 50%; transform: translate(-100%, -50%); display: flex; flex-direction: column; align-items: center; text-align: center; z-index: 1; max-width: 40%; }
        .shop-logo { width: clamp(160px, 18vw, 320px); height: auto; object-fit: contain; filter: drop-shadow(0 0 15px rgba(0,0,0,0.6)); transition: transform 0.6s ease, filter 0.6s ease; }
        .shop-logo:hover { transform: scale(1.05); }
        .shop-slogan { margin-top: 1rem; font-size: clamp(1.2rem, 2vw, 1.6rem); font-weight: 500; color: #e0f2fe; text-shadow: 0 0 12px rgba(0,0,0,0.4); }
        @media (max-width: 1024px) { body { justify-content: center; padding-right: 0; flex-direction: column; } .shop-name-container { position: static; transform: none; margin-bottom: 2rem; margin-top: 2rem; max-width: 90%; } .login-box { width: 90%; max-width: 400px; margin-bottom: 2rem; } }
    </style>
</head>
<body>

    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-3"></div>

    <div class="shop-name-container">
        <img src="../assets/web/logo-removebg.png" alt="Hydrange Logo" class="shop-logo">
        <h1 class="text-4xl font-extrabold mb-2 mt-6 drop-shadow-md text-white">Hydrange Shop</h1>
        <p class="shop-slogan">Sản phẩm tuyệt vời, giá cả phải chăng.</p>
    </div>

    <div class="login-box bg-white/90 backdrop-blur-md shadow-2xl w-96 p-8 rounded-2xl flex flex-col items-center z-10">
        <h1 class="mb-6 text-4xl font-extrabold text-gray-800">Đăng Ký</h1>
        <form class="flex flex-col w-full items-center">
            
            <input type="email" id="email" name="email" placeholder="Email (để xác thực tài khoản)"
                class="mb-4 w-11/12 h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />

            <input type="text" id="username" name="username" placeholder="Username (Tối thiểu 6 ký tự)"
                class="mb-4 w-11/12 h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />
            
            <div class="relative w-11/12 mb-4">
                <input type="password" id="password" name="password" placeholder="Mật khẩu (Tối thiểu 6 ký tự)"
                    class="w-full h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />
                <button id="togglepass" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition">
                    <i class="fas fa-eye"></i> <span id="eye-text" class="text-xs font-bold">Hiện</span>
                </button>
            </div>

            <input type="password" id="confirm" name="confirm" placeholder="Xác nhận mật khẩu"
                class="mb-4 w-11/12 h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />

            <p id='message' class="text-sm h-5 mb-4 font-medium text-center"></p>

            <button class="shine-btn bg-blue-600 hover:bg-blue-700 w-11/12 h-12 rounded-xl text-white font-bold transition disabled:opacity-50 disabled:cursor-not-allowed" id="register" type="submit" disabled>
                Đăng Ký
            </button>
        </form>

        <div class="mt-6 text-gray-700">
            <span>Đã có tài khoản? </span>
            <a href="../index.php" class="text-blue-600 hover:underline font-medium">Đăng Nhập</a>
        </div>
    </div>

    <script>
        // --- 2. HÀM HIỂN THỊ TOAST ---
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            // Màu sắc
            const bgColors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-yellow-500' };
            const icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
            const bgColor = bgColors[type] || bgColors.success;

            // Style Tailwind (Trượt từ phải sang)
            toast.className = `${bgColor} text-white px-6 py-4 rounded-lg shadow-2xl flex items-center gap-3 transform transition-all duration-500 translate-x-full opacity-0 min-w-[320px] max-w-md`;
            toast.innerHTML = `<div class="text-2xl">${icon}</div><div class="font-bold text-sm">${message}</div>`;

            container.appendChild(toast);

            // Hiện
            requestAnimationFrame(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            });

            // Ẩn sau 3s
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // --- 3. LOGIC XỬ LÝ FORM ---
        const email = document.getElementById('email');
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        const message = document.getElementById('message');
        const button = document.getElementById('register');
        const togglepass = document.getElementById('togglepass');

        // Toggle Password
        togglepass.addEventListener('click', () => {
            const type = password.type === "password" ? "text" : "password";
            password.type = confirm.type = type;
            document.getElementById('eye-text').textContent = type === "password" ? "Hiện" : "Ẩn";
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Real-time Validate
        function check() {
            if (!email.value || !username.value || !password.value || !confirm.value) {
                message.textContent = "Vui lòng nhập đủ thông tin.";
                message.style.color = "gray";
                button.disabled = true;
                return;
            }
            if (!isValidEmail(email.value)) {
                message.textContent = "Email không hợp lệ.";
                message.style.color = "red";
                button.disabled = true;
                return;
            }
            if (username.value.length < 6) {
                message.textContent = "Username tối thiểu 6 ký tự.";
                message.style.color = "red";
                button.disabled = true;
                return;
            }
            if (password.value.length < 6) {
                message.textContent = "Mật khẩu tối thiểu 6 ký tự.";
                message.style.color = "red";
                button.disabled = true;
                return;
            }
            if (password.value !== confirm.value) {
                message.textContent = "Mật khẩu xác nhận không khớp.";
                message.style.color = "red";
                button.disabled = true;
                return;
            }
            message.textContent = "Thông tin hợp lệ.";
            message.style.color = "green";
            button.disabled = false;
        }

        [email, username, password, confirm].forEach(inp => inp.addEventListener("input", check));

        // Submit Form
        button.addEventListener("click", async (e) => {
            e.preventDefault();
            button.textContent = "Đang xử lý...";
            button.disabled = true;

            try {
                const res = await fetch('../Config/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        email: email.value,
                        username: username.value,
                        password: password.value,
                        confirm: confirm.value
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    // Đợi 2s để người dùng đọc thông báo rồi chuyển trang
                    setTimeout(() => {
                        window.location.href = '../index.php';
                    }, 2500);
                } else {
                    showToast(data.message, 'error');
                    button.textContent = "Đăng Ký";
                    button.disabled = false;
                }
            } catch (error) {
                console.error(error);
                showToast('Lỗi kết nối server. Vui lòng thử lại!', 'error');
                button.textContent = "Đăng Ký";
                button.disabled = false;
            }
        });
    </script>
</body>
</html>