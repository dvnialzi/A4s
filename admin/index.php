<?php
session_start();
require '../db.php'; 

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../login.php");
    exit();
}

// ==========================================
// AUTO-HEALING: GUARANTEE SIZES EXIST IN DB
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
    
    $check_oi = $conn->query("SHOW COLUMNS FROM order_items LIKE 'shoe_size'");
    if ($check_oi && $check_oi->num_rows == 0) {
        $conn->query("ALTER TABLE order_items ADD COLUMN shoe_size VARCHAR(50) DEFAULT 'N/A'");
    }
} catch (Exception $e) {}

// ==========================================
// DASHBOARD QUERIES
// ==========================================
$sales_res = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status != 'Cancelled'");
$total_sales = $sales_res->fetch_assoc()['total'] ?? 0.00;

$orders_res = $conn->query("SELECT COUNT(orderID) AS total FROM orders");
$total_orders = $orders_res->fetch_assoc()['total'] ?? 0;

$products_res = $conn->query("SELECT COUNT(prodID) AS total FROM product");
$total_products = $products_res->fetch_assoc()['total'] ?? 0;

$pending_res = $conn->query("SELECT COUNT(orderID) AS total FROM orders WHERE status = 'Pending'");
$total_pending = $pending_res->fetch_assoc()['total'] ?? 0;

$profit_res = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status = 'Completed'");
$total_profit = $profit_res->fetch_assoc()['total'] ?? 0.00;

$return_res = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status = 'Cancelled'");
$total_returns = $return_res->fetch_assoc()['total'] ?? 0.00;

$total_expenses = 0.00; 

$notifications = $conn->query("SELECT * FROM orders ORDER BY orderID DESC LIMIT 3");

$top_selling_sql = "SELECT p.name, p.price, p.image, SUM(oi.quantity) as total_sold 
                    FROM order_items oi 
                    JOIN product p ON oi.product_name = p.name 
                    GROUP BY p.name 
                    ORDER BY total_sold DESC LIMIT 5";
$top_selling = $conn->query($top_selling_sql);

// STRICT SIZE-ONLY LOW STOCK QUERY (Alerts only if stock is UNDER 4)
$low_stock_sql = "
    SELECT p.name, ps.size_name, ps.stock_quantity as stock, p.image, p.prodID 
    FROM product_sizes ps 
    JOIN product p ON ps.product_id = p.prodID 
    WHERE ps.stock_quantity < 4 
    ORDER BY stock ASC LIMIT 5
";
$low_stock = $conn->query($low_stock_sql);

$recent_sales = $conn->query("SELECT * FROM orders ORDER BY orderID DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>A4S - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" crossorigin href="./assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <div id="overlay" class="overlay"></div>
  
  <nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm"><i class="ti ti-layout-sidebar-left-expand"></i></button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2"><i class="ti ti-layout-sidebar-left-expand"></i></button>
    
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="dropdown me-2">
        <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" id="adminDarkModeToggle" style="cursor: pointer;">
          <i class="ti ti-moon fs-5"></i>
        </a>
      </div>

      <div class="dropdown">
        <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" data-bs-toggle="dropdown" href="#" role="button">
          <i class="ti ti-bell fs-5"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger mt-2 ms-n2">
            <?php echo $notifications->num_rows; ?>
          </span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-menu-md p-0" style="min-width: 320px;">
          <ul class="list-unstyled p-0 m-0">
            <?php 
            if($notifications->num_rows > 0) {
              while($noti = $notifications->fetch_assoc()) { 
            ?>
              <li class="p-3 border-bottom ">
                <div class="d-flex gap-3">
                  <div class="icon-shape icon-sm bg-primary text-white rounded-circle flex-shrink-0"><i class="ti ti-shopping-cart"></i></div>
                  <div class="flex-grow-1 small">
                    <p class="mb-0 fw-bold">New Order Received</p>
                    <p class="mb-1 text-muted">Order #ORD<?php echo str_pad($noti['orderID'], 4, '0', STR_PAD_LEFT); ?> placed</p>
                    <div class="text-secondary fw-semibold">RM <?php echo number_format($noti['total_amount'], 2); ?></div>
                  </div>
                </div>
              </li>
            <?php } } else { echo "<li class='p-3 text-center small text-muted'>No recent notifications.</li>"; } ?>
            <li class="px-4 py-3 text-center border-top">
              <a href="admin_orders.php" class="text-primary small text-decoration-none">View all orders</a>
            </li>
          </ul>
        </div>
      </div>

      <div class="dropdown ms-2">
        <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
        </a>
        <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
          <div class="d-flex gap-3 align-items-center border-dashed border-bottom px-3 py-3">
            <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-md rounded-circle" />
            <div>
              <h4 class="mb-0 small fw-bold">Admin <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h4>
              <p class="mb-0 small text-muted">Store Manager</p>
            </div>
          </div>
          <div class="p-3 d-flex flex-column gap-1 small lh-lg">
            <a href="../login.php?logout=1" class="text-danger text-decoration-none"><i class="ti ti-logout me-2"></i><span>Logout</span></a>
          </div>
        </div>
      </div>
    </div>
  </nav>

<aside id="sidebar" class="sidebar">
    <div class="logo-area">
     <a href="index.php" class="d-inline-flex text-decoration-none"><span class="logo-text ms-2 fw-bold fs-4 text-dark">A4S Admin</span></a>
    </div>
    <ul class="nav flex-column mt-3">
      <li class="px-4 py-2"><small class="nav-text text-muted">Main</small></li>
      <li><a class="nav-link active" href="index.php"><i class="ti ti-home"></i><span class="nav-text">Dashboard</span></a></li>
      <li><a class="nav-link" href="admin_products.php"><i class="ti ti-box-seam"></i><span class="nav-text">Inventory</span></a></li>
      <li><a class="nav-link" href="admin_variants.php"><i class="ti ti-palette"></i><span class="nav-text">Color Variants</span></a></li>
      <li><a class="nav-link" href="admin_orders.php"><i class="ti ti-truck"></i><span class="nav-text">Orders</span></a></li>
      <li><a class="nav-link" href="admin_coupons.php"><i class="ti ti-ticket"></i><span class="nav-text">Coupons & Promos</span></a></li>
      
      <li class="px-4 pt-4 pb-2"><small class="nav-text text-muted">Support</small></li>
      <li><a class="nav-link" href="reports.php"><i class="ti ti-messages"></i><span class="nav-text">General Inquiries</span></a></li>
      <li><a class="nav-link" href="admin_refunds.php"><i class="ti ti-receipt-refund"></i><span class="nav-text">Refund Requests</span></a></li>
      
      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
      <li class="px-4 pt-4 pb-2"><small class="nav-text text-primary fw-bold">System Management</small></li>
      <li><a class="nav-link" href="admin_settings.php"><i class="ti ti-settings"></i><span class="nav-text">Store Settings</span></a></li>
      <li><a class="nav-link" href="add_admin.php"><i class="ti ti-user-plus"></i><span class="nav-text">Create Admin</span></a></li>
      <?php endif; ?>
      
      <li class="px-4 pt-4 pb-2"><small class="nav-text text-muted">Account</small></li>
      <li><a class="nav-link" href="../login.php?logout=1"><i class="ti ti-logout"></i><span class="nav-text">Log out</span></a></li>
    </ul>
  </aside>

  <main id="content" class="content py-10" style="margin-top: 60px;">
    <div class="container-fluid">
      <div class="row ">
        <div class="col-12">
          <div class="mb-6">
            <h1 class="fs-3 mb-1">Dashboard</h1>
            <p class="text-muted">Live overview of your e-commerce operations.</p>
          </div>
        </div>
      </div>
      
      <div class="row g-3 mb-4">
        <div class="col-lg-3 col-12">
          <div class="card p-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2">
            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-primary text-white rounded-2"><i class="ti ti-report-analytics fs-4"></i></div>
              <div>
                <h2 class="mb-3 fs-6">Total Sales</h2>
                <h3 class="fw-bold mb-0">RM <?php echo number_format($total_sales, 2); ?></h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-12">
          <div class="card p-4 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-2">
            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-success text-white rounded-2"><i class="ti ti-repeat fs-4"></i></div>
              <div>
                <h2 class="mb-3 fs-6">Total Orders</h2>
                <h3 class="fw-bold mb-0"><?php echo $total_orders; ?></h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-12">
          <div class="card p-4 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-2">
            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-info text-white rounded-2"><i class="ti ti-box fs-4"></i></div>
              <div>
                <h2 class="mb-3 fs-6">Active Products</h2>
                <h3 class="fw-bold mb-0"><?php echo $total_products; ?></h3>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-12">
          <div class="card p-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-2">
            <div class="d-flex gap-3 ">
              <div class="icon-shape icon-md bg-warning text-white rounded-2"><i class="ti ti-clock fs-4"></i></div>
              <div>
                <h2 class="mb-3 fs-6">Pending Orders</h2>
                <h3 class="fw-bold mb-0"><?php echo $total_pending; ?></h3>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="row g-3 mb-4">
        <div class="col-lg-4 col-12">
          <div class="card shadow-sm border-0 bg-white">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-4 mb-3">
                <div>
                  <h3 class="fw-bold h4">RM <?php echo number_format($total_profit, 2); ?></h3>
                  <span class="text-muted fw-semibold">Total Profit (Completed)</span>
                </div>
                <div><i class="ti ti-chart-arrows-vertical fs-1 text-success"></i></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-12">
          <div class="card shadow-sm border-0 bg-white">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-4 mb-3">
                <div>
                  <h3 class="fw-bold h4 text-danger">RM <?php echo number_format($total_returns, 2); ?></h3>
                  <span class="text-muted fw-semibold">Payment Returns (Cancelled)</span>
                </div>
                <div><i class="ti ti-credit-card-refund fs-1 text-danger"></i></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-12">
          <div class="card shadow-sm border-0 bg-white">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between border-bottom pb-4 mb-3">
                <div>
                  <h3 class="fw-bold h4">RM <?php echo number_format($total_expenses, 2); ?></h3>
                  <span class="text-muted fw-semibold">Total Expenses</span>
                </div>
                <div><i class="ti ti-cash-banknote fs-1 text-warning"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center px-4 py-3 border-0">
              <h4 class="mb-0 h5 fw-bold text-dark">Top Selling Products</h4>
            </div>
            <ul class="list-group list-group-flush">
              <?php 
              if($top_selling && $top_selling->num_rows > 0) {
                while($top = $top_selling->fetch_assoc()) { 
              ?>
                <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                  <img src="<?php echo (strpos($top['image'], 'http') === 0) ? htmlspecialchars($top['image']) : '../' . htmlspecialchars($top['image']); ?>" class="rounded" width="48" height="48" style="object-fit:cover;">
                  <div class="flex-grow-1">
                    <p class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($top['name']); ?></p>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                      <span>RM <?php echo number_format($top['price'], 2); ?> </span>
                      <span>•</span>
                      <span class="text-success fw-bold"><?php echo $top['total_sold']; ?> Sold</span>
                    </div>
                  </div>
                </li>
              <?php } } else { echo "<li class='list-group-item px-4 py-4 text-center text-muted'>No sales data.</li>"; } ?>
            </ul>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center px-4 py-3 border-0">
              <h4 class="mb-0 h5 fw-bold text-dark">Low Stock Alerts</h4>
              <a href="admin_products.php" class="small text-primary text-decoration-none">View Inventory</a>
            </div>
            <ul class="list-group list-group-flush">
              <?php 
              if($low_stock && $low_stock->num_rows > 0) {
                while($low = $low_stock->fetch_assoc()) { 
                  $img_src = (strpos($low['image'], 'http') === 0) ? htmlspecialchars($low['image']) : '../' . htmlspecialchars($low['image']);
                  $stock_qty = isset($low['stock']) ? (int)$low['stock'] : 0;
                  
                  // NEW UI: Handles True 0 Stock!
                  if ($stock_qty <= 0) {
                      $display_qty = "<span style='font-size: 0.8rem; font-weight: 800; letter-spacing: 0.5px;'>OUT OF STOCK</span>";
                      $qty_class = "text-danger";
                      $stock_label = "";
                  } else {
                      $display_qty = "<span class='fs-5'>" . str_pad($stock_qty, 2, '0', STR_PAD_LEFT) . "</span>";
                      $qty_class = "text-danger";
                      $stock_label = "<small class='text-muted' style='font-size: 0.7rem;'>In Stock</small>";
                  }
              ?>
                <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                  <img src="<?php echo $img_src; ?>" class="rounded" width="48" height="48" style="object-fit:cover;">
                  <div class="flex-grow-1">
                    <p class="mb-1 fw-semibold text-dark"><?php echo htmlspecialchars($low['name']); ?></p>
                    <span class="badge bg-secondary" style="font-size: 0.7rem;">Size: <?php echo htmlspecialchars($low['size_name']); ?></span>
                  </div>
                  <div class="d-flex flex-column gap-0 align-items-center">
                    <span class="fw-bold <?php echo $qty_class; ?>"><?php echo $display_qty; ?></span>
                    <?php echo $stock_label; ?>
                  </div>
                </li>
              <?php } } else { echo "<li class='list-group-item px-4 py-5 text-center text-muted'>All shoe sizes are well stocked!</li>"; } ?>
            </ul>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card shadow-sm border-0 bg-white h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center px-4 py-3 border-0">
              <h4 class="mb-0 h5 fw-bold text-dark">Recent Sales</h4>
              <a href="admin_orders.php" class="small text-primary text-decoration-none">Manage Orders</a>
            </div>
            <ul class="list-group list-group-flush">
              <?php 
              if($recent_sales && $recent_sales->num_rows > 0) {
                while($recent = $recent_sales->fetch_assoc()) { 
                  $badgeClass = "bg-warning-subtle text-warning";
                  if ($recent['status'] == 'Shipped') $badgeClass = "bg-info-subtle text-info";
                  if ($recent['status'] == 'Completed') $badgeClass = "bg-success-subtle text-success";
                  if ($recent['status'] == 'Cancelled') $badgeClass = "bg-danger-subtle text-danger";
              ?>
                <li class="list-group-item d-flex align-items-center gap-3 px-4 py-3">
                  <div class="icon-shape icon-md bg-light text-primary rounded-2">
                    <i class="ti ti-receipt fs-4"></i>
                  </div>
                  <div class="flex-grow-1">
                    <p class="mb-1 fw-semibold text-dark">Order #ORD<?php echo str_pad($recent['orderID'], 4, '0', STR_PAD_LEFT); ?></p>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                      <span>RM <?php echo number_format($recent['total_amount'], 2); ?></span>
                    </div>
                  </div>
                  <span class="badge <?php echo $badgeClass; ?> border-0 rounded-pill px-3 py-2"><?php echo htmlspecialchars($recent['status']); ?></span>
                </li>
              <?php } } else { echo "<li class='list-group-item px-4 py-4 text-center text-muted'>No recent sales.</li>"; } ?>
            </ul>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
      body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; }
      body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .dropdown-menu { background-color: #1e212b !important; border-color: #334155 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.5) !important; }
      body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; }
      body.dark-mode .logo-area { background-color: #15171e !important; border-bottom: 1px solid #334155 !important; border-right: 1px solid #334155 !important; }
      body.dark-mode .text-dark, body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode h5, body.dark-mode h6, body.dark-mode .logo-text, body.dark-mode strong, body.dark-mode label, body.dark-mode .form-label, body.dark-mode .nav-text { color: #f8fafc !important; }
      body.dark-mode .text-muted, body.dark-mode small, body.dark-mode p { color: #94a3b8 !important; }
      body.dark-mode .nav-link, body.dark-mode .nav-link i, body.dark-mode .nav-link span { color: #cbd5e1 !important; }
      body.dark-mode .nav-link:hover, body.dark-mode .nav-link.active, body.dark-mode .nav-link.active i, body.dark-mode .nav-link.active span { color: #88c8bc !important; }
      body.dark-mode .bg-light, body.dark-mode .table-light, body.dark-mode .table-light th, body.dark-mode .input-group-text { background-color: #272b36 !important; color: #f8fafc !important; border-color: #334155 !important; }
      body.dark-mode .table td, body.dark-mode .table th, body.dark-mode .list-group-item, body.dark-mode .border-bottom { border-color: #334155 !important; color: #cbd5e1 !important; background-color: transparent !important; }
      body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode input, body.dark-mode textarea { background-color: #272b36 !important; border: 1px solid #334155 !important; color: #f8fafc !important; }
      body.dark-mode .form-control:focus, body.dark-mode .form-select:focus { background-color: #1e212b !important; border-color: #88c8bc !important; color: #f8fafc !important; box-shadow: 0 0 0 0.25rem rgba(136, 200, 188, 0.25) !important; }
      body.dark-mode .modal-content, body.dark-mode .modal-header, body.dark-mode .modal-body { background-color: #1e212b !important; color: #f8fafc !important; border-color: #334155 !important; }
      body.dark-mode .card-header { background-color: #1e212b !important; border-bottom: 1px solid #334155 !important;}
  </style>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
        if(localStorage.getItem('a4s_admin_dark_mode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
        const darkModeToggle = document.getElementById('adminDarkModeToggle');
        if(darkModeToggle) {
            darkModeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('dark-mode');
                if(document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('a4s_admin_dark_mode', 'enabled');
                } else {
                    localStorage.setItem('a4s_admin_dark_mode', 'disabled');
                }
            });
        }

        const mobileBtn = document.getElementById('mobileBtn');
        const toggleBtn = document.getElementById('toggleBtn');
        const overlay = document.getElementById('overlay');

        function toggleSidebar(e) {
            e.preventDefault();
            document.body.classList.toggle('show-sidebar');
        }

        if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
        if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
        if (overlay) {
            overlay.addEventListener('click', function() {
                document.body.classList.remove('show-sidebar');
            });
        }
    });
  </script>
</body>
</html>