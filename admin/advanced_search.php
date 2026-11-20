<?php
session_start();
require 'db.php';

// Capture Filter Data
$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';
$genders = isset($_GET['gender']) ? $_GET['gender'] : [];
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : '';
$color = isset($_GET['color']) ? $conn->real_escape_string($_GET['color']) : '';

// Base Query (Joins product with variants to allow color searching)
$sql = "SELECT DISTINCT p.* FROM product p LEFT JOIN product_variants v ON p.prodID = v.product_id WHERE 1=1";

if (!empty($q)) {
    $sql .= " AND (p.name LIKE '%$q%' OR p.description LIKE '%$q%')";
}
if (!empty($genders)) {
    $gender_str = implode("','", array_map([$conn, 'real_escape_string'], $genders));
    $sql .= " AND p.category IN ('$gender_str')";
}
if ($min_price !== '') {
    $sql .= " AND p.price >= $min_price";
}
if ($max_price !== '') {
    $sql .= " AND p.price <= $max_price";
}
if (!empty($color)) {
    $sql .= " AND v.color_name LIKE '%$color%'";
}

$sql .= " ORDER BY p.prodID DESC";
$result = $conn->query($sql);
$total_items = $result ? $result->num_rows : 0;

include 'includes/header.php'; 
?>

<style>
    /* Unique Advanced Search UI */
    .adv-search-container {
        display: flex;
        flex-wrap: wrap;
        margin-top: 40px;
        margin-bottom: 100px;
    }
    
    /* Left Control Panel */
    .control-panel {
        background: #f8f9fa;
        border: 2px solid #000;
        padding: 30px;
        position: sticky;
        top: 100px;
    }
    .panel-title {
        font-family: 'Futura Condensed', sans-serif;
        font-weight: 800;
        font-size: 2rem;
        text-transform: uppercase;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
        margin-bottom: 30px;
    }
    .filter-group {
        margin-bottom: 30px;
    }
    .filter-label {
        font-family: 'Helvetica Neue', sans-serif;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #555;
        margin-bottom: 10px;
        display: block;
    }
    .form-control-sharp {
        border: 1px solid #ccc;
        border-radius: 0;
        padding: 10px;
        width: 100%;
        font-family: 'Helvetica Neue', sans-serif;
        background: #fff;
    }
    .form-control-sharp:focus { border-color: #000; outline: none; }
    
    /* Brutalist Checkboxes */
    .checkbox-brutal {
        display: block;
        margin-bottom: 8px;
        font-family: 'Helvetica Neue', sans-serif;
        font-size: 0.95rem;
        cursor: pointer;
    }
    .checkbox-brutal input {
        margin-right: 8px;
        accent-color: #000;
        transform: scale(1.2);
    }
    
    .btn-filter {
        background: #000;
        color: #fff;
        border: none;
        width: 100%;
        padding: 15px;
        font-family: 'Futura Condensed', sans-serif;
        font-size: 1.2rem;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 2px;
        transition: 0.3s;
    }
    .btn-filter:hover { background: #d90429; }

    /* Product Grid Styling (Reused from store.php) */
    .product-card { text-align: left; margin-bottom: 50px; display: block; color: inherit; text-decoration: none; transition: 0.3s; }
    .product-img-container { position: relative; background: #f4f4f4; width: 100%; padding-top: 120%; overflow: hidden; margin-bottom: 15px; }
    .main-shoe-img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
    .product-img-container:hover .main-shoe-img { transform: scale(1.08); }
    .product-title { font-family: 'Futura Condensed', sans-serif; font-size: 1.3rem; font-weight: 700; margin: 0 0 5px 0; text-transform: uppercase; }
    .product-category { font-family: 'Helvetica Neue', sans-serif; font-size: 0.85rem; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .product-price { font-family: 'Helvetica Neue', sans-serif; font-size: 1.1rem; font-weight: 500; color: #111; }
    
    /* Color Variants */
    .variant-thumbnails { display: flex; gap: 8px; margin-bottom: 12px; min-height: 30px; }
    .variant-thumb { width: 30px; height: 30px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; transition: all 0.2s ease; }
    .variant-thumb:hover, .variant-thumb.active { border-color: #000; box-shadow: 0 0 0 1px #000; }

    /* Dark Mode */
    body.dark-mode .control-panel { background: #111; border-color: #333; }
    body.dark-mode .panel-title { border-color: #333; color: #fff; }
    body.dark-mode .form-control-sharp { background: #1a1a1a; border-color: #444; color: #fff; }
    body.dark-mode .checkbox-brutal { color: #ccc; }
    body.dark-mode .product-img-container { background: #1a1a1a; }
    body.dark-mode .product-price { color: #fff; }
    body.dark-mode .variant-thumb { border-color: #444; }
    body.dark-mode .variant-thumb:hover { border-color: #fff; box-shadow: 0 0 0 1px #fff; }
</style>

<div class="container-fluid px-4 px-md-5">
    <div class="row adv-search-container">
        
        <!-- CONTROL PANEL (Filters) -->
        <div class="col-lg-3 mb-5 pe-lg-5">
            <div class="control-panel">
                <h2 class="panel-title">Filters</h2>
                <form action="advanced_search.php" method="GET">
                    
                    <div class="filter-group">
                        <label class="filter-label">Search Query</label>
                        <input type="text" name="q" class="form-control-sharp" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search gear...">
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Gender / Category</label>
                        <label class="checkbox-brutal">
                            <input type="checkbox" name="gender[]" value="Men" <?php if(in_array('Men', $genders)) echo 'checked'; ?>> Men's
                        </label>
                        <label class="checkbox-brutal">
                            <input type="checkbox" name="gender[]" value="Women" <?php if(in_array('Women', $genders)) echo 'checked'; ?>> Women's
                        </label>
                        <label class="checkbox-brutal">
                            <input type="checkbox" name="gender[]" value="Unisex" <?php if(in_array('Unisex', $genders)) echo 'checked'; ?>> Unisex
                        </label>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Price Range (RM)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" name="min_price" class="form-control-sharp" placeholder="Min" value="<?php echo $min_price; ?>">
                            <span>-</span>
                            <input type="number" name="max_price" class="form-control-sharp" placeholder="Max" value="<?php echo $max_price; ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Size (UK)</label>
                        <select name="size" class="form-control-sharp">
                            <option value="">Any Size</option>
                            <?php 
                            $sizes = ['7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11'];
                            foreach($sizes as $s) {
                                // UI Only: Universal standard stock applies to all products currently
                                $sel = (isset($_GET['size']) && $_GET['size'] == $s) ? 'selected' : '';
                                echo "<option value='$s' $sel>UK $s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Specific Color</label>
                        <select name="color" class="form-control-sharp">
                            <option value="">All Colors</option>
                            <?php
                            // Fetch all unique colors available in the database
                            $color_sql = "SELECT DISTINCT color_name FROM product_variants ORDER BY color_name ASC";
                            $c_res = $conn->query($color_sql);
                            if ($c_res && $c_res->num_rows > 0) {
                                while($c = $c_res->fetch_assoc()) {
                                    $sel = ($color == $c['color_name']) ? 'selected' : '';
                                    echo "<option value='".htmlspecialchars($c['color_name'])."' $sel>".htmlspecialchars($c['color_name'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-filter">Apply Filters</button>
                    <a href="advanced_search.php" class="d-block text-center text-muted mt-3 small" style="text-decoration:underline;">Clear All</a>
                </form>
            </div>
        </div>

        <!-- RESULTS GRID -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-end border-bottom border-dark pb-2 mb-4">
                <h3 class="m-0" style="font-family: 'Futura Condensed', sans-serif; text-transform:uppercase; font-weight: 700;">Search Results</h3>
                <span style="font-family: 'Helvetica Neue', sans-serif; font-size:0.9rem; color:#888; text-transform:uppercase;"><?php echo $total_items; ?> Items Found</span>
            </div>

            <div class="row">
                <?php
                if($total_items > 0) {
                    while($p = $result->fetch_assoc()) {
                        $prodID = $p['prodID'];
                        $img = !empty($p['image']) ? $p['image'] : 'images/item-1.jpg';
                        $imgTop = !empty($p['image_alt']) ? $p['image_alt'] : $img;
                        $price = "RM " . number_format($p['price'], 2);
                        ?>
                        
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="product-card">
                                
                                <a href="product-detail.php?id=<?php echo $prodID; ?>" style="display:block;">
                                    <div class="product-img-container" onmouseover="swapToTopView(this)" onmouseout="swapToSideView(this)">
                                        <img src="<?php echo htmlspecialchars($img); ?>" 
                                             data-side="<?php echo htmlspecialchars($img); ?>" 
                                             data-top="<?php echo htmlspecialchars($imgTop); ?>" 
                                             class="main-shoe-img" alt="<?php echo htmlspecialchars($p['name']); ?>">
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
                                    <div class="product-category"><?php echo htmlspecialchars($p['category']); ?></div>
                                    <h3 class="product-title"><?php echo htmlspecialchars($p['name']); ?></h3>
                                    <div class="product-price"><?php echo $price; ?></div>
                                </a>

                            </div>
                        </div>

                        <?php
                    }
                } else {
                    echo "<div class='col-12 py-5 text-center'>
                            <h3 style='font-family: \"Futura Condensed\", sans-serif; font-size:2rem; text-transform:uppercase;'>No Data Found</h3>
                            <p style='color:#888;'>Try adjusting your filters or search terms.</p>
                          </div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Interactive JS for Results -->
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