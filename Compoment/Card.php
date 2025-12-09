<div class="group relative bg-white rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-2 border border-gray-100 overflow-hidden transition-all duration-300 w-full max-w-[280px] flex flex-col h-full">
    
    <span class="absolute top-3 left-3 bg-red-500 text-white text-[10px] font-extrabold px-2 py-1 rounded-md z-10 shadow-sm tracking-wide">HOT</span>

    <div class="relative h-48 w-full bg-gray-50 p-4 overflow-hidden flex items-center justify-center group-hover:bg-blue-50/30 transition-colors">
        <a href="Detail.php?id=<?= $row['id'] ?>" class="block w-full h-full">
            <img src="../<?= htmlspecialchars($row['image']) ?>" 
                 alt="<?= htmlspecialchars($row['name']) ?>" 
                 class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-110">
        </a>
        
        <div class="absolute bottom-0 inset-x-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300 bg-white/90 backdrop-blur-sm border-t border-gray-100">
            <button onclick="buyNow(<?= $row['id'] ?>, <?= $row['price'] ?>)" 
                    class="bg-blue-600 text-white w-full py-2 rounded-lg font-bold text-xs hover:bg-blue-700 shadow-md flex items-center justify-center gap-2 transition-colors">
                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
            </button>
        </div>
    </div>

    <div class="p-4 flex flex-col flex-1">
        <div class="text-[10px] text-blue-500 mb-1 uppercase tracking-wider font-bold truncate">
            <?= htmlspecialchars($row['category'] ?? 'Sản phẩm') ?>
        </div>
        
        <a href="Detail.php?id=<?= $row['id'] ?>" class="block mb-2">
            <h3 class="font-bold text-gray-800 text-sm line-clamp-2 leading-tight group-hover:text-blue-600 transition-colors h-10" title="<?= htmlspecialchars($row['name']) ?>">
                <?= htmlspecialchars($row['name']) ?>
            </h3>
        </a>
        
        <div class="mt-auto pt-2 border-t border-gray-50 flex items-end justify-between">
            <div class="flex flex-col">
                <span class="text-xs text-gray-400 line-through"><?= number_format($row['price'] * 1.2) ?>₫</span>
                <span class="text-base font-extrabold text-red-600 leading-none"><?= number_format($row['price']) ?>₫</span>
            </div>
            <div class="flex items-center gap-0.5 text-yellow-400 text-[10px]">
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star"></i>
                <i class="fas fa-star-half-alt"></i>
            </div>
        </div>
    </div>
</div>
