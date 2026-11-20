<?php
session_start();
require 'db.php';

$raw_query = isset($_GET['ai_query']) ? $_GET['ai_query'] : '';
$clean_query = strtolower(trim($raw_query));

// ==========================================
// 1. AI NATURAL LANGUAGE PARSER
// ==========================================
$max_price = 999999; 
$category_filter = '';
$keywords = [];

// A. Price Extraction Regex (Finds "under 150", "below rm200", etc.)
if (preg_match('/(?:under|below|less|cheaper|rm|<|max)\s*(?:than\s*)?(?:rm)?\s*(\d+(?:\.\d+)?)/i', $clean_query, $matches)) {
    $max_price = (float)$matches[1];
    // Strip the price out so it isn't treated as a keyword
    $clean_query = str_replace($matches[0], '', $clean_query);
}

// B. Category/Gender Extraction
if (preg_match('/\b(women|womens|female|girls)\b/i', $clean_query)) {
    $category_filter = 'Women';
    $clean_query = preg_replace('/\b(women|womens|female|girls)\b/i', '', $clean_query);
} elseif (preg_match('/\b(men|mens|male|boys)\b/i', $clean_query)) {
    $category_filter = 'Men';
    $clean_query = preg_replace('/\b(men|mens|male|boys)\b/i', '', $clean_query);
}

// C. Stopword Removal (Filters out conversational filler words)
$stopwords = ['a', 'an', 'the', 'that', 'is', 'for', 'to', 'with', 'shoe', 'shoes', 'i', 'want', 'looking', 'good', 'need', 'some', 'any', 'my', 'size', 'and', 'in', 'of', 'on', 'really', 'can', 'you', 'find'];
$words = preg_split('/[\s,\.]+/', preg_replace('/[^a-z0-9\s]/i', '', $clean_query));

foreach ($words as $w) {
    if (!empty($w) && !in_array($w, $stopwords) && !is_numeric($w)) {
        $keywords[] = $w;
    }
}

// ==========================================
// 2. OPTIMIZED HIGH-SPEED SQL GENERATOR
// ==========================================
// Check if the search_tags column exists so it doesn't break if loaded before the admin panel
$check_tags = $conn->query("SHOW COLUMNS FROM product LIKE 'search_tags'");
$has_tags = ($check_tags && $check_tags->num_rows > 0);

// We no longer need the heavy LEFT JOIN to the variants table!
$sql = "SELECT * FROM product WHERE 1=1 ";

if ($max_price < 999999) {
    $sql .= " AND price <= $max_price ";
}

if ($category_filter !== '') {
    $sql .= " AND (category = '$category_filter' OR name LIKE '%$category_filter%') ";
}

if (count($keywords) > 0) {
    $sql .= " AND (";
    $kw_conditions = [];
    foreach ($keywords as $kw) {
        $safe_kw = $conn->real_escape_string($kw);
        
        if ($has_tags) {
            // HIGH-SPEED SEARCH: Looks at the new Search Tags, Name, and Description
            $kw_conditions[] = "(name LIKE '%$safe_kw%' OR description LIKE '%$safe_kw%' OR search_tags LIKE '%$safe_kw%')";
        } else {
            // Fallback just in case the column hasn't generated yet
            $kw_conditions[] = "(name LIKE '%$safe_kw%' OR description LIKE '%$safe_kw%')";
        }
    }
    // AI requires ALL major keywords to match to ensure high-quality recommendations
    $sql .= implode(" AND ", $kw_conditions);
    $sql .= ")";
}

$sql .= " ORDER BY prodID DESC";
$prod_res = $conn->query($sql);
$total_results = $prod_res ? $prod_res->num_rows : 0;

include 'includes/header.php'; 
?>

<style>
    .ai-hero {
        background: linear-gradient(135deg, #0a0a0a, #1a1a1a);
        padding: 80px 20px 60px 20px;
        text-align: center;
        color: #fff;
        border-bottom: 2px solid #333;
    }
    .ai-hero-title {
        font-family: 'Futura Condensed', 'Oswald', sans-serif;
        font-size: 3.5rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 20px;
        background: -webkit-linear-gradient(#fff, #88c8bc);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .ai-analysis-box {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        padding: 20px;
        max-width: 800px;
        margin: 0 auto;
        font-family: 'Helvetica Neue', sans-serif;
        color: #ccc;
        font-size: 1.1rem;
    }
    .ai-highlight { color: #88c8bc; font-weight: 700; }
    
    /* Store Product Grid Styling */
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

<div class="ai-hero">
    <h1 class="ai-hero-title">AI Recommendations</h1>
    <div class="ai-analysis-box">
        <?php if(empty($raw_query)): ?>
            Please enter a query in the storefront so we can find your perfect match.
        <?php else: ?>
            <strong>I searched for:</strong> 
            <span class="ai-highlight"><?php echo !empty($category_filter) ? $category_filter . "'s" : "Any"; ?></span> gear, 
            matching <span class="ai-highlight">"<?php echo count($keywords) > 0 ? implode(' + ', $keywords) : 'All Styles'; ?>"</span>,
            <?php if($max_price < 999999): ?>
                priced under <span class="ai-highlight">RM <?php echo $max_price; ?></span>.
            <?php else: ?>
                at <span class="ai-highlight">Any Price</span>.
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="container-fluid px-5 py-5 mt-4">
    <div class="filter-bar">
        <div class="filter-count">We found <?php echo $total_results; ?> perfect match(es)</div>
        <div class="filter-count"><a href="store.php" style="color: inherit; text-decoration: none;">&larr; Try Another Search</a></div>
    </div>

    <div class="row">
        <?php
        if($total_results > 0) {
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
            echo "<div class='col-12 py-5 text-center'>
                    <h3 style='font-family: \"Helvetica Neue\", sans-serif; color: #888; font-weight: 400;'>I couldn't find exactly what you were looking for.</h3>
                    <p style='color: #666;'>Try using broader terms or a different price limit.</p>
                  </div>";
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