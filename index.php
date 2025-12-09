<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <title>Login</title>
    <style>
        /* ... (Giữ nguyên CSS cũ) ... */
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

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        body::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.2);
            z-index: 0;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: -150%;
            width: 150%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.4) 50%, rgba(255, 255, 255, 0) 100%);
            transform: skewX(-20deg);
            animation: shineBG 20s linear infinite;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes shineBG {
            0% {
                left: -150%;
            }

            100% {
                left: 150%;
            }
        }

        .shine-btn {
            position: relative;
            overflow: hidden;
        }

        .shine-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -75%;
            width: 50%;
            height: 200%;
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(25deg);
            transition: all 0.5s;
        }

        .shine-btn:hover::after {
            left: 125%;
        }

        .shop-name-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-100%, -50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            z-index: 1;
            max-width: 40%;
        }

        .shop-logo {
            width: clamp(160px, 18vw, 320px);
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 0 15px rgba(0, 0, 0, 0.6));
            transition: transform 0.6s ease, filter 0.6s ease;
        }

        .shop-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 0 30px rgba(255, 255, 255, 0.7));
        }

        .shop-slogan {
            margin-top: 1rem;
            font-size: clamp(1.2rem, 2vw, 1.6rem);
            font-weight: 500;
            color: #e0f2fe;
            text-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
            letter-spacing: 1px;
            font-family: 'Poppins', sans-serif;
        }

        @media (max-width: 1024px) {
            body {
                justify-content: center;
                padding-right: 0;
                flex-direction: column;
            }

            .shop-name-container {
                position: static;
                transform: none;
                margin-bottom: 2rem;
                margin-top: 2rem;
                max-width: 90%;
            }

            .login-box {
                width: 90%;
                max-width: 400px;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-600 to-teal-500">

    <div class="shop-name-container">
        <img src="assets/web/logo-removebg.png" alt="Hydrange Logo" class="shop-logo">
        <p class="shop-slogan">Nơi mua sắm thông minh – Chọn gì cũng có!</p>
    </div>

    <div class="login-box bg-white/90 backdrop-blur-md shadow-2xl w-96 p-8 rounded-2xl flex flex-col items-center z-10">
        <h1 class="mb-6 text-4xl font-extrabold text-gray-800">Đăng Nhập</h1>
        <form class="flex flex-col w-full items-center">
            <input type="text" id="Username" name="Username" placeholder="Username hoặc Email" class="mb-4 w-11/12 h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />
            <div class="relative w-11/12 mb-4">
                <input type="password" id="password" name="password" placeholder="Mật khẩu" class="w-full h-12 px-4 rounded-xl bg-gray-100 text-gray-800 placeholder-gray-500 focus:ring-2 focus:ring-blue-400 focus:outline-none transition" />
                <button id="togglepass" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700 transition">
                    <svg id="eye_close" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                    <svg id="eye_open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </button>
            </div>
            <div class="w-11/12 text-right mb-4">
                <a href="User_Screen/ForgotPassword_Screen.php" class="text-sm text-black hover:text-white hover:underline transition">Quên mật khẩu?</a>
            </div>

            <button id="button" class="shine-btn bg-blue-600 hover:bg-blue-700 w-11/12 h-12 rounded-xl text-white font-bold transition">Đăng Nhập</button>
        </form>

        <div class="mt-6 text-gray-700">
            <span>Chưa có tài khoản? </span>
            <a href="User_Screen/Register_Screen.php" class="text-blue-600 hover:underline font-medium">Đăng Ký</a>
        </div>
    </div>

    <script>
        const button = document.getElementById('button');
        const Username = document.getElementById('Username');
        const password = document.getElementById('password');
        const togglepass = document.getElementById('togglepass');
        const eye_close = document.getElementById('eye_close');
        const eye_open = document.getElementById('eye_open');

        togglepass.addEventListener('click', () => {
            const isHidden = password.type === "password";
            password.type = isHidden ? "text" : "password";
            eye_close.classList.toggle('hidden', !isHidden);
            eye_open.classList.toggle('hidden', isHidden);
        });

        button.addEventListener('click', async (e) => {
            e.preventDefault();

            if (!Username.value || !password.value) {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu thông tin!',
                    text: 'Vui lòng nhập Username/Email và Mật khẩu.',
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true
                });
                return;
            }

            try {
                // Sửa đường dẫn fetch login
                const res = await fetch("Config/login.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: new URLSearchParams({
                        Username: Username.value,
                        password: password.value
                    })
                });

                if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
                const data = await res.json();

                await Swal.fire({
                    icon: data.success ? 'success' : 'error',
                    title: data.message,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: data.success ? 1500 : 3000,
                    toast: true
                });

                if (data.success) {
                    // Sửa đường dẫn chuyển hướng sau login
                    if (data.role === 'admin' || data.role === 'manager') {
                        window.location.href = 'Admin_Screen/Admin_Dashboard.php';
                    } else {
                        window.location.href = 'User_Screen/Home_Screen.php';
                    }
                }
            } catch (error) {
                console.error("Lỗi Đăng Nhập:", error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối!',
                    text: 'Không thể kết nối đến server.',
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    toast: true
                });
            }
        });
    </script>
</body>

</html>