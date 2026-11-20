<?php
session_start();
require '../db.php'; 

// 1. Security Check: Admin Only
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../index.php");
    exit();
}

$action_success = false;
$action_message = "";

// 2. Process Approval or Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['refund_id'])) {
    $refund_id = (int)$_POST['refund_id'];
    $action = $_POST['action'];
    $order_id = (int)$_POST['order_id'];
    
    $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

    try {
        // Update the refund request status
        $updateReq = $conn->prepare("UPDATE refund_requests SET status = ? WHERE refund_id = ?");
        $updateReq->bind_param("si", $new_status, $refund_id);
        $updateReq->execute();

        // If approved, also update the main orders table to reflect the cancellation/refund
        if ($new_status === 'Approved') {
            $updateOrder = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE orderID = ?");
            $updateOrder->bind_param("i", $order_id);
            $updateOrder->execute();
        }

        $action_success = true;
        $action_message = "Refund request has been " . $new_status . " successfully.";
        
    } catch (Exception $e) {
        $action_message = "Database error. Could not process request.";
    }
}

// 3. Fetch all refund requests safely
$refunds = [];
try {
    $sql = "SELECT r.*, c.username, c.email, o.total_amount 
            FROM refund_requests r 
            JOIN customer c ON r.custID = c.custID 
            JOIN orders o ON r.orderID = o.orderID 
            ORDER BY r.request_date DESC";
    $res = $conn->query($sql);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            $refunds[] = $row;
        }
    }
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>A4S Admin - Refund Requests</title>
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
        <a class="position-relative btn-icon btn-sm btn-light btn rounded-circle" href="#" id="adminDarkModeToggle">
          <i class="ti ti-moon fs-5"></i>
        </a>
      </div>
      <div class="dropdown ms-2">
        <a href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <img src="./assets/images/avatar-1.jpg" alt="" class="avatar avatar-sm rounded-circle" />
        </a>
      </div>
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
      <li><a class="nav-link active" href="admin_refunds.php"><i class="ti ti-receipt-refund"></i><span class="nav-text">Refund Requests</span></a></li>
      
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
          <h1 class="fs-3 mb-1">Refund & Return Requests</h1>
          <p class="text-muted">Manage customer return claims and inspect photographic evidence.</p>
        </div>
      </div>

      <div class="card shadow-sm border-0 bg-white">
        <div class="table-responsive">
          <table class="table align-middle mb-0 table-hover">
            <thead class="table-light">
              <tr>
                <th class="py-3 px-4">Request Details</th>
                <th class="py-3">Reason</th>
                <th class="py-3">Evidence</th>
                <th class="py-3">Status</th>
                <th class="py-3 text-end px-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if(empty($refunds)): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted">No refund requests found.</td></tr>
              <?php else: ?>
                <?php foreach($refunds as $req): ?>
                  <tr>
                    <td class="px-4 py-3">
                        <div class="fw-bold text-dark">#ORD-<?php echo str_pad($req['orderID'], 5, '0', STR_PAD_LEFT); ?></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($req['username']); ?> (<?php echo htmlspecialchars($req['email']); ?>)</div>
                        <div class="small fw-semibold mt-1">RM <?php echo number_format($req['total_amount'], 2); ?></div>
                        <div class="small text-muted mt-1" style="font-size: 0.75rem;">Requested: <?php echo date('d M Y, H:i', strtotime($req['request_date'])); ?></div>
                    </td>
                    <td class="py-3">
                        <span class="badge bg-secondary mb-1"><?php echo htmlspecialchars($req['reason']); ?></span>
                        <p class="small text-muted mb-0" style="max-width: 250px; white-space: normal;"><?php echo htmlspecialchars($req['description']); ?></p>
                    </td>
                    <td class="py-3">
                        <?php 
                        $images = json_decode($req['evidence_image'], true);
                        if (!empty($images) && is_array($images)): 
                            foreach($images as $img): 
                                $clean_img = (strpos($img, '../') === 0) ? $img : '../' . $img;
                        ?>
                            <a href="<?php echo htmlspecialchars($clean_img); ?>" target="_blank" class="d-inline-block me-1 border rounded p-1 bg-white">
                                <img src="<?php echo htmlspecialchars($clean_img); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                            </a>
                        <?php 
                            endforeach; 
                        else: 
                        ?>
                            <span class="small text-muted">No images provided</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3">
                        <?php 
                            $status = $req['status'] ?? 'Pending';
                            if ($status === 'Approved') echo '<span class="badge bg-success-subtle text-success px-2 py-1">Approved</span>';
                            elseif ($status === 'Rejected') echo '<span class="badge bg-danger-subtle text-danger px-2 py-1">Rejected</span>';
                            else echo '<span class="badge bg-warning-subtle text-warning px-2 py-1">Pending</span>';
                        ?>
                    </td>
                    <td class="text-end px-4 py-3">
                        <?php if($status !== 'Approved' && $status !== 'Rejected'): ?>
                            <!-- REPLACED OLD POPUP WITH MODERN SWEETALERT INTEGRATION -->
                            <button type="button" class="btn btn-sm btn-success fw-bold px-3 me-2" onclick="processRefund('approve', <?php echo $req['refund_id']; ?>, <?php echo $req['orderID']; ?>)">Approve</button>
                            <button type="button" class="btn btn-sm btn-danger fw-bold px-3" onclick="processRefund('reject', <?php echo $req['refund_id']; ?>, <?php echo $req['orderID']; ?>)">Reject</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-secondary px-3" disabled>Processed</button>
                        <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Modern Interactive SWAL2 Function -->
  <script>
    function processRefund(action, refundId, orderId) {
        let titleText = action === 'approve' ? 'Approve Refund?' : 'Reject Refund?';
        let textText = action === 'approve' ? 'This will approve the return and cancel the original order.' : 'Are you sure you want to reject this request?';
        let confirmBtnColor = action === 'approve' ? '#198754' : '#dc3545';
        
        Swal.fire({
            title: titleText,
            text: textText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: confirmBtnColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, ' + action + ' it!',
            background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
            color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
        }).then((result) => {
            if (result.isConfirmed) {
                // Dynamically build and submit the form securely
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_refunds.php';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = action;
                form.appendChild(actionInput);
                
                const refundInput = document.createElement('input');
                refundInput.type = 'hidden'; refundInput.name = 'refund_id'; refundInput.value = refundId;
                form.appendChild(refundInput);
                
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden'; orderInput.name = 'order_id'; orderInput.value = orderId;
                form.appendChild(orderInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
  </script>

  <!-- Admin Dark Mode CSS (Table Blending Fixed!) -->
  <style>
      body.dark-mode { background-color: #0f111a !important; color: #cbd5e1 !important; }
      body.dark-mode .bg-white, body.dark-mode .card { background-color: #1e212b !important; border-color: #334155 !important; }
      body.dark-mode .topbar, body.dark-mode .sidebar { background-color: #15171e !important; border-color: #334155 !important; }
      body.dark-mode .logo-area { background-color: #15171e !important; border-bottom: 1px solid #334155 !important; border-right: 1px solid #334155 !important; }
      body.dark-mode .text-dark, body.dark-mode h1, body.dark-mode .logo-text, body.dark-mode .nav-text { color: #f8fafc !important; }
      body.dark-mode .text-muted, body.dark-mode p { color: #94a3b8 !important; }
      body.dark-mode .nav-link { color: #cbd5e1 !important; }
      body.dark-mode .nav-link:hover, body.dark-mode .nav-link.active { color: #88c8bc !important; }
      
      /* FIXED: Remove bright white table background in dark mode */
      body.dark-mode .table { --bs-table-bg: transparent !important; --bs-table-color: #cbd5e1 !important; color: #cbd5e1 !important; }
      body.dark-mode .table td, body.dark-mode .table th { background-color: transparent !important; border-bottom-color: #334155 !important; }
      body.dark-mode .table-hover tbody tr:hover td, body.dark-mode .table-hover tbody tr:hover th { background-color: rgba(255,255,255,0.05) !important; color: #fff !important; }
      body.dark-mode .table-light, body.dark-mode .table-light th { background-color: #272b36 !important; color: #f8fafc !important; border-color: #334155 !important; }
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
                localStorage.setItem('a4s_admin_dark_mode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
            });
        }
    });
  </script>

  <?php if($action_success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Success!',
                text: '<?php echo $action_message; ?>',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
                color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
            }).then(() => {
                window.location.href = 'admin_refunds.php';
            });
        });
    </script>
  <?php endif; ?>
</body>
</html>