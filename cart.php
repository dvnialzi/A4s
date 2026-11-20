<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') { header("Location: login.php"); exit(); }
$custID = $_SESSION['custID'];

if(isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $conn->query("DELETE FROM shopping_cart WHERE cart_id = $remove_id AND custID = $custID");
    header("Location: cart.php"); exit();
}

// Fixed Update logic to use cart_id instead of prodID!
if(isset($_POST['update_cart'])) {
    foreach($_POST['quantities'] as $cart_id => $quantity) {
        $qty = max(1, intval($quantity)); 
        $upd = $conn->prepare("UPDATE shopping_cart SET quantity = ? WHERE cart_id = ? AND custID = ?");
        $upd->bind_param("iii", $qty, $cart_id, $custID);
        $upd->execute();
    }
    header("Location: cart.php"); exit();
}

$msg = "";
$coupon_already_applied = false;
if(isset($_POST['apply_coupon'])) {
    $code = trim($_POST['coupon_code']);
    if (isset($_SESSION['coupon']) && strcasecmp($_SESSION['coupon']['code'], $code) === 0) {
        $coupon_already_applied = true;
    } else {
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows > 0) {
            $_SESSION['coupon'] = $res->fetch_assoc();
            $msg = "<div class='alert alert-success' style='border-radius: 5px;'>Coupon applied successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger' style='border-radius: 5px;'>Invalid or inactive coupon.</div>";
        }
    }
}

// Fetch Cart items including shoe_size
$cart_items = [];
$sql = "SELECT c.cart_id, c.quantity, c.shoe_size, p.prodID, p.name, p.price, p.image 
        FROM shopping_cart c JOIN product p ON c.prodID = p.prodID WHERE c.custID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $custID);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) { $cart_items[] = $row; }

$subtotal = 0;
foreach ($cart_items as $item) { $subtotal += ($item['price'] * $item['quantity']); }
$discount = 0;
if(isset($_SESSION['coupon'])) {
    $discount = ($_SESSION['coupon']['discount_type'] == 'Percentage') ? $subtotal * ($_SESSION['coupon']['discount_value'] / 100) : $_SESSION['coupon']['discount_value'];
}
$grand_total = max(0, $subtotal - $discount);

include 'includes/header.php'; 
?>

<style>
    .btn-remove-modern { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 50%; background-color: #fff; border: 1px solid #ff4d4f; color: #ff4d4f; font-weight: bold; transition: 0.3s; margin-right: 20px; box-shadow: 0 2px 4px rgba(255, 77, 79, 0.1); }
    .btn-remove-modern:hover { background-color: #ff4d4f; color: #fff; text-decoration: none; transform: scale(1.1); }
    .cart-product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; margin-right: 15px; }
</style>

<div class="breadcrumbs"><div class="container"><div class="row"><div class="col"><p class="bread"><span><a href="store.php">Home</a></span> / <span>Shopping Cart</span></p></div></div></div></div>

<div class="colorlib-product">
    <div class="container">
        <?php echo $msg; ?>
        <div class="row row-pb-lg">
            <div class="col-md-12">
                <form action="cart.php" method="POST">
                    <div class="product-name d-flex">
                        <div class="one-forth text-left px-4"><span>Product Details</span></div>
                        <div class="one-eight text-center"><span>Price</span></div>
                        <div class="one-eight text-center"><span>Quantity</span></div>
                        <div class="one-eight text-center"><span>Total</span></div>
                    </div>

                    <?php if(empty($cart_items)): ?>
                        <div class="product-cart d-flex text-center py-5"><div class="one-forth text-center w-100"><p class="text-muted">Your cart is currently empty.</p></div></div>
                    <?php else: ?>
                        <?php foreach($cart_items as $item): ?>
                            <div class="product-cart d-flex">
                                <div class="one-forth">
                                    <div class="display-tc d-flex align-items-center">
                                        <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="btn-remove-modern">✕</a>
                                        <img src="<?php echo !empty($item['image']) ? $item['image'] : 'images/item-1.jpg'; ?>" class="cart-product-img">
                                        <div>
                                            <h3 style="margin: 0; font-weight: 500; font-size: 16px;"><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <?php if(!empty($item['shoe_size']) && $item['shoe_size'] !== 'N/A'): ?>
                                                <small class="text-muted fw-bold">Size: <?php echo htmlspecialchars($item['shoe_size']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="one-eight text-center"><div class="display-tc"><span class="price">RM <?php echo number_format($item['price'], 2); ?></span></div></div>
                                <div class="one-eight text-center">
                                    <div class="display-tc">
                                        <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]" class="form-control input-number text-center" value="<?php echo $item['quantity']; ?>" min="1" max="100">
                                    </div>
                                </div>
                                <div class="one-eight text-center"><div class="display-tc"><span class="price">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></div></div>
                            </div>
                        <?php endforeach; ?>
                        <div class="row mt-4"><div class="col-md-12 text-right"><button type="submit" name="update_cart" class="btn btn-primary" style="border-radius: 30px;">Update Cart</button></div></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="row row-pb-lg">
            <div class="col-md-12">
                <div class="total-wrap">
                    <div class="row">
                        <div class="col-sm-8">
                            <form action="cart.php" method="POST">
                                <div class="row form-group">
                                    <div class="col-sm-9"><input type="text" name="coupon_code" class="form-control input-number" placeholder="Your Coupon Number..."></div>
                                    <div class="col-sm-3"><button type="submit" name="apply_coupon" class="btn btn-primary" style="border-radius: 30px;">Apply Coupon</button></div>
                                </div>
                            </form>
                        </div>
                        <div class="col-sm-4 text-center">
                            <div class="total">
                                <div class="sub">
                                    <p><span>Subtotal:</span> <span>RM <?php echo number_format($subtotal, 2); ?></span></p>
                                    <?php if(isset($_SESSION['coupon'])): ?><p><span>Discount:</span> <span style="color: #28a745;">- RM <?php echo number_format($discount, 2); ?></span></p><?php endif; ?>
                                </div>
                                <div class="grand-total"><p><span><strong>Total:</strong></span> <span>RM <?php echo number_format($grand_total, 2); ?></span></p></div>
                            </div>
                            <?php if(!empty($cart_items)): ?><a href="checkout.php" class="btn btn-primary btn-block mt-3" style="border-radius: 30px; padding: 12px;">Proceed to Checkout <i class="icon-arrow-right-circle"></i></a><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if($coupon_already_applied): ?><script> Swal.fire({title: 'Coupon Active', icon: 'info'}); </script><?php endif; ?>
<?php include 'includes/footer.php'; ?>