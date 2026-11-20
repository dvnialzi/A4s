<?php
session_start();
require 'db.php'; 
include 'includes/header.php'; 
?>

<style>
    /* Cinematic Edge-to-Edge Hero */
    .hero-cinematic {
        position: relative;
        height: 85vh;
        background-image: url('images/item-1.jpg'); 
        background-size: cover;
        background-position: center;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: -1px;
    }
    .hero-cinematic::after {
        content: '';
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.25);
        z-index: 1;
    }
    .hero-content {
        position: relative;
        z-index: 10;
        text-align: center;
        color: #fff;
        padding: 0 20px;
    }
    .hero-title {
        font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif;
        font-size: clamp(4rem, 8vw, 7rem);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        line-height: 1;
        margin-bottom: 20px;
        color: #fff !important;
        text-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    .hero-subtitle {
        font-family: 'Helvetica Neue', Helvetica, sans-serif;
        font-size: 1.2rem;
        font-weight: 500;
        letter-spacing: 1px;
        margin-bottom: 40px;
        color: #f8f9fa;
        text-transform: uppercase;
    }
    
    .btn-hero { background: #fff; color: #000 !important; padding: 16px 45px; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; border: 2px solid #fff; transition: all 0.3s ease; text-decoration: none; display: inline-block; margin: 0 10px; }
    .btn-hero:hover { background: #000; color: #fff !important; border-color: #000; text-decoration: none; }
    .btn-hero-outline { background: transparent; color: #fff !important; border: 2px solid #fff; }
    .btn-hero-outline:hover { background: #fff; color: #000 !important; }

    /* AI MATCHMAKER UI */
    .ai-search-box { background: linear-gradient(135deg, #111, #1a1a1a); color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: relative; overflow: hidden; margin-top: -60px; z-index: 20; border: 1px solid #333; }
    .ai-search-box::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.05) 10%, transparent 60%); pointer-events: none; }
    .ai-input-wrap { display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap; }
    .ai-input { flex-grow: 1; padding: 20px; font-family: 'Helvetica Neue', sans-serif; font-size: 1.1rem; border: 2px solid #444; background: #000; color: #fff; border-radius: 4px; transition: 0.3s; min-width: 250px; }
    .ai-input:focus { border-color: #88c8bc; outline: none; box-shadow: 0 0 15px rgba(136, 200, 188, 0.2); }
    .btn-ai { background: #fff; color: #000; border: none; padding: 0 40px; font-family: 'Futura Condensed', sans-serif; font-size: 1.4rem; font-weight: 700; text-transform: uppercase; border-radius: 4px; cursor: pointer; transition: 0.3s; letter-spacing: 1px; min-height: 65px; }
    .btn-ai:hover { background: #88c8bc; color: #000; }

    /* Product Grid */
    .section-title { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: 3rem; font-weight: 800; text-align: left; margin-bottom: 40px; text-transform: uppercase; letter-spacing: -1px; border-bottom: 2px solid #000; padding-bottom: 15px; }
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

    /* Dark Mode specific overrides */
    body.dark-mode .section-title { border-bottom-color: #333; }
    body.dark-mode .product-img-container { background: #1a1a1a; }
    body.dark-mode .product-price { color: #fff; }
    body.dark-mode .hero-cinematic::after { background: rgba(0,0,0,0.5); }
    body.dark-mode .variant-thumb { border-color: #444; }
    body.dark-mode .variant-thumb:hover, body.dark-mode .variant-thumb.active { border-color: #fff; box-shadow: 0 0 0 1px #fff; }
</style>

<div class="hero-cinematic">
    <div class="hero-content">
        <h1 class="hero-title">Tomorrow Is Yours</h1>
        <p class="hero-subtitle">Engineered for the elements. Designed for the streets.</p>
        <div>
            <a href="men.php" class="btn-hero">Shop Men</a>
            <a href="women.php" class="btn-hero btn-hero-outline">Shop Women</a>
        </div>
    </div>
</div>

<div class="container-fluid px-4 px-md-5">
    <div class="row justify-content-center">
        <div class="col-xl-9 col-lg-11">
            <div class="ai-search-box">
                <h3 style="font-family:'Futura Condensed', sans-serif; font-weight:800; text-transform:uppercase; font-size: 2rem; margin-bottom: 5px;">
                    ✨ Don't know what to buy?
                </h3>
                <p style="font-family:'Helvetica Neue', sans-serif; color: #aaa; font-size: 1.1rem;">
                    Type the desired shoe you want, and our smart assistant will find the perfect match.
                </p>
                <form action="ai_recommender.php" method="GET" class="ai-input-wrap">
                    <input type="text" name="ai_query" class="ai-input" placeholder="e.g. blue shoe that is below rm100 size 8 good for hiking" required>
                    <button type="submit" class="btn-ai">Ask AI</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-5 py-5 mt-4">
    <h2 class="section-title">Latest Innovations</h2>
    <div class="row">
        <?php
        $prod_sql = "SELECT * FROM product ORDER BY prodID DESC LIMIT 8";
        $prod_res = $conn->query($prod_sql);
        
        if($prod_res && $prod_res->num_rows > 0) {
            while($p = $prod_res->fetch_assoc()) {
                $prodID = $p['prodID'];
                $img = !empty($p['image']) ? $p['image'] : 'images/item-1.jpg';
                $imgTop = !empty($p['image_alt']) ? $p['image_alt'] : $img;
                $price = "RM " . number_format($p['price'], 2);
                
                // SMART STOCK FILTER: Finds the highest real stock value to bypass dead columns!
                $stock = 0;
                if (isset($p['stock_quantity'])) { $stock = max($stock, (int)$p['stock_quantity']); }
                if (isset($p['quantity'])) { $stock = max($stock, (int)$p['quantity']); }
                if (isset($p['stock'])) { $stock = max($stock, (int)$p['stock']); }
                if (isset($p['qty'])) { $stock = max($stock, (int)$p['qty']); }
                
                $is_out_of_stock = ($stock <= 0);
                $gray_filter = $is_out_of_stock ? "filter: grayscale(100%); opacity: 0.6;" : "";
                
                $category = "Performance Footwear";
                if(stripos($p['name'], 'women') !== false) $category = "Women's Footwear";
                if(stripos($p['name'], 'men') !== false) $category = "Men's Footwear";

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
                            <div class="product-category"><?php echo $category; ?></div>
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
            echo "<div class='col-12'><p>No products available at the moment.</p></div>";
        }
        ?>
    </div>
    
    <div class="row mt-4 mb-5">
        <div class="col-12 text-center">
            <a href="advanced_search.php" class="btn-hero" style="border: 1px solid #000;">View All Gear</a>
        </div>
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