<?php
session_start();
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') { header("Location: login.php"); exit(); }
$custID = $_SESSION['custID'];

$sql = "SELECT o.*, 
        (SELECT status FROM refund_requests WHERE orderID = o.orderID ORDER BY refund_id DESC LIMIT 1) as refund_status,
        (SELECT refund_id FROM refund_requests WHERE orderID = o.orderID ORDER BY refund_id DESC LIMIT 1) as refund_id 
        FROM orders o WHERE o.custID = ? ORDER BY o.orderID DESC";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $custID);
$stmt->execute();
$orders = $stmt->get_result();

include 'includes/header.php'; 
?>

<style>
    .dashboard-title { font-family: 'Futura Condensed', 'Oswald', sans-serif; font-size: 3rem; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 40px; color: #000; }
    .orders-table { width: 100%; border-collapse: collapse; }
    .orders-table th { background: #f8f9fa; font-family: 'Futura Condensed', sans-serif; text-transform: uppercase; font-size: 1.1rem; padding: 15px; border-bottom: 2px solid #000; }
    .orders-table td { padding: 20px 15px; vertical-align: top; border-bottom: 1px solid #eee; font-family: 'Helvetica Neue', sans-serif; }
    .status-dot { display: inline-block; padding: 5px 12px; border-radius: 50px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-completed { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    .qty-badge { background: #000; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; margin-right: 8px; }
    
    body.dark-mode .dashboard-title { color: #fff; border-bottom-color: #fff; }
    body.dark-mode .orders-table th { background: #1a1a1a; border-bottom-color: #fff; color: #fff; }
    body.dark-mode .orders-table td { border-bottom-color: #333; color: #ccc; }
</style>

<div class="breadcrumbs"><div class="container"><div class="row"><div class="col"><p class="bread"><span><a href="store.php">Home</a></span> / <span>My Orders</span></p></div></div></div></div>

<div class="colorlib-product">
    <div class="container">
        <div class="row"><div class="col-md-12"><h2 class="dashboard-title">Order History</h2></div></div>
        <div class="row">
            <div class="col-md-12" style="overflow-x: auto;">
                <table class="orders-table">
                    <thead><tr><th>Order #</th><th>Items</th><th>Total Paid</th><th>Order Status</th><th>Refund Status</th></tr></thead>
                    <tbody>
                        <?php
                        if ($orders->num_rows > 0) {
                            while ($row = $orders->fetch_assoc()) {
                                $display_id = str_pad($row['orderID'], 5, '0', STR_PAD_LEFT);
                                $orderDotClass = ($row['status'] == 'Pending') ? 'status-pending' : (($row['status'] == 'Completed') ? 'status-completed' : 'status-cancelled');
                                
                                $refundBadgeHTML = "<span class='text-muted' style='font-size: 0.85rem;'>Not Applicable</span>";
                                if (!empty($row['refund_status'])) {
                                    $refundColor = ($row['refund_status'] == 'Pending') ? 'color: #856404;' : (($row['refund_status'] == 'Approved') ? 'color: #155724;' : 'color: #721c24;');
                                    $refundBadgeHTML = "<span style='font-weight: bold; $refundColor'>" . $row['refund_status'] . "</span>";
                                }

                                echo "<tr>";
                                echo "<td><a href='receipt.php?id=" . $row['orderID'] . "' style='font-weight: bold; text-decoration: underline; color: inherit;'>#ORD-" . $display_id . "</a></td>";
                                echo "<td><ul style='list-style:none; padding:0; margin:0;'>";
                                
                                $item_sql = "SELECT product_name, quantity, shoe_size FROM order_items WHERE orderID = " . $row['orderID'];
                                $check_col = $conn->query("SHOW COLUMNS FROM order_items LIKE 'shoe_size'");
                                if(!$check_col || $check_col->num_rows == 0) { $item_sql = "SELECT product_name, quantity, 'N/A' as shoe_size FROM order_items WHERE orderID = " . $row['orderID']; }
                                
                                $item_result = $conn->query($item_sql);
                                if ($item_result && $item_result->num_rows > 0) {
                                    while ($item = $item_result->fetch_assoc()) {
                                        $size_str = (!empty($item['shoe_size']) && $item['shoe_size'] !== 'N/A') ? " <br><small class='text-muted' style='margin-left: 35px;'>Size: " . htmlspecialchars($item['shoe_size']) . "</small>" : "";
                                        echo "<li style='margin-bottom:8px;'><span class='qty-badge'>" . $item['quantity'] . "</span> " . htmlspecialchars($item['product_name']) . $size_str . "</li>";
                                    }
                                }
                                echo "</ul></td>";
                                echo "<td style='font-weight: 500;'>RM " . number_format($row['total_amount'], 2) . "</td>";
                                echo "<td><span class='status-dot " . $orderDotClass . "'>" . $row['status'] . "</span></td>";
                                echo "<td>" . $refundBadgeHTML . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5'><h4>No orders found.</h4></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>