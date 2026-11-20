<?php
session_start();
require '../db.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../index.php");
    exit();
}

if (isset($_POST['resolve_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    $update_sql = "UPDATE contact_messages SET status = 'Resolved' WHERE msgID = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $ticket_id);
    if ($stmt->execute()) {
        $_SESSION['swal_msg'] = "Ticket #" . $ticket_id . " marked as resolved!";
        header("Location: reports.php");
        exit();
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    $del_sql = "DELETE FROM contact_messages WHERE msgID = ?";
    $stmt = $conn->prepare($del_sql);
    $stmt->bind_param("i", $del_id);
    if ($stmt->execute()) {
        header("Location: reports.php");
        exit();
    }
}

$sql = "SELECT * FROM contact_messages ORDER BY msgID DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Customer Service - A4S Admin Dashboard</title>
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
      
      <!-- Admin Dark Mode Toggle -->
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
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
              <h1 class="fs-3 mb-1">Customer Service Hub</h1>
              <p class="mb-0 text-muted">Manage storefront inquiries, complaints, and support tickets</p>
            </div>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card table-responsive shadow-sm border-0">
            <table class="table mb-0 table-hover align-middle">
              <thead class="table-light border-light">
                <tr>
                  <th style="width: 10%;">Ticket ID</th>
                  <th style="width: 20%;">Customer Info</th>
                  <th style="width: 40%;">Message Preview</th>
                  <th style="width: 10%;">Status</th>
                  <th style="width: 20%;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($ticket = $result->fetch_assoc()) {
                        $t_id = $ticket['msgID'];
                        $fname = $ticket['firstname'] ?? '';
                        $lname = $ticket['lastname'] ?? '';
                        $t_name = trim($fname . ' ' . $lname) ?: 'Unknown User';
                        $t_email = $ticket['email'] ?? 'No email provided';
                        $t_subj = $ticket['subject'] ?? 'No Subject';
                        $t_msg = $ticket['message'] ?? '';
                        $t_status = $ticket['status'] ?? 'Pending';
                        
                        $badgeClass = ($t_status == 'Resolved') ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning";
                        $short_msg = (strlen($t_msg) > 60) ? substr($t_msg, 0, 60) . "..." : $t_msg;

                        ?>
                        <tr>
                          <td><strong class="text-primary">#TKT-<?php echo str_pad($t_id, 4, '0', STR_PAD_LEFT); ?></strong></td>
                          <td>
                            <p class="mb-0 fw-semibold"><?php echo htmlspecialchars($t_name); ?></p>
                            <small class="text-muted"><?php echo htmlspecialchars($t_email); ?></small>
                          </td>
                          <td style="max-width: 300px; white-space: normal;">
                             <div class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($t_subj); ?></div>
                             <div class="text-muted small">"<?php echo htmlspecialchars($short_msg); ?>"</div>
                          </td>
                          <td>
                             <span class="badge <?php echo $badgeClass; ?> border-0 rounded-pill px-3 py-2">
                                <?php echo htmlspecialchars($t_status); ?>
                             </span>
                          </td>
                          <td>
                            <div class="d-flex gap-2 align-items-center">
                              <button type="button" class="btn btn-sm btn-light border shadow-sm" data-bs-toggle="modal" data-bs-target="#modal<?php echo $t_id; ?>">Read</button>
                              <?php if($t_status != 'Resolved'): ?>
                              <form action="reports.php" method="POST" class="m-0 p-0">
                                <input type="hidden" name="ticket_id" value="<?php echo $t_id; ?>">
                                <button type="submit" name="resolve_ticket" class="btn btn-sm btn-success shadow-sm">Resolve</button>
                              </form>
                              <?php endif; ?>
                              <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $t_id; ?>)" class="text-danger ms-2" title="Delete Ticket"><i class="ti ti-trash fs-5"></i></a>
                            </div>

                            <div class="modal fade" id="modal<?php echo $t_id; ?>" tabindex="-1" aria-hidden="true">
                              <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                  <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($t_subj); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body pt-3 pb-4">
                                    <p class="text-muted small mb-3">From: <strong><?php echo htmlspecialchars($t_name); ?></strong> (<?php echo htmlspecialchars($t_email); ?>)</p>
                                    <p class="mb-4 text-dark lh-lg" style="white-space: pre-wrap; background-color: #f8f9fa; padding: 15px; border-radius: 8px;"><?php echo htmlspecialchars($t_msg); ?></p>
                                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                      <small class="text-muted">Reply directly via email:</small>
                                      <a href="mailto:<?php echo htmlspecialchars($t_email); ?>?subject=Re: <?php echo urlencode($t_subj); ?> (Ticket #TKT-<?php echo str_pad($t_id, 4, '0', STR_PAD_LEFT); ?>)" class="btn btn-primary btn-sm">
                                        <i class="ti ti-mail me-1"></i> Email Customer
                                      </a>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                          </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='ti ti-inbox fs-1 d-block mb-2' style='font-size: 2.5rem;'></i>No support tickets found.</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <?php if(isset($_SESSION['swal_msg'])): ?>
    <script>
      Swal.fire({
        title: 'Action Successful',
        text: '<?php echo $_SESSION['swal_msg']; ?>',
        icon: 'success',
        confirmButtonColor: '#198754',
        timer: 3000,
        showConfirmButton: false
      });
    </script>
  <?php unset($_SESSION['swal_msg']); endif; ?>
  <script>
    function confirmDelete(ticketId) {
      Swal.fire({
        title: 'Delete this ticket?',
        text: "This will permanently erase the customer's message.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', 
        cancelButtonColor: '#6c757d', 
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'reports.php?delete_id=' + ticketId;
        }
      })
    }
  </script>


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