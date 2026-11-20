<?php
session_start();
require '../db.php';

// Security: Admins and Super Admins only
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../index.php");
    exit();
}

$msg = "";

// Handle New Coupon Creation (Removed Theme)
if (isset($_POST['create_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = $_POST['discount_value'];

    $sql = "INSERT INTO coupons (code, discount_type, discount_value, is_active) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssd", $code, $type, $value);

    if ($stmt->execute()) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm' role='alert'>Coupon <strong>$code</strong> created successfully!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm' role='alert'>Error: That code might already exist.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}

// Handle Toggling Active Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $conn->query("UPDATE coupons SET is_active = IF(is_active=1, 0, 1) WHERE coupon_id = $id");
    header("Location: admin_coupons.php");
    exit();
}

// NEW: Handle Permanent Deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM coupons WHERE coupon_id = $id");
    header("Location: admin_coupons.php");
    exit();
}

$coupons = $conn->query("SELECT * FROM coupons ORDER BY coupon_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Coupons - A4S Admin</title>
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
      <div class="dropdown me-3">
        <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" id="adminDarkModeToggle" style="cursor: pointer;">
          <i class="ti ti-moon fs-5"></i>
        </a>
      </div>
      <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
        <li class="dropdown">
          <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
          </a>
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
            <div class="d-flex gap-3 align-items-center border-dashed border-bottom px-3 py-3">
              <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-md rounded-circle" />
              <div>
                <h4 class="mb-0 small">Admin <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h4>
                <p class="mb-0 small <?php echo ($_SESSION['role'] === 'super_admin') ? 'text-primary fw-bold' : ''; ?>">
                  <?php echo ($_SESSION['role'] === 'super_admin') ? 'Master Admin' : 'Store Manager'; ?>
                </p>
              </div>
            </div>
            <div class="p-3 d-flex flex-column gap-1 small lh-lg">
              <a href="../index.php?logout=1" class="text-danger text-decoration-none"><i class="ti ti-logout me-2"></i><span>Logout</span></a>
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
      <li><a class="nav-link" href="../index.php?logout=1"><i class="ti ti-logout"></i><span class="nav-text">Log out</span></a></li>
    </ul>
  </aside>

  <main id="content" class="content py-10" style="margin-top: 60px;">
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="fs-3 mb-1">Coupon Engine</h1>
          <p class="text-muted">Generate discount codes for the customer storefront checkout.</p>
        </div>
      </div>

      <?php echo $msg; ?>

      <div class="row">
        <!-- CREATE COUPON FORM -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Create New Promo</h5>
                    <form action="admin_coupons.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Promo Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. SALE50" required style="text-transform: uppercase;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Discount Type</label>
                            <select name="discount_type" class="form-select">
                                <option value="percentage">% Percentage Off</option>
                                <option value="flat">RM Flat Amount Off</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="e.g. 15.00" required>
                        </div>
                        <button type="submit" name="create_coupon" class="btn btn-primary w-100">Publish Coupon</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ACTIVE COUPONS LIST -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light border-light">
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($coupons->num_rows > 0) {
                                while($row = $coupons->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?php echo htmlspecialchars($row['code']); ?></strong></td>
                                <td>
                                    <?php 
                                        if($row['discount_type'] == 'percentage') echo $row['discount_value'] . "% Off";
                                        if($row['discount_type'] == 'flat') echo "RM " . $row['discount_value'] . " Off";
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['is_active']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="admin_coupons.php?toggle=<?php echo $row['coupon_id']; ?>" class="btn btn-sm btn-outline-secondary">Toggle</a>
                                        <a href="#" onclick="confirmDelete(<?php echo $row['coupon_id']; ?>)" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-4 text-muted'>No coupons created yet.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Coupon?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
            color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#545454'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'admin_coupons.php?delete=' + id;
            }
        });
    }
  </script>

  <!-- THE ULTIMATE ADMIN DARK MODE OVERRIDE V2 -->
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
      body.dark-mode .btn-outline-secondary { color: #cbd5e1 !important; border-color: #334155 !important; }
      body.dark-mode .btn-outline-secondary:hover { background-color: #334155 !important; color: #fff !important; }
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
    });
  </script>
</body>
</html>