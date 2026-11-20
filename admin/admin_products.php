<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../login.php");
    exit();
}

$msg = "";

$check_tags = $conn->query("SHOW COLUMNS FROM product LIKE 'search_tags'");
if ($check_tags && $check_tags->num_rows == 0) { 
    $conn->query("ALTER TABLE product ADD COLUMN search_tags VARCHAR(255) DEFAULT ''"); 
}

try {
    $conn->query("CREATE TABLE IF NOT EXISTS product_sizes (
        size_id INT AUTO_INCREMENT PRIMARY KEY, 
        product_id INT NOT NULL, 
        size_name VARCHAR(50) NOT NULL, 
        stock_quantity INT DEFAULT 0
    )");
} catch (Exception $e) {}


function uploadProductImage($fileArray) {
    if (isset($fileArray) && $fileArray['error'] == 0) {
        $target_dir = "../images/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = "item_" . time() . "_" . rand(100, 999) . "." . $ext;
            $target_file = $target_dir . $new_name;
            if (move_uploaded_file($fileArray['tmp_name'], $target_file)) {
                return "images/" . $new_name;
            }
        }
    }
    return false;
}

$standard_sizes = ['UK 5', 'UK 6', 'UK 7', 'UK 8', 'UK 9', 'UK 10', 'UK 11', 'UK 12'];

// ==========================================
// 1. ADD NEW PRODUCT
// ==========================================
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category']; 
    $description = $_POST['description'];
    $search_tags = isset($_POST['search_tags']) ? trim($_POST['search_tags']) : '';
    
    $sizes_input = isset($_POST['sizes']) ? $_POST['sizes'] : [];
    
    $stock = 0;
    foreach ($sizes_input as $sz_name => $qty_str) {
        if (trim($qty_str) !== '') {
            $stock += max(0, (int)$qty_str);
        }
    }
    
    $image_path = uploadProductImage($_FILES['image']);
    if (!$image_path) { $image_path = "images/item-1.jpg"; } 
    $image_alt_path = uploadProductImage($_FILES['image_alt']);
    
    $sql = "INSERT INTO product (name, price, stock, category, description, image, image_alt, search_tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdisssss", $name, $price, $stock, $category, $description, $image_path, $image_alt_path, $search_tags);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        
        // Allows saving '0' explicitly
        foreach ($sizes_input as $sz_name => $qty_str) {
            if (trim($qty_str) !== '') {
                $qty = max(0, (int)$qty_str);
                $safe_name = $conn->real_escape_string($sz_name);
                $conn->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES ($new_id, '$safe_name', $qty)");
            }
        }
        
        $_SESSION['swal_msg'] = "Product successfully added to inventory.";
        $_SESSION['swal_type'] = "success";
        header("Location: admin_products.php");
        exit();
    }
}

// ==========================================
// 2. EDIT EXISTING PRODUCT
// ==========================================
if (isset($_POST['edit_product'])) {
    $prodID = $_POST['prodID'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $search_tags = isset($_POST['search_tags']) ? trim($_POST['search_tags']) : '';
    
    $sizes_input = isset($_POST['sizes']) ? $_POST['sizes'] : [];
    
    $stock = 0;
    foreach ($sizes_input as $sz_name => $qty_str) {
        if (trim($qty_str) !== '') {
            $stock += max(0, (int)$qty_str);
        }
    }
    
    $current_image = $_POST['current_image'];
    $current_image_alt = $_POST['current_image_alt'];
    
    $new_image_path = (isset($_FILES['image']) && $_FILES['image']['error'] == 0) ? uploadProductImage($_FILES['image']) : $current_image;
    $new_image_alt_path = (isset($_FILES['image_alt']) && $_FILES['image_alt']['error'] == 0) ? uploadProductImage($_FILES['image_alt']) : $current_image_alt;
    
    $sql = "UPDATE product SET name=?, price=?, stock=?, category=?, description=?, image=?, image_alt=?, search_tags=? WHERE prodID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdisssssi", $name, $price, $stock, $category, $description, $new_image_path, $new_image_alt_path, $search_tags, $prodID);
    
    if ($stmt->execute()) {
        $conn->query("DELETE FROM product_sizes WHERE product_id = $prodID");
        
        // Allows saving '0' explicitly
        foreach ($sizes_input as $sz_name => $qty_str) {
            if (trim($qty_str) !== '') {
                $qty = max(0, (int)$qty_str);
                $safe_name = $conn->real_escape_string($sz_name);
                $conn->query("INSERT INTO product_sizes (product_id, size_name, stock_quantity) VALUES ($prodID, '$safe_name', $qty)");
            }
        }
        
        $_SESSION['swal_msg'] = "Product updated successfully.";
        $_SESSION['swal_type'] = "success";
        header("Location: admin_products.php");
        exit();
    }
}

if (isset($_POST['delete_product'])) {
    $prodID = $_POST['prodID'];
    $sql = "DELETE FROM product WHERE prodID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $prodID);
    if ($stmt->execute()) {
        $conn->query("DELETE FROM product_sizes WHERE product_id = $prodID");
        $_SESSION['swal_msg'] = "Product permanently removed.";
        $_SESSION['swal_type'] = "info";
        header("Location: admin_products.php");
        exit();
    }
}

$products = $conn->query("SELECT * FROM product ORDER BY prodID DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Inventory Management - A4S Admin</title>
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
        <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" id="adminDarkModeToggle" style="cursor: pointer;"><i class="ti ti-moon fs-5"></i></a>
      </div>
      <ul class="list-unstyled d-flex align-items-center mb-0 gap-1">
        <li class="dropdown">
          <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" /></a>
          <div class="dropdown-menu dropdown-menu-end p-0" style="min-width: 200px;">
            <div class="d-flex gap-3 align-items-center border-dashed border-bottom px-3 py-3">
              <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-md rounded-circle" />
              <div>
                <h4 class="mb-0 small">Admin <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h4>
                <p class="mb-0 small text-primary fw-bold">Store Manager</p>
              </div>
            </div>
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
      <li><a class="nav-link active" href="admin_products.php"><i class="ti ti-box-seam"></i><span class="nav-text">Inventory</span></a></li>
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
      <div class="row mb-4 align-items-center">
        <div class="col-md-8">
          <h1 class="fs-3 mb-1">Inventory Operations</h1>
          <p class="mb-0 text-muted">Manage product details, pricing, sizes, and main display images.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <button class="btn btn-dark rounded-0 fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="ti ti-plus me-1"></i> ADD NEW GEAR
            </button>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Total Stock</th>
                                <th>Price</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products && $products->num_rows > 0): ?>
                                <?php while($row = $products->fetch_assoc()): 
                                    $imgRaw = !empty($row['image']) ? $row['image'] : 'images/item-1.jpg';
                                    $displaySrc = (strpos($imgRaw, 'http') === 0) ? $imgRaw : '../' . $imgRaw;
                                    
                                    $altRaw = !empty($row['image_alt']) ? $row['image_alt'] : '';
                                    $displayAltSrc = '';
                                    if ($altRaw) { $displayAltSrc = (strpos($altRaw, 'http') === 0) ? $altRaw : '../' . $altRaw; }

                                    $stock = isset($row['stock']) ? (int)$row['stock'] : 0;
                                    $stock_badge = $stock > 0 ? "<span class='badge bg-success'>$stock in stock</span>" : "<span class='badge bg-danger'>OUT OF STOCK</span>";
                                    
                                    $current_sizes = [];
                                    $sz_res = $conn->query("SELECT * FROM product_sizes WHERE product_id = " . $row['prodID']);
                                    if ($sz_res && $sz_res->num_rows > 0) {
                                        while($sz = $sz_res->fetch_assoc()) { 
                                            $current_sizes[$sz['size_name']] = $sz['stock_quantity']; 
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($displaySrc); ?>" alt="Shoe" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #eee; border-radius: 4px;">
                                        </td>
                                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                        <td><?php echo $stock_badge; ?></td>
                                        <td class="text-success fw-semibold">RM <?php echo number_format($row['price'], 2); ?></td>
                                        <td class="text-end pe-4">
                                            <button class="btn btn-sm btn-outline-dark border-2 me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['prodID']; ?>">
                                                <i class="ti ti-edit"></i> Edit
                                            </button>
                                            
                                            <form method="POST" action="admin_products.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="prodID" value="<?php echo $row['prodID']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal<?php echo $row['prodID']; ?>" tabindex="-1" aria-hidden="true">
                                      <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content rounded-0 border-dark">
                                          <div class="modal-header border-bottom border-dark bg-light">
                                            <h5 class="modal-title fw-bold text-uppercase">Edit Product: <?php echo htmlspecialchars($row['name']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                          </div>
                                          <div class="modal-body p-4 text-start">
                                            <form action="admin_products.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="prodID" value="<?php echo $row['prodID']; ?>">
                                                <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($row['image']); ?>">
                                                <input type="hidden" name="current_image_alt" value="<?php echo htmlspecialchars($row['image_alt'] ?? ''); ?>">
                                                
                                                <div class="row">
                                                    <div class="col-md-8 mb-3">
                                                        <label class="form-label fw-bold small text-muted text-uppercase">Product Name</label>
                                                        <input type="text" name="name" class="form-control rounded-0 border-dark" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label fw-bold small text-muted text-uppercase">Price (RM)</label>
                                                        <input type="number" step="0.01" name="price" class="form-control rounded-0 border-dark" value="<?php echo $row['price']; ?>" required>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-muted text-uppercase">Category</label>
                                                    <select name="category" class="form-select rounded-0 border-dark" required>
                                                        <option value="Men" <?php if($row['category'] == 'Men') echo 'selected'; ?>>Men's Footwear</option>
                                                        <option value="Women" <?php if($row['category'] == 'Women') echo 'selected'; ?>>Women's Footwear</option>
                                                    </select>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold small text-muted text-uppercase">Product Description</label>
                                                    <textarea name="description" class="form-control rounded-0 border-dark" rows="4"><?php echo htmlspecialchars($row['description'] ?? ''); ?></textarea>
                                                </div>

                                                <div class="mb-3 p-3 bg-light border border-primary">
                                                    <label class="form-label fw-bold text-primary mb-3"><i class="ti ti-ruler-measure"></i> Manage Shoe Sizes & Stock</label>
                                                    <div class="row g-2">
                                                        <?php foreach($standard_sizes as $sz): 
                                                            $sz_val = isset($current_sizes[$sz]) ? $current_sizes[$sz] : '';
                                                        ?>
                                                        <div class="col-4 col-md-3">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-dark text-white fw-bold border-dark"><?php echo $sz; ?></span>
                                                                <input type="number" name="sizes[<?php echo $sz; ?>]" class="form-control text-center border-dark" value="<?php echo $sz_val; ?>" min="0" placeholder="-">
                                                            </div>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="mb-3 p-3 bg-light border border-success">
                                                    <label class="form-label fw-bold text-success"><i class="ti ti-sparkles"></i> AI Search Tags</label>
                                                    <input type="text" name="search_tags" class="form-control rounded-0 border-dark" value="<?php echo htmlspecialchars($row['search_tags'] ?? ''); ?>" placeholder="e.g. blue, waterproof, hiking">
                                                </div>

                                                <div class="mb-3 border p-3 bg-light">
                                                    <label class="form-label fw-bold small text-dark"><i class="ti ti-camera"></i> Update Main Image</label>
                                                    <div class="d-flex align-items-center gap-3 mb-2">
                                                        <img src="<?php echo htmlspecialchars($displaySrc); ?>" class="border" style="width:60px; height:60px; object-fit:cover;">
                                                    </div>
                                                    <input type="file" name="image" class="form-control rounded-0 border-dark" accept=".jpg,.jpeg,.png,.webp">
                                                </div>
                                                
                                                <div class="mb-4 border p-3 bg-light">
                                                    <label class="form-label fw-bold small text-dark"><i class="ti ti-view-360"></i> Alternate View (Hover Image)</label>
                                                    <div class="d-flex align-items-center gap-3 mb-2">
                                                        <?php if ($displayAltSrc): ?>
                                                            <img src="<?php echo htmlspecialchars($displayAltSrc); ?>" class="border" style="width:60px; height:60px; object-fit:cover;">
                                                        <?php else: ?>
                                                            <div class="border d-flex align-items-center justify-content-center bg-white" style="width:60px; height:60px;"><i class="ti ti-photo-off text-muted"></i></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="file" name="image_alt" class="form-control rounded-0 border-dark" accept=".jpg,.jpeg,.png,.webp">
                                                </div>
                                                
                                                <button type="submit" name="edit_product" class="btn btn-dark w-100 rounded-0 py-3 fw-bold text-uppercase letter-spacing-1">Save Changes</button>
                                            </form>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Your inventory is empty.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
      </div>
    </div>
  </main>

  <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content rounded-0 border-dark">
        <div class="modal-header border-bottom border-dark bg-light">
          <h5 class="modal-title fw-bold text-uppercase">Add New Gear</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4 text-start">
          <form action="admin_products.php" method="POST" enctype="multipart/form-data">
              <div class="row">
                  <div class="col-md-8 mb-3">
                      <label class="form-label fw-bold small text-muted text-uppercase">Product Name</label>
                      <input type="text" name="name" class="form-control rounded-0 border-dark" required>
                  </div>
                  <div class="col-md-4 mb-3">
                      <label class="form-label fw-bold small text-muted text-uppercase">Price (RM)</label>
                      <input type="number" step="0.01" name="price" class="form-control rounded-0 border-dark" required>
                  </div>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted text-uppercase">Category</label>
                  <select name="category" class="form-select rounded-0 border-dark" required>
                      <option value="Men">Men's Footwear</option>
                      <option value="Women">Women's Footwear</option>
                  </select>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold small text-muted text-uppercase">Product Description</label>
                  <textarea name="description" class="form-control rounded-0 border-dark" rows="4"></textarea>
              </div>

              <div class="mb-3 p-3 bg-light border border-primary">
                  <label class="form-label fw-bold text-primary mb-3"><i class="ti ti-ruler-measure"></i> Manage Shoe Sizes & Stock</label>
                  <div class="row g-2">
                      <?php foreach($standard_sizes as $sz): ?>
                      <div class="col-4 col-md-3">
                          <div class="input-group input-group-sm">
                              <span class="input-group-text bg-dark text-white fw-bold border-dark"><?php echo $sz; ?></span>
                              <input type="number" name="sizes[<?php echo $sz; ?>]" class="form-control text-center border-dark" value="" min="0" placeholder="-">
                          </div>
                      </div>
                      <?php endforeach; ?>
                  </div>
              </div>

              <div class="mb-3 p-3 bg-light border border-success">
                  <label class="form-label fw-bold text-success"><i class="ti ti-sparkles"></i> AI Search Tags</label>
                  <input type="text" name="search_tags" class="form-control rounded-0 border-dark" placeholder="e.g. blue, waterproof, hiking">
              </div>

              <div class="mb-3 border p-3 bg-light">
                  <label class="form-label fw-bold small text-dark"><i class="ti ti-upload"></i> Upload Main Image</label>
                  <input type="file" name="image" class="form-control rounded-0 border-dark" accept=".jpg,.jpeg,.png,.webp" required>
              </div>
              
              <div class="mb-4 border p-3 bg-light">
                  <label class="form-label fw-bold small text-dark"><i class="ti ti-view-360"></i> Alternate View (Hover Image)</label>
                  <input type="file" name="image_alt" class="form-control rounded-0 border-dark" accept=".jpg,.jpeg,.png,.webp">
              </div>
              
              <button type="submit" name="add_product" class="btn btn-dark w-100 rounded-0 py-3 fw-bold text-uppercase letter-spacing-1">Publish to Store</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
      Swal.fire({
        title: 'Inventory Updated',
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
      body.dark-mode .modal-content, body.dark-mode .modal-header, body.dark-mode .modal-body { background-color: #1e212b !important; color: #f8fafc !important; border-color: #334155 !important; }
      body.dark-mode .btn-dark, body.dark-mode .bg-dark { background-color: #f8fafc !important; color: #0f111a !important; border:none;}
      body.dark-mode .btn-close-white { filter: invert(1); }
      body.dark-mode .btn-outline-dark { color: #f8fafc !important; border-color: #f8fafc !important; }
      body.dark-mode .btn-outline-dark:hover { background-color: #f8fafc !important; color: #0f111a !important; }
      body.dark-mode .input-group-text { background-color: #1e212b !important; border-color: #334155 !important; color: #fff !important;}
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