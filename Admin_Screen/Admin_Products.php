<?php
session_start();
include '../Config/Database.php';

$role = $_SESSION['role'] ?? 'user';
if (!in_array($role, ['admin', 'manager'])) {
    header("Location: ../User_Screen/Home_Screen.php");
    exit;
}

$db = Database::getInstance()->getConnection();

// --- 1. XỬ LÝ TÌM KIẾM & PHÂN TRANG ---
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Trang hiện tại, tối thiểu là 1
$limit = 10; // Số sản phẩm mỗi trang
$offset = ($page - 1) * $limit;

// Đếm tổng số sản phẩm (để tính số trang)
$countSql = "SELECT COUNT(*) as total FROM items WHERE name LIKE ?";
$searchTerm = "%$search%";
$stmtC = $db->prepare($countSql);
$stmtC->bind_param("s", $searchTerm);
$stmtC->execute();
$totalRows = $stmtC->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$stmtC->close();

// Lấy danh sách sản phẩm theo trang
$sql = "SELECT * FROM items WHERE name LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$stmt->bind_param("sii", $searchTerm, $limit, $offset);
$stmt->execute();
$products = $stmt->get_result();
//$stmt->close(); // Không đóng ngay để dùng result bên dưới

// Lấy danh mục để fill vào form thêm/sửa
$categories = $db->query("SELECT DISTINCT category FROM items ORDER BY category ASC");
$cat_options = [];
while ($cat = $categories->fetch_assoc()) $cat_options[] = $cat['category'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Quản Lý Sản Phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-100 min-h-screen font-sans">
    <?php include '../Compoment/Admin_Menu.php'; ?>

    <div class="ml-64 p-8 transition-all duration-300">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">Sản Phẩm & Kho 📦</h1>
            <button onclick="document.getElementById('add-form-container').classList.toggle('hidden')"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fas fa-plus"></i> Thêm Sản Phẩm Mới
            </button>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex justify-between items-center">
            <form method="GET" class="flex gap-2 w-full md:w-1/2">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Tìm kiếm theo tên sản phẩm..."
                    class="w-full border p-2 rounded-lg text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-search"></i>
                </button>
                <?php if ($search): ?>
                    <a href="Admin_Products.php" class="bg-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-300 transition flex items-center">Hủy</a>
                <?php endif; ?>
            </form>
            <div class="text-sm text-gray-500">
                Hiển thị <b><?= $products->num_rows ?></b> / <b><?= $totalRows ?></b> kết quả
            </div>
        </div>

        <div id="add-form-container" class="hidden bg-white p-6 rounded-xl shadow-md mb-8 border border-blue-100">
            <h2 class="text-lg font-bold mb-4 text-blue-600 border-b pb-2">Nhập thông tin sản phẩm</h2>
            <form id="add-product-form" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <input type="text" name="name" placeholder="Tên sản phẩm" class="input-field" required>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" name="price" placeholder="Giá tiền" class="input-field" required>
                    <input type="number" name="stock" placeholder="Tồn kho" class="input-field" value="100" required>
                </div>
                <input type="text" name="brand" placeholder="Thương hiệu" class="input-field">

                <div class="col-span-1 relative">
                    <select id="category-select" class="input-field" onchange="checkCategory(this)">
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($cat_options as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                        <option value="new">+ Nhập mới...</option>
                    </select>
                    <input type="text" id="category-input" name="category" placeholder="Nhập tên danh mục..." class="input-field mt-2 hidden">
                </div>

                <div class="col-span-2 md:col-span-1 flex flex-col items-center justify-center p-2 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                    <label class="block text-xs font-bold text-gray-500 mb-2 w-full text-left">Hình ảnh sản phẩm</label>

                    <div class="relative w-full h-32 group cursor-pointer overflow-hidden rounded-lg border bg-white" onclick="document.getElementById('upload_prod_img').click()">
                        <img id="preview_img_add" src="https://via.placeholder.com/300x200?text=Bấm+để+chọn+ảnh"
                            class="w-full h-full object-contain p-1 transition-transform duration-300 group-hover:scale-105">

                        <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-camera text-white text-2xl"></i>
                        </div>

                        <div id="loading_img_add" class="absolute inset-0 bg-white/90 flex items-center justify-center hidden">
                            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
                        </div>
                    </div>

                    <input type="file" id="upload_prod_img" accept="image/*" class="hidden" onchange="uploadProductImage(this, 'preview_img_add', 'prod_image_url', 'loading_img_add')">
                    <input type="hidden" id="prod_image_url" name="image">
                </div>
                <textarea name="description" placeholder="Mô tả chi tiết" class="input-field col-span-2 h-24 pt-2"></textarea>

                <div class="col-span-2 flex justify-end gap-3 mt-2">
                    <button type="button" onclick="document.getElementById('add-form-container').classList.add('hidden')" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Hủy</button>
                    <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 shadow">Xác Nhận Thêm</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="p-4 border-b">Ảnh</th>
                        <th class="p-4 border-b">Tên Sản Phẩm</th>
                        <th class="p-4 border-b">Danh Mục</th>
                        <th class="p-4 border-b">Giá Bán</th>
                        <th class="p-4 border-b">Kho</th>
                        <th class="p-4 border-b text-center">Hành Động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($products->num_rows > 0): ?>
                        <?php while ($item = $products->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50/50 transition duration-150">
                                <td class="p-4 w-20"><img src="<?= (strpos($item['image'], 'http') === 0 ? $item['image'] : '../' . htmlspecialchars($item['image'])) ?>" class="w-12 h-12 object-contain bg-white rounded border p-1"></td>
                                <td class="p-4 font-semibold text-slate-700 max-w-[200px] truncate" title="<?= htmlspecialchars($item['name']) ?>">
                                    <?= htmlspecialchars($item['name']) ?>
                                    <div class="text-xs text-gray-400 font-normal mt-0.5">#<?= $item['id'] ?> - <?= htmlspecialchars($item['brand']) ?></div>
                                </td>
                                <td class="p-4"><span class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600 font-bold"><?= htmlspecialchars($item['category']) ?></span></td>
                                <td class="p-4 font-bold text-green-600"><?= number_format($item['price']) ?>đ</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <input type="number" id="stock-<?= $item['id'] ?>" value="<?= $item['stock'] ?>" class="w-16 p-1 border rounded text-center text-sm focus:border-blue-500 outline-none">
                                        <button onclick="updateStock(<?= $item['id'] ?>)" class="text-blue-500 hover:bg-blue-100 p-1.5 rounded transition"><i class="fas fa-save"></i></button>
                                    </div>
                                </td>
                                <td class="p-4 flex justify-center gap-2">
                                    <button onclick="openEditModal(<?= $item['id'] ?>)" class="text-yellow-500 hover:bg-yellow-50 p-2 rounded-full transition" title="Sửa"><i class="fas fa-pen"></i></button>
                                    <button onclick="deleteProduct(<?= $item['id'] ?>)" class="text-red-500 hover:bg-red-50 p-2 rounded-full transition" title="Xóa"><i class="fas fa-trash-alt"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500 italic">Không tìm thấy sản phẩm nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-6">
                <nav class="flex gap-2 bg-white p-2 rounded-lg shadow-sm border">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100 text-gray-600"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                            class="w-8 h-8 flex items-center justify-center rounded font-bold text-sm <?= $i == $page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100 text-gray-600"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>

    </div>

    <div id="edit-modal" class="fixed inset-0 bg-black/50 hidden z-[60] flex justify-center items-center backdrop-blur-sm">
        <div class="bg-white p-6 rounded-2xl shadow-2xl w-[600px] max-h-[90vh] overflow-y-auto animate-bounce-slow">
            <h2 class="text-2xl font-bold mb-4 text-slate-800">Cập Nhật Sản Phẩm</h2>
            <form id="edit-product-form" class="grid grid-cols-2 gap-4">
                <input type="hidden" name="id" id="edit-id">
                <div class="col-span-2"><label class="text-xs font-bold text-gray-500">Tên SP</label><input type="text" name="name" id="edit-name" class="input-field w-full"></div>
                <div><label class="text-xs font-bold text-gray-500">Giá</label><input type="number" name="price" id="edit-price" class="input-field w-full"></div>
                <div><label class="text-xs font-bold text-gray-500">Thương hiệu</label><input type="text" name="brand" id="edit-brand" class="input-field w-full"></div>

                <div class="col-span-2">
                    <label class="text-xs font-bold text-gray-500">Danh mục</label>
                    <select name="category" id="edit-category" class="input-field w-full">
                        <?php foreach ($cat_options as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-span-2 flex flex-col items-center justify-center p-2 border border-dashed border-gray-300 rounded-lg bg-gray-50">
                    <label class="block text-xs font-bold text-gray-500 mb-2 w-full text-left">Hình ảnh sản phẩm</label>
                    <div class="relative w-full h-32 group cursor-pointer overflow-hidden rounded-lg border bg-white" onclick="document.getElementById('upload_prod_img_edit').click()">
                        <img id="preview_img_edit" src="https://via.placeholder.com/300x200?text=Bấm+để+chọn+ảnh"
                            class="w-full h-full object-contain p-1 transition-transform duration-300 group-hover:scale-105">
                        <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <i class="fas fa-camera text-white text-2xl"></i>
                        </div>
                        <div id="loading_img_edit" class="absolute inset-0 bg-white/90 flex items-center justify-center hidden">
                            <i class="fas fa-spinner fa-spin text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <input type="file" id="upload_prod_img_edit" accept="image/*" class="hidden" onchange="uploadProductImage(this, 'preview_img_edit', 'prod_image_url_edit', 'loading_img_edit')">
                    <input type="hidden" id="prod_image_url_edit" name="image">
                </div>
                <div class="col-span-2"><label class="text-xs font-bold text-gray-500">Mô tả</label><textarea name="description" id="edit-description" class="input-field w-full h-20"></textarea></div>

                <div class="col-span-2 flex justify-end gap-3 mt-4 border-t pt-4">
                    <button type="button" onclick="document.getElementById('edit-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 rounded-lg font-bold text-gray-600 hover:bg-gray-300">Đóng</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 shadow">Lưu Thay Đổi</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .input-field {
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.5rem;
            outline: none;
            transition: all 0.2s;
            width: 100%;
        }

        .input-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>

    <script>
        function checkCategory(select) {
            const input = document.getElementById('category-input');
            if (select.value === 'new') {
                select.classList.add('hidden');
                input.classList.remove('hidden');
                input.value = '';
                input.focus();
            } else {
                input.value = select.value;
            }
        }

        // Add Product
        document.getElementById('add-product-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const catInp = document.getElementById('category-input');
            const catSel = document.getElementById('category-select');
            if (catInp.classList.contains('hidden')) catInp.value = catSel.value;

            const res = await fetch('../Config/add_product.php', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const data = await res.json();
            data.success ? Swal.fire('Thành công', 'Đã thêm sản phẩm', 'success').then(() => location.reload()) : Swal.fire('Lỗi', data.message, 'error');
        });

        // Edit Product
        async function openEditModal(id) {
            const res = await fetch(`../Config/get_product.php?id=${id}`);
            const data = await res.json();
            if (data.success) {
                const p = data.data;
                document.getElementById('edit-id').value = p.id;
                document.getElementById('edit-name').value = p.name;
                document.getElementById('edit-price').value = p.price;
                document.getElementById('edit-brand').value = p.brand;
                document.getElementById('edit-category').value = p.category;
                document.getElementById('preview_img_edit').src = (p.image.startsWith('http') ? p.image : '../' + p.image);
                document.getElementById('prod_image_url_edit').value = p.image;
                document.getElementById('edit-description').value = p.description;
                document.getElementById('edit-modal').classList.remove('hidden');
            }
        }
        document.getElementById('edit-product-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const res = await fetch('../Config/edit_product.php', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const data = await res.json();
            data.success ? Swal.fire('Xong', 'Đã cập nhật', 'success').then(() => location.reload()) : Swal.fire('Lỗi', data.message, 'error');
        });

        // Update Stock
        async function updateStock(id) {
            const stock = document.getElementById(`stock-${id}`).value;
            const res = await fetch('../Config/update_stock.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${id}&stock=${stock}`
            });
            const data = await res.json();
            data.success ? Swal.fire({
                icon: 'success',
                title: 'Đã cập nhật kho',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            }) : Swal.fire('Lỗi', data.message, 'error');
        }

        // Delete Product
        async function deleteProduct(id) {
            if (await Swal.fire({
                    title: 'Xóa vĩnh viễn?',
                    text: 'Hành động này không thể hoàn tác!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33'
                }).then(r => r.isConfirmed)) {
                const res = await fetch(`../Config/delete_product.php?id=${id}`);
                const data = await res.json();
                if (data.success) location.reload();
                else Swal.fire('Lỗi', data.message, 'error');
            }
        }
        
        async function uploadProductImage(input, previewId, hiddenInputId, loadingId) {
            const file = input.files[0];
            if (!file) return;

            // 1. Hiện loading
            document.getElementById(loadingId).classList.remove('hidden');

            const formData = new FormData();
            formData.append('file', file);

            try {
                // 2. Gửi file lên server
                const res = await fetch('../Config/upload_product_image.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                // 3. Ẩn loading
                document.getElementById(loadingId).classList.add('hidden');

                if (data.success) {
                    // 4. Thành công: Hiện ảnh preview và gán link vào input hidden
                    document.getElementById(previewId).src = data.url;
                    document.getElementById(hiddenInputId).value = data.url; // Đây là giá trị sẽ gửi đi khi bấm Lưu

                    // Reset input file để có thể chọn lại ảnh đó nếu muốn
                    input.value = '';
                } else {
                    Swal.fire('Lỗi', data.message, 'error');
                }
            } catch (e) {
                console.error(e);
                document.getElementById(loadingId).classList.add('hidden');
                Swal.fire('Lỗi', 'Không thể kết nối đến server', 'error');
            }
        }
    </script>
</body>

</html>