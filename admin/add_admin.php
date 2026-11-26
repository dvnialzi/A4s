<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

$success_msg = "";
$error_msg = "";

if (isset($_POST['create_admin'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password']; 

    $check_sql = "SELECT * FROM admin WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $new_username, $new_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (strlen($new_password) < 8) {
        $error_msg = "Use a password of at least 8 characters.";
    } elseif ($result->num_rows > 0) {
        $error_msg = "An account with that username or email already exists!";
    } else {
        // Store a hash, never the password itself
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $insert_sql = "INSERT INTO admin (username, password, email, role) VALUES (?, ?, ?, 'admin')";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $new_username, $hashed_password, $new_email);

        if ($insert_stmt->execute()) {
            $success_msg = "Success! The new admin account for <strong>" . htmlspecialchars($new_username) . "</strong> has been created.";
        } else {
            $error_msg = "Error creating account: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add Admin - A4S Master Dashboard</title>
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
                <p class="mb-0 small text-primary fw-bold">Master Admin</p>
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
          <div>
            <h1 class="fs-3 mb-1">Create Admin Account</h1>
            <p class="mb-0 text-muted">Provision a new staff account with standard inventory and support access.</p>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xl-6 col-lg-8 mx-auto">
          
          <?php if($success_msg != ""): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
              <i class="ti ti-check me-2"></i> <?php echo $success_msg; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          
          <?php if($error_msg != ""): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
              <i class="ti ti-alert-triangle me-2"></i> <?php echo $error_msg; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <div class="card shadow-sm border-0 mt-3">
            <div class="card-body p-4">
              <form action="add_admin.php" method="POST">
                
                <div class="mb-3">
                  <label class="form-label fw-bold">Username</label>
                  <input type="text" name="username" class="form-control" placeholder="e.g. StaffJohn" required>
                  <small class="text-muted">This is what the staff member will use to log in.</small>
                </div>

                <div class="mb-3">
                  <label class="form-label fw-bold">Email Address</label>
                  <input type="email" name="email" class="form-control" placeholder="staff@a4s.com" required>
                </div>

                <div class="mb-4">
                  <label class="form-label fw-bold">Password</label>
                  <input type="password" name="password" class="form-control" placeholder="Create a strong password" required minlength="8">
                </div>
                
                <div class="p-3 bg-light rounded mb-4 border">
                    <p class="small mb-0 text-muted">
                        <i class="ti ti-info-circle text-primary me-1"></i> 
                        <strong>Notice:</strong> This account will be created with standard <code>admin</code> privileges. They will not have access to this creation page.
                    </p>
                </div>

                <div class="d-flex justify-content-end gap-2">
                  <button type="reset" class="btn btn-light border">Clear</button>
                  <button type="submit" name="create_admin" class="btn btn-primary px-4">Create Account</button>
                </div>
                
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- THE ULTIMATE ADMIN DARK MODE OVERRIDE -->
  <style>
      /* 1. Base Backgrounds & Core Colors */
      body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; }
      body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .dropdown-menu { background-color: #1e212b !important; border-color: #334155 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.5) !important; }
      body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; }
      
      /* 2. FIX: Top-Left Logo Area */
      body.dark-mode .logo-area { background-color: #15171e !important; border-bottom: 1px solid #334155 !important; border-right: 1px solid #334155 !important; }
      
      /* 3. FIX: Aggressive Text Color Overrides (Solving the invisible black text) */
      body.dark-mode .text-dark, 
      body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode h5, body.dark-mode h6, 
      body.dark-mode .logo-text, 
      body.dark-mode strong,
      body.dark-mode label,
      body.dark-mode .form-label,
      body.dark-mode .nav-text { 
          color: #f8fafc !important; 
      }
      
      body.dark-mode .text-muted, body.dark-mode small, body.dark-mode p { color: #94a3b8 !important; }
      
      /* 4. FIX: Sidebar Links Visibility */
      body.dark-mode .nav-link, body.dark-mode .nav-link i, body.dark-mode .nav-link span { color: #cbd5e1 !important; }
      body.dark-mode .nav-link:hover, body.dark-mode .nav-link.active, body.dark-mode .nav-link.active i, body.dark-mode .nav-link.active span { color: #88c8bc !important; }
      
      /* 5. Form Inputs, Tables & UI Elements */
      body.dark-mode .bg-light, body.dark-mode .table-light, body.dark-mode .table-light th, body.dark-mode .input-group-text { background-color: #272b36 !important; color: #f8fafc !important; border-color: #334155 !important; }
      body.dark-mode .table td, body.dark-mode .table th, body.dark-mode .list-group-item, body.dark-mode .border-bottom { border-color: #334155 !important; color: #cbd5e1 !important; background-color: transparent !important; }
      
      /* 6. FIX: Input Fields Visibility */
      body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode input, body.dark-mode textarea { background-color: #272b36 !important; border: 1px solid #334155 !important; color: #f8fafc !important; }
      body.dark-mode .form-control:focus, body.dark-mode .form-select:focus { background-color: #1e212b !important; border-color: #88c8bc !important; color: #f8fafc !important; box-shadow: 0 0 0 0.25rem rgba(136, 200, 188, 0.25) !important; }
      
      /* 7. Modals */
      body.dark-mode .modal-content, body.dark-mode .modal-header, body.dark-mode .modal-body { background-color: #1e212b !important; color: #f8fafc !important; border-color: #334155 !important; }
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