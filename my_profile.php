<?php
session_start();
require 'db.php';

// Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$custID = $_SESSION['custID'];
$msg = "";

// 1. Handle Profile Update
if (isset($_POST['update_profile'])) {
    $fname = $_POST['firstname'];
    $lname = $_POST['lastname'];
    $phone = $_POST['phone'];
    
    $update_sql = "UPDATE customer SET firstname=?, lastname=?, phone=? WHERE custID=?";
    $stmt = $conn->prepare($update_sql);
    if($stmt) {
        $stmt->bind_param("sssi", $fname, $lname, $phone, $custID);
        if($stmt->execute()) {
            $msg = "<div class='alert alert-dark sharp-alert'>Profile updated successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger sharp-alert'>Error updating profile.</div>";
        }
    }
}

// 2. Handle Adding New Address
if (isset($_POST['add_address'])) {
    $label = $_POST['address_label'];
    $a_fname = $_POST['a_firstname'];
    $a_lname = $_POST['a_lastname'];
    $a_address = $_POST['a_address'];
    $a_town = $_POST['a_towncity'];
    $a_zip = $_POST['a_zipcode'];
    $a_phone = $_POST['a_phone'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    // If set to default, remove default status from all other addresses first
    if ($is_default == 1) {
        $conn->query("UPDATE customer_addresses SET is_default = 0 WHERE custID = $custID");
    }

    $add_sql = "INSERT INTO customer_addresses (custID, address_label, firstname, lastname, address, towncity, zipcode, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_add = $conn->prepare($add_sql);
    if($stmt_add) {
        $stmt_add->bind_param("isssssssi", $custID, $label, $a_fname, $a_lname, $a_address, $a_town, $a_zip, $a_phone, $is_default);
        if($stmt_add->execute()) {
            $msg = "<div class='alert alert-success sharp-alert' style='background: #28a745; color: #fff; border:none;'>New address added successfully.</div>";
        } else {
            $msg = "<div class='alert alert-danger sharp-alert'>Error adding address.</div>";
        }
    }
}

// Fetch Customer Data
$sql = "SELECT * FROM customer WHERE custID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $custID);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

include 'includes/header.php'; 
?>

<style>
    /* Premium Profile Styles */
    .dashboard-title { font-family: 'Futura Condensed', 'Oswald', sans-serif; font-size: 3rem; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 40px; color: #000; }
    .section-title { font-family: 'Futura Condensed', 'Oswald', sans-serif; font-size: 1.8rem; text-transform: uppercase; margin-bottom: 20px; color: #000; }
    
    .sharp-alert { border-radius: 0; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 500; text-transform: uppercase; letter-spacing: 1px; font-size: 0.85rem; }
    
    .profile-card { background: #fafafa; border: 1px solid #eee; padding: 30px; margin-bottom: 30px; }
    
    /* Brutalist Forms */
    .form-group label { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 700; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; color: #555; margin-bottom: 8px; }
    .form-control-sharp { border-radius: 0; border: 1px solid #ccc; padding: 12px 15px; font-family: 'Helvetica Neue', Helvetica, sans-serif; background: #fff; transition: 0.2s; }
    .form-control-sharp:focus { border-color: #000; box-shadow: none; outline: none; }
    .form-control-sharp:disabled { background: #f0f0f0; color: #888; }
    
    .btn-brutal { background: #000; color: #fff; border: 2px solid #000; border-radius: 0; padding: 12px 25px; font-family: 'Futura Condensed', 'Oswald', sans-serif; font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1.5px; transition: 0.3s; width: 100%; cursor: pointer; }
    .btn-brutal:hover { background: #fff; color: #000; }
    
    .btn-brutal-outline { background: transparent; color: #000; border: 2px solid #000; border-radius: 0; padding: 10px 20px; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; cursor: pointer;}
    .btn-brutal-outline:hover { background: #000; color: #fff; }

    /* Address List */
    .address-item { border-bottom: 1px solid #eee; padding: 15px 0; }
    .address-item:last-child { border-bottom: none; }
    .address-name { font-family: 'Futura Condensed', 'Oswald', sans-serif; font-size: 1.2rem; text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center; }
    .address-details { font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 0.95rem; color: #555; line-height: 1.5; }
    
    /* MODERN DOT BADGE */
    .badge-modern {
        display: inline-flex;
        align-items: center;
        font-family: 'Helvetica Neue', Helvetica, sans-serif;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 1px;
        color: #888;
        margin-left: 12px;
        text-transform: uppercase;
    }
    .badge-modern::before {
        content: '';
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #28a745;
        margin-right: 6px;
    }

    /* Modal Brutalist Styling */
    .modal-content { border-radius: 0; border: 2px solid #000; }
    .modal-header { border-bottom: 2px solid #000; border-radius: 0; background: #fafafa; }
    .modal-title { font-family: 'Futura Condensed', 'Oswald', sans-serif; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; }
    
    /* Dark Mode Overrides */
    body.dark-mode .dashboard-title, body.dark-mode .section-title { border-color: #fff; color: #fff; }
    body.dark-mode .profile-card { background: #111; border-color: #333; }
    body.dark-mode .form-group label { color: #aaa; }
    body.dark-mode .form-control-sharp { background: #1a1a1a; border-color: #444; color: #fff; }
    body.dark-mode .form-control-sharp:focus { border-color: #fff; }
    body.dark-mode .form-control-sharp:disabled { background: #222; }
    body.dark-mode .btn-brutal { background: #fff; color: #000; border-color: #fff; }
    body.dark-mode .btn-brutal:hover { background: #000; color: #fff; }
    body.dark-mode .btn-brutal-outline { color: #fff; border-color: #fff; }
    body.dark-mode .btn-brutal-outline:hover { background: #fff; color: #000; }
    body.dark-mode .address-item { border-color: #333; }
    body.dark-mode .address-details { color: #aaa; }
    body.dark-mode .modal-content { background: #111; border-color: #fff; }
    body.dark-mode .modal-header { background: #000; border-color: #fff; color: #fff; }
    body.dark-mode .close { color: #fff; text-shadow: none; }
</style>

<div class="container py-5 my-5">
    <h1 class="dashboard-title">Account Settings</h1>
    
    <?php echo $msg; ?>

    <div class="row">
        <!-- LEFT COLUMN: PERSONAL DETAILS -->
        <div class="col-lg-6 mb-5">
            <div class="profile-card">
                <h2 class="section-title">Personal Information</h2>
                <form action="my_profile.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3 form-group">
                            <label>First Name</label>
                            <input type="text" name="firstname" class="form-control form-control-sharp" value="<?php echo htmlspecialchars($user['firstname'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3 form-group">
                            <label>Last Name</label>
                            <input type="text" name="lastname" class="form-control form-control-sharp" value="<?php echo htmlspecialchars($user['lastname'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label>Email Address (Cannot be changed)</label>
                        <input type="email" class="form-control form-control-sharp" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                    </div>
                    
                    <div class="form-group mb-4">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control form-control-sharp" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-brutal">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- RIGHT COLUMN: ADDRESS BOOK -->
        <div class="col-lg-6">
            <div class="profile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0">Address Book</h2>
                    <!-- Changed from link to a Button that triggers the Modal -->
                    <button type="button" class="btn-brutal-outline" data-toggle="modal" data-target="#addAddressModal">
                        Add New
                    </button>
                </div>
                
                <div class="address-list mt-4">
                    <?php
                    // Fetch saved addresses
                    $addr_sql = "SELECT * FROM customer_addresses WHERE custID = $custID ORDER BY is_default DESC, address_id DESC";
                    $addr_res = $conn->query($addr_sql);
                    
                    if ($addr_res && $addr_res->num_rows > 0) {
                        while($addr = $addr_res->fetch_assoc()) {
                            
                            $default_badge = $addr['is_default'] ? "<span class='badge-modern'>Default</span>" : "";
                            
                            echo "
                            <div class='address-item'>
                                <div class='address-name'>" . htmlspecialchars($addr['address_label']) . $default_badge . "</div>
                                <div class='address-details'>
                                    " . htmlspecialchars($addr['firstname'] . ' ' . $addr['lastname']) . "<br>
                                    " . htmlspecialchars($addr['address']) . "<br>
                                    " . htmlspecialchars($addr['towncity']) . ", " . htmlspecialchars($addr['zipcode']) . "<br>
                                    T: " . htmlspecialchars($addr['phone']) . "
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<p style='font-family: \"Helvetica Neue\", sans-serif; color: #888;'>No addresses saved. Add one using the button above.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BRUTALIST ADD ADDRESS MODAL -->
<div class="modal fade" id="addAddressModal" tabindex="-1" role="dialog" aria-labelledby="addAddressModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="border:none; background:transparent; font-size:1.5rem; font-weight:700;">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4">
        <form action="my_profile.php" method="POST">
            
            <div class="form-group mb-4">
                <label>Address Label (e.g. Home, Office)</label>
                <input type="text" name="address_label" class="form-control form-control-sharp" placeholder="Home" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label>Recipient First Name</label>
                    <input type="text" name="a_firstname" class="form-control form-control-sharp" required>
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label>Recipient Last Name</label>
                    <input type="text" name="a_lastname" class="form-control form-control-sharp" required>
                </div>
            </div>
            
            <div class="form-group mb-3">
                <label>Street Address</label>
                <input type="text" name="a_address" class="form-control form-control-sharp" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <label>Town / City</label>
                    <input type="text" name="a_towncity" class="form-control form-control-sharp" required>
                </div>
                <div class="col-md-6 form-group mb-3">
                    <label>Zip / Postal Code</label>
                    <input type="text" name="a_zipcode" class="form-control form-control-sharp" required>
                </div>
            </div>
            
            <div class="form-group mb-4">
                <label>Phone Number</label>
                <input type="text" name="a_phone" class="form-control form-control-sharp" required>
            </div>
            
            <div class="form-check mb-4">
                <input type="checkbox" name="is_default" value="1" class="form-check-input" id="defaultCheck" style="border-radius: 0;">
                <label class="form-check-label" for="defaultCheck" style="font-size:0.85rem;">Set as default shipping address</label>
            </div>
            
            <button type="submit" name="add_address" class="btn-brutal">Add Address</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Required for the Modal to open if not already included in footer -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'includes/footer.php'; ?>