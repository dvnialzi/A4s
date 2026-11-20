<?php
session_start();
require '../db.php'; 

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../login.php");
    exit();
}

if (isset($_POST['update_status'])) {
    $orderID = $_POST['orderID'];
    $new_status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$new_status' WHERE orderID = $orderID");
    header("Location: admin_orders.php"); exit();
}

$orders = $conn->query("SELECT * FROM orders ORDER BY orderID DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Orders - A4S Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" crossorigin href="./assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
  <div id="overlay" class="overlay"></div>
  <nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
    <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm "><i class="ti ti-layout-sidebar-left-expand"></i></button>
    <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2"><i class="ti ti-layout-sidebar-left-expand"></i></button>
    <div class="ms-auto d-flex align-items-center">
      <div class="dropdown me-3"><a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" id="adminDarkModeToggle"><i class="ti ti-moon fs-5"></i></a></div>
      <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
        <li class="dropdown"><a href="#" role="button" data-bs-toggle="dropdown"><img src="./assets/images/avatar-1.jpg" class="avatar avatar-sm rounded-circle" /></a>
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
            <div class="p-3 d-flex flex-column gap-1 small lh-lg">
                <a href="../login.php?logout=1" class="text-danger text-decoration-none"><i class="ti ti-logout me-2"></i><span>Logout</span></a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>

  <aside id="sidebar" class="sidebar">
    <div class="logo-area">
     <a href="index.php" class="d-inline-flex text-decoration-none"><span class="logo-text ms-2 fw-bold fs-4 text-dark">A4S Admin</span></a>
    </div>
    <ul class="nav flex-column mt-3">
      <li class="px-4 py-2"><small class="nav-text text-muted">Main</small></li>
      <li><a class="nav-link" href="index.php"><i class="ti ti-home"></i><span class="nav-text">Dashboard</span></a></li>
      <li><a class="nav-link" href="admin_products.php"><i class="ti ti-box-seam"></i><span class="nav-text">Inventory</span></a></li>
      <li><a class="nav-link" href="admin_variants.php"><i class="ti ti-palette"></i><span class="nav-text">Color Variants</span></a></li>
      <li><a class="nav-link active" href="admin_orders.php"><i class="ti ti-truck"></i><span class="nav-text">Orders</span></a></li>
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
      <div class="row mb-4"><div class="col-12"><h1 class="fs-3 mb-1">Order Management</h1><p class="text-muted">Process and track customer purchases and shoe sizes.</p></div></div>
      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Order ID</th><th>Items & Sizes</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($row = $orders->fetch_assoc()): ?>
                    <tr>
                        <td class="fw-bold">#ORD-<?php echo str_pad($row['orderID'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td>
                            <ul class="list-unstyled mb-0">
                            <?php 
                            $items = $conn->query("SELECT * FROM order_items WHERE orderID = " . $row['orderID']);
                            if($items) {
                                while($it = $items->fetch_assoc()) {
                                    $sz = (!empty($it['shoe_size']) && $it['shoe_size'] !== 'N/A') ? " <span class='badge bg-secondary ms-1'>Size: ".$it['shoe_size']."</span>" : "";
                                    echo "<li><span class='badge bg-dark me-1'>".$it['quantity']."x</span> ".$it['product_name'].$sz."</li>";
                                }
                            }
                            ?>
                            </ul>
                        </td>
                        <td class="fw-bold text-success">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><span class="badge <?php echo ($row['status']=='Pending')?'bg-warning':(($row['status']=='Completed')?'bg-success':'bg-danger'); ?>"><?php echo $row['status']; ?></span></td>
                        <td>
                            <form method="POST" action="admin_orders.php" class="d-flex gap-2">
                                <input type="hidden" name="orderID" value="<?php echo $row['orderID']; ?>">
                                <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                    <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Completed" <?php if($row['status']=='Completed') echo 'selected'; ?>>Completed</option>
                                    <option value="Cancelled" <?php if($row['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-sm btn-dark">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style> body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; } body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .dropdown-menu { background-color: #1e212b !important; border-color: #334155 !important; } body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; } body.dark-mode .text-dark, body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode label, body.dark-mode .nav-text, body.dark-mode td { color: #f8fafc !important; } body.dark-mode .table-light, body.dark-mode .table-light th { background-color: #272b36 !important; border-color: #334155 !important; color:#fff !important;} body.dark-mode .form-control, body.dark-mode .form-select { background-color: #272b36 !important; border: 1px solid #334155 !important; color: #f8fafc !important; } body.dark-mode td { border-bottom: 1px solid #334155 !important;} </style>
  <script> document.addEventListener("DOMContentLoaded", function() { if(localStorage.getItem('a4s_admin_dark_mode') === 'enabled') { document.body.classList.add('dark-mode'); } const darkModeToggle = document.getElementById('adminDarkModeToggle'); if(darkModeToggle) { darkModeToggle.addEventListener('click', function(e) { e.preventDefault(); document.body.classList.toggle('dark-mode'); localStorage.setItem('a4s_admin_dark_mode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); }); } }); </script>
</body>
</html>