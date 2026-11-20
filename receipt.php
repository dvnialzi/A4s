<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: store.php");
    exit();
}
$orderID = intval($_GET['id']);
$custID = $_SESSION['custID'] ?? 0;

// Auto-Heal order_items to support sizes just in case
try {
    $check_oi = $conn->query("SHOW COLUMNS FROM order_items LIKE 'shoe_size'");
    if ($check_oi && $check_oi->num_rows == 0) {
        $conn->query("ALTER TABLE order_items ADD COLUMN shoe_size VARCHAR(50) DEFAULT 'N/A'");
    }
} catch (Exception $e) {}

// Fetch Order Details
$order_sql = "SELECT * FROM orders WHERE orderID = ? AND custID = ?";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $orderID, $custID);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("<div style='text-align:center; padding: 50px; font-family:sans-serif;'><h2>Receipt Not Found or Access Denied</h2><a href='store.php'>Return to Store</a></div>");
}

// Fetch Customer Info
$cust_sql = "SELECT * FROM customer WHERE custID = ?";
$stmtC = $conn->prepare($cust_sql);
$stmtC->bind_param("i", $custID);
$stmtC->execute();
$customer = $stmtC->get_result()->fetch_assoc();

// Calculate display variables
$subtotal = $order['total_amount']; 
$service_fee = 0.00;
$grand_total = $subtotal + $service_fee;

include 'includes/header.php';
?>

<style>
    .invoice-box { max-width: 800px; margin: 20px auto 60px auto; padding: 50px; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
    .invoice-header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #000; padding-bottom: 20px; }
    .invoice-title { font-family: 'Futura Condensed', sans-serif; font-size: 3rem; text-transform: uppercase; font-weight: 800; margin: 0; }
    .invoice-meta { text-align: right; font-family: 'Helvetica Neue', sans-serif; color: #555; }
    .invoice-details { display: flex; justify-content: space-between; margin-bottom: 40px; font-family: 'Helvetica Neue', sans-serif; }
    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-family: 'Helvetica Neue', sans-serif; }
    .invoice-table th { border-bottom: 2px solid #000; padding: 12px 0; text-align: left; text-transform: uppercase; font-size: 0.9rem; color: #555; }
    .invoice-table td { padding: 15px 0; border-bottom: 1px solid #eee; vertical-align: top;}
    .invoice-totals { width: 300px; float: right; font-family: 'Helvetica Neue', sans-serif; }
    .total-line { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .grand-total { font-weight: 800; font-size: 1.2rem; border-bottom: 2px solid #000; }
    .btn-print { background: #000; color: #fff; padding: 12px 30px; text-transform: uppercase; font-weight: bold; border-radius: 30px; border: none; text-decoration: none; margin-bottom: 20px; display: inline-block; cursor: pointer; transition: 0.3s; }
    .btn-print:hover { background: #333; color: #fff; }
    
    body.dark-mode .invoice-box { background: #161921; border-color: #334155; }
    body.dark-mode .invoice-header, body.dark-mode .invoice-table th, body.dark-mode .grand-total { border-color: #f8fafc; color: #f8fafc; }
    body.dark-mode .invoice-table td, body.dark-mode .total-line { border-color: #334155; color: #cbd5e1; }
    body.dark-mode .invoice-meta, body.dark-mode .invoice-details { color: #cbd5e1; }
</style>

<div class="container text-center mt-5">
    <button onclick="downloadPDF()" class="btn-print" id="downloadBtn"><i class="icon-download"></i> Download PDF</button>
</div>

<div class="container" id="invoice-content">
    <div class="invoice-box">
        <div class="invoice-header">
            <h1 class="invoice-title">OFFICIAL RECEIPT</h1>
            <div class="invoice-meta">
                <strong>Order #:</strong> ORD-<?php echo str_pad($orderID, 5, '0', STR_PAD_LEFT); ?><br>
                <strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['order_date'] ?? 'now')); ?><br>
                <strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?>
            </div>
        </div>
        
        <div class="invoice-details">
            <div>
                <strong style="text-transform: uppercase; font-size: 0.9rem; color: #888;">Billed To:</strong><br>
                <strong><?php echo htmlspecialchars($customer['username'] ?? 'Customer'); ?></strong><br>
                <?php echo htmlspecialchars($customer['email'] ?? ''); ?><br>
                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
            </div>
            <div style="text-align: right;">
                <strong style="text-transform: uppercase; font-size: 0.9rem; color: #888;">Payment Method:</strong><br>
                <?php echo htmlspecialchars($order['payment_method']); ?>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch Order Items and display specific shoe size
                $items_sql = "SELECT product_name, shoe_size, quantity, price FROM order_items WHERE orderID = ?";
                $stmtI = $conn->prepare($items_sql);
                $stmtI->bind_param("i", $orderID);
                $stmtI->execute();
                $items = $stmtI->get_result();
                
                while($item = $items->fetch_assoc()):
                    $item_total = $item['quantity'] * $item['price'];
                ?>
                <tr>
                    <td>
                        <strong style="display:block;"><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <?php if(!empty($item['shoe_size']) && $item['shoe_size'] !== 'N/A'): ?>
                            <small style="color:#888; font-weight:bold;">Size: <?php echo htmlspecialchars($item['shoe_size']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="text-align: right;">RM <?php echo number_format($item['price'], 2); ?></td>
                    <td style="text-align: right;">RM <?php echo number_format($item_total, 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="invoice-totals">
            <div class="total-line">
                <span>Subtotal</span>
                <span>RM <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="total-line">
                <span>Platform Service Fee</span>
                <span>RM <?php echo number_format($service_fee, 2); ?></span>
            </div>
            <div class="total-line grand-total">
                <span>TOTAL PAID</span>
                <span>RM <?php echo number_format($grand_total, 2); ?></span>
            </div>
        </div>
        
        <div class="clearfix"></div>
        
        <div style="margin-top: 60px; border-top: 1px solid #eee; padding-top: 20px; font-family: 'Helvetica Neue', sans-serif; font-size: 0.85rem; color: #888; text-align: center;">
            Thank you for gearing up with A4S Footwear.<br>
            If you have any questions about this receipt, please contact support@a4sfootwear.com.
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function downloadPDF() {
        const btn = document.getElementById('downloadBtn');
        const originalText = btn.innerHTML;
        
        // Show loading state
        btn.innerHTML = '<i class="icon-spinner"></i> Generating PDF...';
        btn.disabled = true;

        // Target only the invoice content box to make it look clean
        const element = document.getElementById('invoice-content');
        
        // Configuration for the PDF
        const opt = {
            margin:       0.5, // Half inch margin
            filename:     'A4S_Receipt_ORD-<?php echo str_pad($orderID, 5, "0", STR_PAD_LEFT); ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
        };

        // Generate and instantly download
        html2pdf().set(opt).from(element).save().then(() => {
            // Restore button
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
</script>

<?php include 'includes/footer.php'; ?>