<?php
// File: Web php/Admin_Screen/Admin_Dashboard.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../Config/Database.php';

// 1. Kiểm tra quyền
$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    header("Location: ../User_Screen/Home_Screen.php");
    exit;
}

$db = Database::getInstance()->getConnection();

// --- 2. LOGIC THỐNG KÊ (TOÀN BỘ THỜI GIAN) ---

// A. Tổng doanh thu (Chỉ tính các đơn KHÔNG bị hủy)
$revenue = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'Đã hủy'")->fetch_assoc()['total'] ?? 0;

// B. Tổng số đơn hàng thành công (Trừ đơn hủy để đồng bộ với doanh thu)
$orders_count = $db->query("SELECT COUNT(*) as total FROM orders WHERE status != 'Đã hủy'")->fetch_assoc()['total'] ?? 0;

// C. Tổng số sản phẩm (Đếm tất cả sản phẩm có trong hệ thống) -> CÁI MỚI THÊM
$products_count = $db->query("SELECT COUNT(*) as total FROM items")->fetch_assoc()['total'] ?? 0;

// D. Tổng số khách hàng (User)
$users_count = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'] ?? 0;

// E. Top 5 sản phẩm bán chạy nhất mọi thời đại
$top_products = $db->query("
    SELECT i.name, SUM(od.quantity) as total_sold
    FROM order_details od
    JOIN items i ON od.item_id = i.id
    JOIN orders o ON od.order_id = o.id
    WHERE o.status != 'Đã hủy'
    GROUP BY i.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$chart_labels = json_encode(array_column($top_products, 'name'));
$chart_data = json_encode(array_column($top_products, 'total_sold'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen font-sans">
    <?php include '../Compoment/Menu.php'; ?>

    <div class="ml-64 p-8 transition-all duration-300">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Tổng Quan Hệ Thống 📊</h1>
                <p class="text-sm text-gray-500 mt-1">Dữ liệu thống kê toàn bộ từ trước đến nay</p>
            </div>
            <span class="text-sm text-gray-500 bg-white px-4 py-2 rounded-lg shadow-sm border font-medium">
                📅 Hôm nay: <?= date('d/m/Y') ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Tổng Doanh Thu</p>
                    <p class="text-2xl font-extrabold text-blue-600"><?= number_format($revenue) ?>₫</p>
                </div>
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fas fa-wallet text-xl"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Đơn Hàng Thực Tế</p>
                    <p class="text-2xl font-extrabold text-emerald-600"><?= number_format($orders_count) ?></p>
                    <span class="text-[10px] text-gray-400">(Không tính đơn hủy)</span>
                </div>
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Tổng Sản Phẩm</p>
                    <p class="text-2xl font-extrabold text-purple-600"><?= number_format($products_count) ?></p>
                    <span class="text-[10px] text-gray-400">(Trong kho)</span>
                </div>
                <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fas fa-box-open text-xl"></i>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between group hover:shadow-md transition">
                <div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-1">Tổng Khách Hàng</p>
                    <p class="text-2xl font-extrabold text-orange-600"><?= number_format($users_count) ?></p>
                </div>
                <div class="p-3 bg-orange-50 text-orange-600 rounded-xl group-hover:bg-orange-600 group-hover:text-white transition">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-700 mb-6 flex items-center gap-2">
                    <i class="fas fa-trophy text-yellow-500"></i> Top 5 Sản Phẩm Bán Chạy Nhất
                </h2>
                <div class="w-full h-[350px]">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-slate-700 mb-4">Chi tiết Top 5</h2>
                <div class="space-y-4">
                    <?php foreach($top_products as $idx => $tp): ?>
                    <div class="flex items-center justify-between border-b border-gray-50 pb-3 last:border-0">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                #<?= $idx + 1 ?>
                            </span>
                            <span class="text-sm font-medium text-gray-700 line-clamp-1" title="<?= $tp['name'] ?>">
                                <?= $tp['name'] ?>
                            </span>
                        </div>
                        <span class="text-sm font-bold text-blue-600"><?= $tp['total_sold'] ?> đã bán</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= $chart_labels ?>,
                datasets: [{
                    label: 'Số lượng đã bán',
                    data: <?= $chart_data ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(139, 92, 246, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderRadius: 8,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 13 },
                        bodyFont: { size: 14, weight: 'bold' }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [4, 4], color: '#f1f5f9' },
                        ticks: { font: { size: 11 } }
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    </script>
</body>
</html>