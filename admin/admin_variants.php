<?php
session_start();
require '../db.php';

// Security Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../index.php");
    exit();
}

$msg = "";

// Helper function to handle the image uploads securely
function uploadVariantImage($fileArray, $prefix) {
    if ($fileArray['error'] == 0) {
        $target_dir = "../images/variants/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed)) {
            // Generate a unique filename to prevent overwriting
            $new_name = $prefix . "_" . time() . "_" . rand(100, 999) . "." . $ext;
            $target_file = $target_dir . $new_name;
            
            if (move_uploaded_file($fileArray['tmp_name'], $target_file)) {
                // Return the relative path for the database (frontend looks in 'images/variants/')
                return "images/variants/" . $new_name;
            }
        }
    }
    return false;
}

// Handle Adding a New Variant
if (isset($_POST['add_variant'])) {
    $product_id = $_POST['product_id'];
    $color_name = $_POST['color_name'];
    
    // Process the 3 uploads
    $img_thumb = uploadVariantImage($_FILES['img_thumb'], "thumb");
    $img_side = uploadVariantImage($_FILES['img_side'], "side");
    $img_top = uploadVariantImage($_FILES['img_top'], "top");
    
    if ($img_thumb && $img_side && $img_top) {
        $sql = "INSERT INTO product_variants (product_id, color_name, img_side, img_top, img_thumb) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $product_id, $color_name, $img_side, $img_top, $img_thumb);
            if ($stmt->execute()) {
                $_SESSION['swal_msg'] = "Color variant '" . htmlspecialchars($color_name) . "' successfully added!";
                $_SESSION['swal_type'] = "success";
                header("Location: admin_variants.php");
                exit();
            } else {
                $msg = "<div class='alert alert-danger rounded-0 fw-bold'>Database error: Could not save variant.</div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger rounded-0 fw-bold'>Upload Failed. Ensure all 3 files are valid JPG/PNG images.</div>";
    }
}

// Handle Deleting a Variant
if (isset($_POST['delete_variant'])) {
    $variant_id = $_POST['variant_id'];
    
    // Optional: Fetch image paths to delete the physical files from the server
    $get_imgs = $conn->query("SELECT img_side, img_top, img_thumb FROM product_variants WHERE variant_id = $variant_id");
    if ($img_row = $get_imgs->fetch_assoc()) {
        @unlink("../" . $img_row['img_side']);
        @unlink("../" . $img_row['img_top']);
        @unlink("../" . $img_row['img_thumb']);
    }
    
    $del_sql = "DELETE FROM product_variants WHERE variant_id = ?";
    $stmt = $conn->prepare($del_sql);
    $stmt->bind_param("i", $variant_id);
    if ($stmt->execute()) {
        $_SESSION['swal_msg'] = "Variant permanently deleted.";
        $_SESSION['swal_type'] = "info";
        header("Location: admin_variants.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Color Variants - A4S Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" crossorigin href="./assets/css/main.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div id="overlay" class="overlay"></div>
  
  <nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
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
      <li><a class="nav-link active" href="admin_variants.php"><i class="ti ti-palette"></i><span class="nav-text">Color Variants</span></a></li>
      <li><a class="nav-link" href="admin_orders.php"><i class="ti ti-truck"></i><span class="nav-text">Orders</span></a></li>
      <li><a class="nav-link" href="admin_coupons.php"><i class="ti ti-ticket"></i><span class="nav-text">Coupons & Promos</span></a></li>
      
      <li class="px-4 pt-4 pb-2"><small class="nav-text text-muted">Support</small></li>
      <li><a class="nav-link" href="reports.php"><i class="ti ti-messages"></i><span class="nav-text">Inquiries</span></a></li>
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
          <h1 class="fs-3 mb-1">Color Variant Management</h1>
          <p class="mb-0 text-muted">Upload and link multiple image perspectives to create interactive colors for your gear.</p>
        </div>
      </div>

      <?php echo $msg; ?>

      <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 bg-white p-4">
                <h5 class="fw-bold mb-4 border-bottom pb-2">Add New Variant</h5>
                
                <form action="admin_variants.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Target Product</label>
                        <select name="product_id" class="form-select rounded-0 border-dark" required>
                            <option value="" disabled selected>Select a base product...</option>
                            <?php
                            $prods = $conn->query("SELECT prodID, name FROM product ORDER BY prodID DESC");
                            while($p = $prods->fetch_assoc()) {
                                echo "<option value='".$p['prodID']."'>".htmlspecialchars($p['name'])."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted text-uppercase">Color Name</label>
                        <input type="text" name="color_name" class="form-control rounded-0 border-dark" placeholder="e.g. Olive Drab / Black" required>
                    </div>

                    <div class="mb-3 border p-3 bg-light">
                        <label class="form-label fw-bold small text-dark"><i class="ti ti-square"></i> 1. Color Thumbnail</label>
                        <p class="small text-muted mb-2" style="font-size:0.75rem;">The tiny square the user clicks on.</p>
                        <input type="file" name="img_thumb" class="form-control form-control-sm rounded-0" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>

                    <div class="mb-3 border p-3 bg-light">
                        <label class="form-label fw-bold small text-dark"><i class="ti ti-shoe"></i> 2. Side Perspective</label>
                        <p class="small text-muted mb-2" style="font-size:0.75rem;">The default profile shot of the shoe.</p>
                        <input type="file" name="img_side" class="form-control form-control-sm rounded-0" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>

                    <div class="mb-4 border p-3 bg-light">
                        <label class="form-label fw-bold small text-dark"><i class="ti ti-view-360"></i> 3. Top Perspective</label>
                        <p class="small text-muted mb-2" style="font-size:0.75rem;">The image shown during the hover zoom.</p>
                        <input type="file" name="img_top" class="form-control form-control-sm rounded-0" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>

                    <button type="submit" name="add_variant" class="btn btn-dark w-100 rounded-0 py-2 fw-bold text-uppercase letter-spacing-1">Upload & Link Variant</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Color Name</th>
                                <th class="text-center">Thumb</th>
                                <th class="text-center">Side View</th>
                                <th class="text-center">Top View</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $list_sql = "
                                SELECT v.*, p.name 
                                FROM product_variants v 
                                JOIN product p ON v.product_id = p.prodID 
                                ORDER BY v.variant_id DESC
                            ";
                            $list_res = $conn->query($list_sql);

                            if ($list_res && $list_res->num_rows > 0) {
                                while($row = $list_res->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td class='fw-bold'>".htmlspecialchars($row['name'])."</td>";
                                    echo "<td>".htmlspecialchars($row['color_name'])."</td>";
                                    echo "<td class='text-center'><img src='../".htmlspecialchars($row['img_thumb'])."' style='width:35px; height:35px; object-fit:cover; border:1px solid #ccc;'></td>";
                                    echo "<td class='text-center'><img src='../".htmlspecialchars($row['img_side'])."' style='height:40px;'></td>";
                                    echo "<td class='text-center'><img src='../".htmlspecialchars($row['img_top'])."' style='height:40px;'></td>";
                                    echo "<td class='text-end pe-4'>
                                            <form method='POST' action='admin_variants.php' onsubmit='return confirm(\"Permanently delete this color variant?\");'>
                                                <input type='hidden' name='variant_id' value='".$row['variant_id']."'>
                                                <button type='submit' name='delete_variant' class='btn btn-sm btn-outline-danger border-0'><i class='ti ti-trash fs-5'></i></button>
                                            </form>
                                          </td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-5 text-muted'>No color variants have been created yet.</td></tr>";
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

  <?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
      Swal.fire({
        title: 'Success!',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: '<?php echo $_SESSION['swal_type']; ?>',
        confirmButtonColor: '#000',
        timer: 3000,
        showConfirmButton: false,
        background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
        color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#545454'
      });
    </script>
  <?php 
    unset($_SESSION['swal_msg']); 
    unset($_SESSION['swal_type']); 
  endif; 
  ?>

  <style>
      body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; }
      body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .dropdown-menu { background-color: #1e212b !important; border-color: #334155 !important; box-shadow: 0 4px 6px rgba(0,0,0,0.5) !important; }
      body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; }
      body.dark-mode .logo-area { background-color: #15171e !important; border-bottom: 1px solid #334155 !important; border-right: 1px solid #334155 !important; }
      body.dark-mode .text-dark, body.dark-mode h1, body.dark-mode h5, body.dark-mode .logo-text, body.dark-mode label { color: #f8fafc !important; }
      body.dark-mode .text-muted, body.dark-mode p { color: #94a3b8 !important; }
      body.dark-mode .nav-link, body.dark-mode .nav-link i, body.dark-mode .nav-link span { color: #cbd5e1 !important; }
      body.dark-mode .nav-link:hover, body.dark-mode .nav-link.active, body.dark-mode .nav-link.active i, body.dark-mode .nav-link.active span { color: #88c8bc !important; }
      body.dark-mode .bg-light, body.dark-mode .table-light, body.dark-mode .table-light th { background-color: #272b36 !important; color: #f8fafc !important; border-color: #334155 !important; }
      body.dark-mode .table td, body.dark-mode .table th { border-color: #334155 !important; color: #cbd5e1 !important; background-color: transparent !important; }
      body.dark-mode .form-control, body.dark-mode .form-select { background-color: #272b36 !important; border: 1px solid #334155 !important; color: #f8fafc !important; }
      body.dark-mode .border-bottom, body.dark-mode .border { border-color: #334155 !important; }
      body.dark-mode .btn-dark { background-color: #f8fafc !important; color: #0f111a !important; border:none;}
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