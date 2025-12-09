<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <title>Đăng Ký</title>
    <style>
        /* ... (Copy lại phần CSS style từ file cũ vào đây) ... */
        body {
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
        .shop-slogan { margin-top: 1rem; font-size: clamp(1.2rem, 2vw, 1.6rem); font-weight: 500; color: #e0f2fe; text-shadow: 0 0 12px rgba(0,0,0,0.4); font-family: 'Poppins', sans-serif; }
        @media (max-width: 1024px) { body { justify-content: center; padding-right: 0; flex-direction: column; } .shop-name-container { position: static; transform: none; margin-bottom: 2rem; margin-top: 2rem; max-width: 90%; } .login-box { width: 90%; max-width: 400px; margin-bottom: 2rem; } }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-600 to-teal-500">

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
        const email = document.getElementById('email'); // Lấy input email
        const username = document.getElementById('username');
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm');
        const message = document.getElementById('message');
        const button = document.getElementById('register');
        const togglepass = document.getElementById('togglepass');

        // Logic ẩn/hiện mật khẩu (Đơn giản hóa)
        togglepass.addEventListener('click', () => {
            const type = password.type === "password" ? "text" : "password";
            password.type = confirm.type = type;
        });

        // Hàm kiểm tra định dạng Email
        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Logic kiểm tra form
        function check() {
            if (!email.value || !username.value || !password.value || !confirm.value) {
                message.textContent = "Vui lòng nhập đủ thông tin.";
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

        email.addEventListener("input", check);
        username.addEventListener("input", check);
        password.addEventListener("input", check);
        confirm.addEventListener("input", check);

        // Logic xử lý Đăng ký
        button.addEventListener("click", async (e) => {
            e.preventDefault();

            // Hiệu ứng Loading
            button.textContent = "Đang xử lý...";
            button.disabled = true;

            try {
                const res = await fetch('../Config/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    // QUAN TRỌNG: Gửi cả email lên server
                    body: new URLSearchParams({
                        email: email.value,
                        username: username.value,
                        password: password.value,
                        confirm: confirm.value
                    })
                });
                const data = await res.json();

                await Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.success ? 'Thành công' : 'Thất bại',
                    text: data.message
                });

                if (data.success) {
                    window.location.href = '../index.php'; // Chuyển về đăng nhập
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Lỗi', 'Không thể kết nối server', 'error');
            } finally {
                button.textContent = "Đăng Ký";
                if(message.style.color === "green") button.disabled = false;
            }
        });
    </script>
</body>
</html>