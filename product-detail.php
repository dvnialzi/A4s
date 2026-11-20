<?php
session_start();
require 'db.php'; 

// ==========================================
// AUTO-HEALING: CREATE SIZE TABLES & COLUMNS
// ==========================================
try {
    $conn->query("CREATE TABLE IF NOT EXISTS product_sizes (
        size_id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        size_name VARCHAR(50) NOT NULL,
        stock_quantity INT DEFAULT 0
    )");
    
    $check_cart = $conn->query("SHOW COLUMNS FROM shopping_cart LIKE 'shoe_size'");
    if ($check_cart && $check_cart->num_rows == 0) {
        $conn->query("ALTER TABLE shopping_cart ADD COLUMN shoe_size VARCHAR(50) DEFAULT 'N/A'");
    }
} catch (Exception $e) {}

$added_to_cart = false;
$out_of_stock_error = false;
$login_required_error = false;

if(isset($_POST['add_to_cart'])) {
    // REAL WEBSITE FEATURE: Intercept unauthorized users trying to buy
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        $login_required_error = true;
    } else {
        $custID = $_SESSION['custID'];
        $prodID = (int)$_POST['prodID'];
        $quantity = (int)$_POST['quantity'];
        
        $shoe_size = isset($_POST['shoe_size']) ? trim($_POST['shoe_size']) : '';
        
        $current_stock = 0;
        if ($shoe_size !== '') {
            $safe_size = $conn->real_escape_string($shoe_size);
            $st_res = $conn->query("SELECT stock_quantity FROM product_sizes WHERE product_id = $prodID AND size_name = '$safe_size'");
            $current_stock = ($st_res && $st_res->num_rows > 0) ? (int)$st_res->fetch_assoc()['stock_quantity'] : 0;
        }

        if ($current_stock >= $quantity && $shoe_size !== '') {
            $check_cart = $conn->prepare("SELECT cart_id, quantity FROM shopping_cart WHERE custID = ? AND prodID = ? AND shoe_size = ?");
            $check_cart->bind_param("iis", $custID, $prodID, $shoe_size);
            $check_cart->execute();
            $cart_res = $check_cart->get_result();

            if ($cart_res->num_rows > 0) {
                $existing = $cart_res->fetch_assoc();
                $new_qty = $existing['quantity'] + $quantity;
                $update = $conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ?");
                $update->bind_param("ii", $new_qty, $existing['cart_id']);
                $update->execute();
            } else {
                $insert = $conn->prepare("INSERT INTO shopping_cart (custID, prodID, quantity, shoe_size) VALUES (?, ?, ?, ?)");
                $insert->bind_param("iiis", $custID, $prodID, $quantity, $shoe_size);
                $insert->execute();
            }
            $added_to_cart = true;
        } else {
            $out_of_stock_error = true;
        }
    }
}

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$prodID = (int)$_GET['id'];
$sql = "SELECT * FROM product WHERE prodID = $prodID";
$product = $conn->query($sql)->fetch_assoc();
if (!$product) { header("Location: index.php"); exit(); }

$img = !empty($product['image']) ? $product['image'] : 'images/item-1.jpg';
$price = "RM " . number_format($product['price'], 2);

$sizes_res = $conn->query("SELECT * FROM product_sizes WHERE product_id = $prodID AND stock_quantity > 0");
$has_sizes = ($sizes_res && $sizes_res->num_rows > 0);
$sizes = [];
if($has_sizes) { while($s = $sizes_res->fetch_assoc()) { $sizes[] = $s; } }

$is_out_of_stock = !$has_sizes;

include 'includes/header.php'; 
?>

<style>
    .size-box { display: inline-block; cursor: pointer; }
    .size-box input[type="radio"] { display: none; }
    .size-box span { display: inline-block; border: 2px solid #ccc; padding: 10px 20px; font-family: 'Futura Condensed', sans-serif; font-size: 1.2rem; transition: 0.2s; border-radius: 4px; min-width: 60px; text-align: center; }
    .size-box input[type="radio"]:checked + span { border-color: #000; background: #000; color: #fff; }
    
    body.dark-mode .size-box span { border-color: #444; color: #fff; }
    body.dark-mode .size-box input[type="radio"]:checked + span { border-color: #88c8bc; background: #88c8bc; color: #000; }
</style>

<div class="breadcrumbs">
    <div class="container"><div class="row"><div class="col"><p class="bread"><span><a href="index.php">Home</a></span> / <span>Product Details</span></p></div></div></div>
</div>

<div class="colorlib-product">
    <div class="container">
        <div class="row row-pb-lg product-detail-wrap">
            <div class="col-sm-8">
                <div class="product-entry border">
                    <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="product-desc">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price"><span><?php echo $price; ?></span></p>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    
                    <?php if($is_out_of_stock): ?>
                        <div class="alert alert-danger mt-4" style="border-radius: 30px;">Currently Out of Stock</div>
                    <?php else: ?>
                        <form action="product-detail.php?id=<?php echo $prodID; ?>" method="POST" class="colorlib-form">
                            <input type="hidden" name="prodID" value="<?php echo $prodID; ?>">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold" style="font-family:'Helvetica Neue', sans-serif; text-transform:uppercase; letter-spacing:1px;">Select Size:</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php foreach($sizes as $sz): ?>
                                    <label class="size-box">
                                        <input type="radio" name="shoe_size" value="<?php echo htmlspecialchars($sz['size_name']); ?>" data-stock="<?php echo $sz['stock_quantity']; ?>" required>
                                        <span><?php echo htmlspecialchars($sz['size_name']); ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-sm-12">
                                    <div class="input-group mb-4">
                                        <span class="input-group-btn"><button type="button" class="quantity-left-minus btn"><i class="icon-minus2"></i></button></span>
                                        <input type="text" id="quantity" name="quantity" class="form-control input-number text-center" value="1" min="1" readonly>
                                        <span class="input-group-btn ml-1"><button type="button" class="quantity-right-plus btn"><i class="icon-plus2"></i></button></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <p class="addtocart"><button type="submit" name="add_to_cart" id="btnAddToCart" class="btn btn-primary btn-addtocart" style="border-radius: 30px; width: 100%;"><i class="icon-shopping-cart"></i> Add to Cart</button></p>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- REAL WEBSITE FEATURE: Unauthorized user interceptor -->
<?php if($login_required_error): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Login Required',
            text: 'You need an account to add items to your cart.',
            icon: 'warning',
            confirmButtonText: 'Go to Login',
            confirmButtonColor: '#000',
            background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
            color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
        }).then((result) => {
            window.location.href = 'login.php';
        });
    });
</script>
<?php endif; ?>

<?php if($added_to_cart): ?><script> Swal.fire({title: 'Added to Cart', icon: 'success', timer: 2000, showConfirmButton: false}); </script><?php endif; ?>
<?php if($out_of_stock_error): ?><script> Swal.fire({title: 'Out of Stock', icon: 'error', confirmButtonColor: '#000'}); </script><?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        var max_qty = 0; 
        var qtyInput = document.getElementById('quantity');
        var btnAdd = document.getElementById('btnAddToCart');
        
        if(btnAdd) btnAdd.disabled = true;
        document.querySelectorAll('input[name="shoe_size"]').forEach(radio => {
            radio.addEventListener('change', function() {
                max_qty = parseInt(this.getAttribute('data-stock'));
                qtyInput.value = 1;
                btnAdd.disabled = false;
            });
        });
        
        const btnPlus = document.querySelector('.quantity-right-plus');
        const btnMinus = document.querySelector('.quantity-left-minus');

        if(btnPlus) {
            btnPlus.addEventListener('click', function(e){
                e.preventDefault();
                if(parseInt(qtyInput.value) < max_qty) { qtyInput.value = parseInt(qtyInput.value) + 1; }
            });
        }
        
        if(btnMinus) {
            btnMinus.addEventListener('click', function(e){
                e.preventDefault();
                if(parseInt(qtyInput.value) > 1) { qtyInput.value = parseInt(qtyInput.value) - 1; }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>