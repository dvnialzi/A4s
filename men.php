<?php
session_start();
require 'db.php';
include 'includes/header.php'; 
?>

<style>
    .catalog-hero { position: relative; height: 40vh; background-color: #111; background-image: url('images/item-1.jpg'); background-size: cover; background-position: center 20%; display: flex; align-items: center; justify-content: center; margin-top: -1px; }
    .catalog-hero::after { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.4); z-index: 1; }
    .catalog-hero-content { position: relative; z-index: 10; text-align: center; }
    .catalog-title { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: clamp(3rem, 6vw, 5rem); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #fff !important; margin: 0; }
    .filter-bar { border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: flex-end; }
    .filter-count { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 500; text-transform: uppercase; color: #888; font-size: 0.9rem; letter-spacing: 1px; }
    .product-card { text-align: left; margin-bottom: 50px; display: block; color: inherit; text-decoration: none; transition: 0.3s; }
    .product-img-container { position: relative; background: #f4f4f4; width: 100%; padding-top: 120%; overflow: hidden; margin-bottom: 15px; }
    .main-shoe-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
    .product-img-container:hover .main-shoe-img { transform: scale(1.08); }
    .variant-thumbnails { display: flex; gap: 8px; margin-bottom: 12px; min-height: 30px; }
    .variant-thumb { width: 30px; height: 30px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
    .variant-thumb:hover, .variant-thumb.active { border-color: #000; box-shadow: 0 0 0 1px #000; }
    .product-title { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: 1.3rem; font-weight: 700; margin: 0 0 5px 0; text-transform: uppercase; }
    .product-category { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .product-price { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 1.1rem; font-weight: 500; color: #111; }
    
    body.dark-mode .filter-bar { border-bottom-color: #333; }
    body.dark-mode .product-img-container { background: #1a1a1a; }
    body.dark-mode .product-price { color: #fff; }
    body.dark-mode .variant-thumb { border-color: #444; }
    body.dark-mode .variant-thumb:hover, body.dark-mode .variant-thumb.active { border-color: #fff; box-shadow: 0 0 0 1px #fff; }
</style>

<div class="catalog-hero">
    <div class="catalog-hero-content">
        <h1 class="catalog-title">Men's Footwear</h1>
    </div>
</div>

<div class="container-fluid px-5 py-5 mt-4">
    
    <?php
    $prod_sql = "SELECT * FROM product WHERE name LIKE '%Men%' OR category = 'Men' ORDER BY prodID DESC";
    $prod_res = $conn->query($prod_sql);
    
    if (!$prod_res) {
        die("<div class='container py-5 text-center'><h3>Database Error: " . $conn->error . "</h3><p>Please check your product table columns.</p></div>");
    }
    
    $total_items = $prod_res->num_rows;
    ?>

    <div class="filter-bar">
        <div class="filter-count"><?php echo $total_items; ?> Items Found</div>
        <div class="filter-count" style="cursor: pointer;"><i class="icon-filter"></i> Refine</div>
    </div>

    <div class="row">
        <?php
        if($total_items > 0) {
            while($p = $prod_res->fetch_assoc()) {
                $prodID = $p['prodID'];
                $img = !empty($p['image']) ? $p['image'] : 'images/item-1.jpg';
                $imgTop = !empty($p['image_alt']) ? $p['image_alt'] : $img;
                $price = "RM " . number_format($p['price'], 2);

                // SMART STOCK FILTER
                $stock = 0;
                if (isset($p['stock_quantity'])) { $stock = max($stock, (int)$p['stock_quantity']); }
                if (isset($p['quantity'])) { $stock = max($stock, (int)$p['quantity']); }
                if (isset($p['stock'])) { $stock = max($stock, (int)$p['stock']); }
                if (isset($p['qty'])) { $stock = max($stock, (int)$p['qty']); }
                
                $is_out_of_stock = ($stock <= 0);
                $gray_filter = $is_out_of_stock ? "filter: grayscale(100%); opacity: 0.6;" : "";
                ?>
                
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="product-card">
                        
                        <a href="product-detail.php?id=<?php echo $prodID; ?>" style="display:block;">
                            <div class="product-img-container" 
                                 onmouseover="swapToTopView(this)" 
                                 onmouseout="swapToSideView(this)">
                                <img src="<?php echo htmlspecialchars($img); ?>" 
                                    data-side="<?php echo htmlspecialchars($img); ?>" 
                                    data-top="<?php echo htmlspecialchars($imgTop); ?>" 
                                    class="main-shoe-img" 
                                    style="<?php echo $gray_filter; ?>"
                                    alt="<?php echo htmlspecialchars($p['name']); ?>">
                            </div>
                        </a>

                        <div class="variant-thumbnails" onmouseleave="resetToOriginal(this)">
                            <?php
                            $var_sql = "SELECT * FROM product_variants WHERE product_id = $prodID LIMIT 5";
                            $var_res = $conn->query($var_sql);
                            if ($var_res && $var_res->num_rows > 0) {
                                while($variant = $var_res->fetch_assoc()) {
                                    echo "<img src='".htmlspecialchars($variant['img_thumb'])."' 
                                               class='variant-thumb' 
                                               title='".htmlspecialchars($variant['color_name'])."'
                                               onmouseover=\"changeActiveVariant(this, '".htmlspecialchars($variant['img_side'])."', '".htmlspecialchars($variant['img_top'])."')\">";
                                }
                            }
                            ?>
                        </div>

                        <a href="product-detail.php?id=<?php echo $prodID; ?>" style="text-decoration:none; color:inherit;">
                            <div class="product-category">Performance</div>
                            <h3 class="product-title"><?php echo htmlspecialchars($p['name']); ?></h3>
                            <div class="product-price">
                                <?php echo $price; ?>
                                <?php if($is_out_of_stock): ?>
                                    <br><span style="color: #d90429; font-size: 0.9rem; font-weight: 700; letter-spacing: 1px;">OUT OF STOCK</span>
                                <?php endif; ?>
                            </div>
                        </a>

                    </div>
                </div>

                <?php
            } 
        } else {
            echo "<div class='col-12 py-5 text-center'><h3 style='font-family: \"Helvetica Neue\", sans-serif; color: #888; font-weight: 400;'>No gear found in this category.</h3></div>";
        }
        ?>
    </div>
</div>

<script>
    function changeActiveVariant(element, sideImgPath, topImgPath) {
        let card = element.closest('.product-card');
        let mainImg = card.querySelector('.main-shoe-img');
        if (!mainImg.dataset.baseSide) {
            mainImg.dataset.baseSide = mainImg.dataset.side;
            mainImg.dataset.baseTop = mainImg.dataset.top;
        }
        mainImg.src = sideImgPath;
        mainImg.dataset.side = sideImgPath;
        mainImg.dataset.top = topImgPath;
        let thumbs = card.querySelectorAll('.variant-thumb');
        thumbs.forEach(t => t.classList.remove('active'));
        element.classList.add('active');
    }

    function resetToOriginal(container) {
        let card = container.closest('.product-card');
        let mainImg = card.querySelector('.main-shoe-img');
        if (mainImg.dataset.baseSide) {
            mainImg.src = mainImg.dataset.baseSide;
            mainImg.dataset.side = mainImg.dataset.baseSide;
            mainImg.dataset.top = mainImg.dataset.baseTop;
        }
        let thumbs = card.querySelectorAll('.variant-thumb');
        thumbs.forEach(t => t.classList.remove('active'));
    }

    function swapToTopView(container) {
        let mainImg = container.querySelector('.main-shoe-img');
        if (mainImg.dataset.top && mainImg.dataset.top !== '') { mainImg.src = mainImg.dataset.top; }
    }

    function swapToSideView(container) {
        let mainImg = container.querySelector('.main-shoe-img');
        if (mainImg.dataset.side && mainImg.dataset.side !== '') { mainImg.src = mainImg.dataset.side; }
    }
</script>

<?php include 'includes/footer.php'; ?>