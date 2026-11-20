<?php
session_start();
require 'db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') { header("Location: login.php"); exit(); }
$custID = $_SESSION['custID'];

// Auto-Heal order_items to support sizes
try {
    $check_oi = $conn->query("SHOW COLUMNS FROM order_items LIKE 'shoe_size'");
    if ($check_oi && $check_oi->num_rows == 0) {
        $conn->query("ALTER TABLE order_items ADD COLUMN shoe_size VARCHAR(50) DEFAULT 'N/A'");
    }
} catch (Exception $e) {}

$cart_items = [];
$sql = "SELECT c.quantity, c.shoe_size, p.prodID, p.name, p.price FROM shopping_cart c JOIN product p ON c.prodID = p.prodID WHERE c.custID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $custID);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()) { $cart_items[] = $row; }

if (empty($cart_items)) { header("Location: cart.php"); exit(); }

$saved_addresses = [];
try {
    $table_name = ($conn->query("SHOW TABLES LIKE 'customer_addresses'")->num_rows > 0) ? 'customer_addresses' : 'address_book';
    if ($table_name) {
        $resA = $conn->query("SELECT * FROM " . $table_name . " WHERE custID = " . $custID);
        if ($resA) { while ($rowA = $resA->fetch_assoc()) { $saved_addresses[] = $rowA; } }
    }
} catch (Exception $e) {}

$subtotal = 0;
foreach ($cart_items as $item) { $subtotal += ($item['price'] * $item['quantity']); }
$discount = isset($_SESSION['coupon']) ? ($_SESSION['coupon']['discount_type'] == 'Percentage' ? $subtotal * ($_SESSION['coupon']['discount_value'] / 100) : $_SESSION['coupon']['discount_value']) : 0;
$grand_total = max(0, $subtotal - $discount);

$order_success = false;
$new_order_id = 0;
$db_error_message = "";

$check_stock = $conn->query("SHOW COLUMNS FROM product LIKE 'stock_quantity'");
$stock_col = ($check_stock && $check_stock->num_rows > 0) ? 'stock_quantity' : 'stock';
$check_prod = $conn->query("SHOW COLUMNS FROM order_items LIKE 'prodID'");
$prod_col = ($check_prod && $check_prod->num_rows > 0) ? 'prodID' : 'product_id';

if (isset($_POST['place_order_flag'])) {
    
    $address = "";
    if (isset($_POST['address_selection'])) {
        $address = ($_POST['address_selection'] === 'new' && !empty($_POST['new_shipping_address'])) ? trim($_POST['new_shipping_address']) : trim($_POST['address_selection']);
    } else { $address = "No Address Selected"; }
    
    $payment = $_POST['payment_method'];

    $conn->begin_transaction();
    try {
        $insert_order = $conn->prepare("INSERT INTO orders (custID, total_amount, status, payment_method, shipping_address) VALUES (?, ?, 'Pending', ?, ?)");
        $insert_order->bind_param("idss", $custID, $grand_total, $payment, $address);
        $insert_order->execute();
        $new_order_id = $insert_order->insert_id;

        // Insert Items WITH size included
        $insert_item = $conn->prepare("INSERT INTO order_items (orderID, `$prod_col`, product_name, shoe_size, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $insert_item->bind_param("iissid", $new_order_id, $item['prodID'], $item['name'], $item['shoe_size'], $item['quantity'], $item['price']);
            $insert_item->execute();
            
            // Deduct from specific size
            if ($item['shoe_size'] !== 'N/A') {
                $safe_size = $conn->real_escape_string($item['shoe_size']);
                $conn->query("UPDATE product_sizes SET stock_quantity = stock_quantity - " . $item['quantity'] . " WHERE product_id = " . $item['prodID'] . " AND size_name = '$safe_size'");
            }
            // Deduct from global total fallback
            $conn->query("UPDATE product SET `$stock_col` = `$stock_col` - " . $item['quantity'] . " WHERE prodID = " . $item['prodID']);
        }

        $clear_cart = $conn->prepare("DELETE FROM shopping_cart WHERE custID = ?");
        $clear_cart->bind_param("i", $custID);
        $clear_cart->execute();
        
        unset($_SESSION['coupon']);
        $conn->commit();
        $order_success = true;

    } catch (Exception $e) {
        $conn->rollback();
        $db_error_message = $e->getMessage();
    }
}

include 'includes/header.php'; 
?>

<style>
    .address-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .address-card { border: 2px solid #e5e5e5; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.2s ease; position: relative; background: #fff; display: flex; flex-direction: column; height: 100%; }
    .address-card:hover { border-color: #ccc; }
    .address-card.active { border-color: #000; background: #fdfdfd; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .address-card input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
    .address-label { font-family: 'Futura Condensed', sans-serif; font-size: 1.2rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block; color: #111; }
    .address-details { font-family: 'Helvetica Neue', sans-serif; font-size: 0.9rem; color: #555; white-space: pre-line; line-height: 1.5; flex-grow: 1; }
    .check-icon { display: none; position: absolute; top: 20px; right: 20px; color: #000; font-size: 1.2rem; }
    .address-card.active .check-icon { display: block; }
    .summary-container { width: 100%; margin: 0; padding: 0; }
    .summary-row-strict { display: flex; justify-content: space-between; align-items: flex-start; width: 100%; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px dashed #ccc; }
    .summary-col-left { flex: 1; padding-right: 15px; word-break: break-word; font-family: 'Helvetica Neue', sans-serif; color: #333; line-height: 1.4; }
    .summary-col-mid { width: 40px; text-align: left; font-family: 'Helvetica Neue', sans-serif; font-weight: 600; color: #111; }
    .summary-col-right { width: 100px; text-align: right; font-family: 'Helvetica Neue', sans-serif; font-weight: 500; color: #111; white-space: nowrap; }
    .summary-totals-row { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 12px; }
    .summary-totals-label { font-family: 'Helvetica Neue', sans-serif; color: #333; }
    .summary-totals-value { width: 100px; text-align: right; font-family: 'Helvetica Neue', sans-serif; font-weight: 500; color: #111; white-space: nowrap; }
    
    body.dark-mode .colorlib-form, body.dark-mode .cart-detail { background-color: #161921 !important; border: 1px solid #2a2e39 !important; }
    body.dark-mode .colorlib-form h2, body.dark-mode .cart-detail h2, body.dark-mode .form-group label { color: #e2e8f0 !important; }
    body.dark-mode .form-control { background-color: #1a1d24 !important; color: #e2e8f0 !important; border-color: #334155 !important; }
    body.dark-mode .address-card { background-color: #1a1d24 !important; border-color: #334155 !important; }
    body.dark-mode .address-card:hover { border-color: #64748b !important; background-color: #1e222b !important; }
    body.dark-mode .address-card.active { border-color: #88c8bc !important; background-color: #222630 !important; }
    body.dark-mode .address-label { color: #f8fafc !important; }
    body.dark-mode .address-details { color: #94a3b8 !important; }
    body.dark-mode .check-icon { color: #88c8bc !important; }
    body.dark-mode .summary-row-strict { border-bottom-color: #334155 !important; }
    body.dark-mode .summary-col-left, body.dark-mode .summary-col-mid, body.dark-mode .summary-col-right, body.dark-mode .summary-totals-label, body.dark-mode .summary-totals-value { color: #e2e8f0 !important; }
</style>

<div class="breadcrumbs"><div class="container"><div class="row"><div class="col"><p class="bread"><span><a href="store.php">Home</a></span> / <span>Checkout</span></p></div></div></div></div>

<div class="colorlib-product">
    <div class="container">
        
        <form method="POST" action="checkout.php" id="checkoutForm">
            <input type="hidden" name="place_order_flag" value="1">
            
            <div class="row">
                <div class="col-lg-7">
                    <div class="colorlib-form p-4 rounded border">
                        <h2 class="mb-4">Shipping Destination</h2>
                        <div class="address-grid">
                            <?php foreach($saved_addresses as $index => $addr): ?>
                                <?php 
                                $fname = $addr['a_firstname'] ?? $addr['firstname'] ?? '';
                                $lname = $addr['a_lastname'] ?? $addr['lastname'] ?? '';
                                $street = $addr['a_address'] ?? $addr['address'] ?? '';
                                $city = $addr['a_towncity'] ?? $addr['towncity'] ?? '';
                                $zip = $addr['a_zipcode'] ?? $addr['zipcode'] ?? '';
                                $phone = $addr['a_phone'] ?? $addr['phone'] ?? '';
                                $label = $addr['address_label'] ?? 'Saved Address';
                                $full_address = trim("$fname $lname") . "\n$street\n" . trim("$city, $zip", ", ") . "\nPhone: $phone";
                                ?>
                                <label class="address-card" onclick="selectAddressCard(this, false)">
                                    <input type="radio" name="address_selection" value="<?php echo htmlspecialchars($full_address); ?>">
                                    <i class="icon-check check-icon"></i>
                                    <span class="address-label"><?php echo htmlspecialchars($label); ?></span>
                                    <span class="address-details"><?php echo htmlspecialchars($full_address); ?></span>
                                </label>
                            <?php endforeach; ?>
                            <label class="address-card" onclick="selectAddressCard(this, true)">
                                <input type="radio" name="address_selection" value="new">
                                <i class="icon-check check-icon"></i>
                                <span class="address-label">+ New Address</span>
                                <span class="address-details">Deliver your gear to a different location.</span>
                            </label>
                        </div>

                        <div id="newAddressContainer" style="display: none;" class="mt-4">
                            <div class="form-group">
                                <label for="address">Enter New Delivery Address</label>
                                <textarea name="new_shipping_address" id="newShippingBox" class="form-control" rows="4" placeholder="Enter full street address..."></textarea>
                            </div>
                        </div>
                        
                        <h2 class="mt-5 mb-4">Payment Method</h2>
                        <div class="form-group">
                            <select name="payment_method" class="form-control" style="height: 50px;" required>
                                <option value="Credit Card">Credit Card / Debit Card</option>
                                <option value="Online Banking">Online Banking (FPX)</option>
                                <option value="Cash on Delivery">Cash on Delivery</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cart-detail p-4 rounded border">
                        <h2 class="mb-4">Order Summary</h2>
                        <div class="summary-container">
                            <?php foreach($cart_items as $item): ?>
                                <div class="summary-row-strict">
                                    <div class="summary-col-left">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <?php if(!empty($item['shoe_size']) && $item['shoe_size'] !== 'N/A'): ?>
                                            <br><small class="text-muted fw-bold">Size: <?php echo htmlspecialchars($item['shoe_size']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="summary-col-mid">x<?php echo $item['quantity']; ?></div>
                                    <div class="summary-col-right">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="summary-totals-row mt-3">
                                <div class="summary-totals-label">Subtotal</div>
                                <div class="summary-totals-value">RM <?php echo number_format($subtotal, 2); ?></div>
                            </div>
                            <?php if($discount > 0): ?>
                            <div class="summary-totals-row text-success">
                                <div class="summary-totals-label font-weight-bold" style="color: inherit;">Discount</div>
                                <div class="summary-totals-value font-weight-bold" style="color: inherit;">- RM <?php echo number_format($discount, 2); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="summary-totals-row mt-4 pt-3" style="border-top: 2px solid #333;">
                                <div class="summary-totals-label" style="font-family: 'Futura Condensed', sans-serif; font-size: 1.5rem; font-weight: 800; text-transform: uppercase;">Total</div>
                                <div class="summary-totals-value" style="font-size: 1.5rem; font-weight: 700;">RM <?php echo number_format($grand_total, 2); ?></div>
                            </div>
                        </div>
                        <div class="mt-5"><button type="submit" class="btn btn-primary" style="border-radius: 30px; width: 100%; padding: 15px; font-weight: bold; text-transform: uppercase;">Place Order</button></div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function selectAddressCard(selectedCard, isNewAddress) {
        document.querySelectorAll('.address-card').forEach(card => card.classList.remove('active'));
        selectedCard.classList.add('active');
        const radio = selectedCard.querySelector('input[type="radio"]');
        if (radio) { radio.checked = true; }
        document.getElementById('newAddressContainer').style.display = isNewAddress ? 'block' : 'none';
    }

    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const selected = document.querySelector('input[name="address_selection"]:checked');
        if (!selected) {
            e.preventDefault();
            Swal.fire({ title: 'Missing Address', text: 'Please select a delivery address.', icon: 'warning' });
            return;
        }
        if (selected.value === 'new' && document.getElementById('newShippingBox').value.trim() === '') {
            e.preventDefault();
            Swal.fire({ title: 'Incomplete Address', text: 'Please type your new delivery address.', icon: 'warning' });
        }
    });
</script>

<?php if($order_success): ?><script> Swal.fire({ title: 'Payment Successful!', icon: 'success', timer: 2000, showConfirmButton: false }).then(() => { window.location.href = 'receipt.php?id=<?php echo $new_order_id; ?>'; }); </script><?php endif; ?>
<?php if(!empty($db_error_message)): ?><script> Swal.fire({ title: 'Order Failed', text: 'Database Error: <?php echo addslashes($db_error_message); ?>', icon: 'error' }); </script><?php endif; ?>
<?php include 'includes/footer.php'; ?>