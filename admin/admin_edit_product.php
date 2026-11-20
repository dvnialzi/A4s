<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) { header("Location: ../index.php"); exit(); }

$success_msg = ""; $error_msg = "";

$check_tags = $conn->query("SHOW COLUMNS FROM product LIKE 'search_tags'");
if ($check_tags && $check_tags->num_rows == 0) { $conn->query("ALTER TABLE product ADD COLUMN search_tags VARCHAR(255) DEFAULT ''"); }

if (isset($_POST['update_product'])) {
    $prodID = $_POST['prodID'];
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = $_POST['price'];
    $image = trim($_POST['image']); 
    $description = trim($_POST['description']);
    $search_tags = trim($_POST['search_tags']); 
    $size_stock = trim($_POST['size_stock']);

    $total_stock = 0;
    if (!empty($size_stock)) {
        $pairs = explode(',', $size_stock);
        foreach ($pairs as $pair) {
            $parts = explode(':', $pair);
            if (count($parts) == 2) { $total_stock += (int)trim($parts[1]); }
        }
    }
    $stock = $total_stock > 0 ? $total_stock : (int)$_POST['stock_quantity'];

    $update_sql = "UPDATE product SET name=?, price=?, stock_quantity=?, description=?, category=?, image=?, search_tags=? WHERE prodID=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sdissssi", $name, $price, $stock, $description, $category, $image, $search_tags, $prodID);

    if ($stmt->execute()) {
        // Destroy old sizes and insert the fresh ones!
        $conn->query("DELETE FROM product_sizes WHERE product_id = $prodID");
        if (!empty($size_stock)) {
            $pairs = explode(',', $size_stock);
            foreach ($pairs as $pair) {
                $parts = explode(':', $pair);
                if (count($parts) == 2) {
                    $s_name = $conn->real_escape_string(trim($parts[0]));
                    $s_qty = (int)trim($parts[1]);
                    $conn->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES ($prodID, '$s_name', $s_qty)");
                }
            }
        }
        $success_msg = "Product successfully updated!";
    } else {
        $error_msg = "Error updating product: " . $conn->error;
    }
}

if (!isset($_GET['id'])) { header("Location: admin_products.php"); exit(); }
$prodID = $_GET['id'];
$product = $conn->query("SELECT * FROM product WHERE prodID = $prodID")->fetch_assoc();
if (!$product) { header("Location: admin_products.php"); exit(); }

// Pre-fill existing sizes!
$size_str = "";
$sz_res = $conn->query("SELECT * FROM product_sizes WHERE product_id = $prodID");
$sz_arr = [];
if ($sz_res) {
    while($sz = $sz_res->fetch_assoc()) { $sz_arr[] = $sz['size_name'].":".$sz['stock_quantity']; }
    $size_str = implode(", ", $sz_arr);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit Product - A4S Admin Dashboard</title>
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
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;"><div class="p-3"><a href="../index.php?logout=1" class="text-danger text-decoration-none"><i class="ti ti-logout me-2"></i><span>Logout</span></a></div></div>
        </li>
      </ul>
    </div>
  </nav>

  <aside id="sidebar" class="sidebar">
    <div class="logo-area"><a href="index.php" class="d-inline-flex text-decoration-none"><span class="logo-text ms-2 fw-bold fs-4 text-dark">A4S Admin</span></a></div>
    <ul class="nav flex-column mt-3">
      <li class="px-4 py-2"><small class="nav-text text-muted">Main</small></li>
      <li><a class="nav-link" href="index.php"><i class="ti ti-home"></i><span class="nav-text">Dashboard</span></a></li>
      <li><a class="nav-link" href="admin_products.php"><i class="ti ti-box-seam"></i><span class="nav-text">Inventory</span></a></li>
      <li><a class="nav-link" href="admin_variants.php"><i class="ti ti-palette"></i><span class="nav-text">Color Variants</span></a></li>
      <li><a class="nav-link" href="admin_orders.php"><i class="ti ti-truck"></i><span class="nav-text">Orders</span></a></li>
      <li class="px-4 pt-4 pb-2"><small class="nav-text text-muted">Account</small></li>
      <li><a class="nav-link" href="../index.php?logout=1"><i class="ti ti-logout"></i><span class="nav-text">Log out</span></a></li>
    </ul>
  </aside>

  <main id="content" class="content py-10" style="margin-top: 60px;">
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div><h1 class="fs-3 mb-1">Edit Product</h1></div>
            <a href="admin_products.php" class="btn btn-outline-secondary">Back to Inventory</a>
        </div>
      </div>

      <div class="row">
        <div class="col-xl-8 col-lg-10 mx-auto">
          <?php if($success_msg != ""): ?><div class="alert alert-success alert-dismissible fade show shadow-sm"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
          <?php if($error_msg != ""): ?><div class="alert alert-danger alert-dismissible fade show shadow-sm"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

          <div class="card shadow-sm border-0">
            <div class="card-body p-4">
              <form action="admin_edit_product.php?id=<?php echo $product['prodID']; ?>" method="POST">
                <input type="hidden" name="prodID" value="<?php echo $product['prodID']; ?>">

                <div class="row mb-3">
                  <div class="col-md-6"><label class="form-label fw-bold">Shoe Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required></div>
                  <div class="col-md-6"><label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select form-control" required><option value="Men" <?php if($product['category'] == 'Men') echo 'selected'; ?>>Men</option><option value="Women" <?php if($product['category'] == 'Women') echo 'selected'; ?>>Women</option></select>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-6"><label class="form-label fw-bold">Price (RM)</label><div class="input-group"><span class="input-group-text bg-light">RM</span><input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($product['price']); ?>" required></div></div>
                  <div class="col-md-6"><label class="form-label fw-bold">Image URL</label><input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($product['image']); ?>" required></div>
                </div>

                <div class="mb-3"><label class="form-label fw-bold">Product Description</label><textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea></div>

                <div class="mb-4 p-3 bg-light rounded border">
                  <label class="form-label fw-bold text-primary"><i class="ti ti-ruler-measure"></i> Shoe Sizes & Inventory Mapping</label>
                  <input type="text" name="size_stock" class="form-control" value="<?php echo htmlspecialchars($size_str); ?>" placeholder="e.g. UK 7:15, UK 8:20, UK 9:5" required>
                  <small class="text-muted d-block mt-1">Format: <strong>Size:Stock</strong> separated by commas.</small>
                  <input type="hidden" name="stock_quantity" value="<?php echo htmlspecialchars($product['stock_quantity'] ?? $product['stock'] ?? 0); ?>">
                </div>

                <div class="mb-4 p-3 bg-light rounded border">
                  <label class="form-label fw-bold text-success"><i class="ti ti-sparkles"></i> AI Search Tags</label>
                  <input type="text" name="search_tags" class="form-control" value="<?php echo htmlspecialchars($product['search_tags'] ?? ''); ?>" placeholder="e.g. blue, red, running">
                </div>

                <div class="d-flex justify-content-end gap-2"><button type="submit" name="update_product" class="btn btn-primary px-4">Save Changes</button></div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <style> body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; } body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .dropdown-menu { background-color: #1e212b !important; border-color: #334155 !important; } body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; } body.dark-mode .text-dark, body.dark-mode h1, body.dark-mode h2, body.dark-mode h3, body.dark-mode h4, body.dark-mode label, body.dark-mode .form-label, body.dark-mode .nav-text { color: #f8fafc !important; } body.dark-mode .text-muted, body.dark-mode small { color: #94a3b8 !important; } body.dark-mode .form-control, body.dark-mode .form-select, body.dark-mode .input-group-text { background-color: #272b36 !important; border: 1px solid #334155 !important; color: #f8fafc !important; } body.dark-mode .bg-light { background-color: #272b36 !important; border-color: #334155 !important; } </style>
  <script> document.addEventListener("DOMContentLoaded", function() { if(localStorage.getItem('a4s_admin_dark_mode') === 'enabled') { document.body.classList.add('dark-mode'); } const darkModeToggle = document.getElementById('adminDarkModeToggle'); if(darkModeToggle) { darkModeToggle.addEventListener('click', function(e) { e.preventDefault(); document.body.classList.toggle('dark-mode'); localStorage.setItem('a4s_admin_dark_mode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled'); }); } }); </script>
</body>
</html>