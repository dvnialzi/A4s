<?php
session_start();
require 'db.php';

// Enable strict error reporting 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$message_sent = false;
$refund_sent = false;
$db_error_message = "";

// ==========================================
// AGGRESSIVE AUTO-HEALING DATABASE LOGIC
// Forces missing columns to be created even if the table already exists!
// ==========================================
try {
    // 1. Ensure tables exist with primary keys
    $conn->query("CREATE TABLE IF NOT EXISTS contact_messages (msgID INT AUTO_INCREMENT PRIMARY KEY)");
    $conn->query("CREATE TABLE IF NOT EXISTS refund_requests (refund_id INT AUTO_INCREMENT PRIMARY KEY)");

    // 2. Aggressively inject missing columns into contact_messages
    $contact_cols = [
        'name' => 'VARCHAR(255)',
        'email' => 'VARCHAR(255)',
        'subject' => 'VARCHAR(255)',
        'message' => 'TEXT',
        'status' => "VARCHAR(50) DEFAULT 'Pending'",
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    foreach ($contact_cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM contact_messages LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE contact_messages ADD COLUMN `$col` $def");
        }
    }
    
    // 3. Aggressively inject missing columns into refund_requests
    $refund_cols = [
        'custID' => 'INT',
        'orderID' => 'INT',
        'reason' => 'VARCHAR(255)',
        'description' => 'TEXT',
        'evidence_image' => 'TEXT',
        'status' => "VARCHAR(50) DEFAULT 'Pending'",
        'request_date' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];
    foreach ($refund_cols as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM refund_requests LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $conn->query("ALTER TABLE refund_requests ADD COLUMN `$col` $def");
        }
    }
} catch (Exception $e) {}


// Fetch customer data if logged in
$cust_name = "";
$cust_email = "";
$custID = 0;
$customer_orders = [];

if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    $custID = $_SESSION['custID'];
    
    try {
        $stmtC = $conn->prepare("SELECT username, email FROM customer WHERE custID = ?");
        $stmtC->bind_param("i", $custID);
        $stmtC->execute();
        $customer = $stmtC->get_result()->fetch_assoc();
        if ($customer) {
            $cust_name = $customer['username'];
            $cust_email = $customer['email'];
        }
    } catch (Exception $e) {}
    
    // Fetch Eligible Orders
    try {
        $sql = "SELECT o.* FROM orders o 
                LEFT JOIN refund_requests r ON o.orderID = r.orderID 
                WHERE o.custID = ? AND r.orderID IS NULL 
                ORDER BY o.orderID DESC";
                
        $stmtO = $conn->prepare($sql);
        if ($stmtO) {
            $stmtO->bind_param("i", $custID);
            $stmtO->execute();
            $resO = $stmtO->get_result();
            while($row = $resO->fetch_assoc()) {
                $customer_orders[] = $row;
            }
        }
    } catch (Exception $e) {
        try {
            $stmtO = $conn->prepare("SELECT * FROM orders WHERE custID = ? ORDER BY orderID DESC");
            if ($stmtO) {
                $stmtO->bind_param("i", $custID);
                $stmtO->execute();
                $resO = $stmtO->get_result();
                while($row = $resO->fetch_assoc()) {
                    $customer_orders[] = $row;
                }
            }
        } catch (Exception $fallbackE) { }
    }
}

// ==========================================
// 1. Process the General Contact Form
// ==========================================
if (isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    try {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        $stmt->execute();
        
        $message_sent = true; 
    } catch (Exception $e) {
        $db_error_message = $e->getMessage();
    } 
}

// ==========================================
// 2. Process the Refund Request Form 
// ==========================================
if (isset($_POST['submit_refund'])) {
    $orderID = (int) $_POST['order_number'];
    $reason = trim($_POST['reason']);
    $description = trim($_POST['details']); 
    
    // Process Images
    $uploaded_paths = [];
    if (isset($_FILES['refund_images']) && !empty($_FILES['refund_images']['name'][0])) {
        $file_count = count($_FILES['refund_images']['name']);
        
        if ($file_count >= 1 && $file_count <= 5) {
            $upload_dir = 'images/refunds/'; 
            if (!file_exists($upload_dir)) { mkdir($upload_dir, 0777, true); }

            for ($i = 0; $i < $file_count; $i++) {
                $tmp_name = $_FILES['refund_images']['tmp_name'][$i];
                $ext = strtolower(pathinfo($_FILES['refund_images']['name'][$i], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $new_name = "refund_" . $orderID . "_" . time() . "_" . $i . "." . $ext;
                    $target_path = $upload_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $uploaded_paths[] = $target_path;
                    }
                }
            }
        }
    }
    
    $images_json = json_encode($uploaded_paths);
    
    try {
        $stmtR = $conn->prepare("INSERT INTO refund_requests (custID, orderID, reason, description, evidence_image) VALUES (?, ?, ?, ?, ?)");
        $stmtR->bind_param("iisss", $custID, $orderID, $reason, $description, $images_json);
        $stmtR->execute();
        
        $refund_sent = true;
    } catch (Exception $e) {
        $db_error_message = $e->getMessage();
    } 
}

include 'includes/header.php'; 
?>

<style>
    body { background: #f8f9fa; }
    .support-wrapper { margin-top: 60px; margin-bottom: 100px; }
    .support-card { background: #fff; padding: 50px; border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.05); height: 100%; border-radius: 8px;}
    .support-card.bg-light-custom { background: #fafafa; border: 1px solid #e9ecef; }
    .section-title { font-family: 'Futura Condensed', sans-serif; font-size: 2.5rem; text-transform: uppercase; font-weight: 800; letter-spacing: 1px; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 30px; }
    .support-info-title { font-family: 'Futura Condensed', sans-serif; font-size: 1.5rem; text-transform: uppercase; font-weight: 700; margin-top: 30px; margin-bottom: 10px; }
    .support-info-text { font-family: 'Helvetica Neue', sans-serif; font-size: 1rem; color: #555; line-height: 1.6; }
    .form-group { margin-bottom: 25px; }
    .form-label { font-family: 'Helvetica Neue', sans-serif; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-bottom: 8px; display: block; }
    .form-control-sharp { width: 100%; padding: 15px; border: 1px solid #ccc; background: #fafafa; font-family: 'Helvetica Neue', sans-serif; outline: none; transition: 0.3s; border-radius: 4px; }
    .form-control-sharp:focus { border-color: #000; background: #fff; box-shadow: 0 0 8px rgba(0,0,0,0.1); }
    input[type=file]::file-selector-button { background: #000; color: #fff; border: none; padding: 8px 15px; font-family: 'Futura Condensed', sans-serif; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s; margin-right: 15px; border-radius: 4px; }
    input[type=file]::file-selector-button:hover { background: #333; }
    .btn-submit { background: #000; color: #fff; border: none; width: 100%; padding: 20px; font-family: 'Futura Condensed', sans-serif; font-size: 1.4rem; text-transform: uppercase; font-weight: 700; letter-spacing: 2px; cursor: pointer; transition: 0.3s; margin-top: 10px; border-radius: 4px;}
    .btn-submit:hover { background: #333; }
    .btn-danger-custom { background: #d90429; }
    .btn-danger-custom:hover { background: #b00322; }

    body.dark-mode { background: #0a0a0a !important; }
    body.dark-mode .support-card { background: #111; border-color: #222; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    body.dark-mode .support-card.bg-light-custom { background: #151515 !important; border-color: #222; }
    body.dark-mode .section-title, body.dark-mode .support-info-title { border-color: #333; color: #fff; }
    body.dark-mode .form-label, body.dark-mode .support-info-text { color: #aaa; }
    body.dark-mode .form-control-sharp { background: #1a1a1a; border-color: #333; color: #fff; }
    body.dark-mode .form-control-sharp:focus { border-color: #88c8bc; }
    body.dark-mode input[type=file]::file-selector-button { background: #fff; color: #000; }
    body.dark-mode input[type=file]::file-selector-button:hover { background: #ccc; }
    body.dark-mode .btn-submit { background: #fff; color: #000; }
    body.dark-mode .btn-submit:hover { background: #ccc; }
    body.dark-mode .btn-danger-custom { background: #d90429; color: #fff; }
    body.dark-mode .btn-danger-custom:hover { background: #b00322; color: #fff; }
</style>

<div class="container support-wrapper">
    
    <div class="row g-4 mb-5">
        <div class="col-lg-5">
            <div class="support-card">
                <h2 class="section-title">Get In Touch</h2>
                <p class="support-info-text">Have a question about a specific shoe, sizing, or general inquiry? Our team is here to help you step forward.</p>
                <h3 class="support-info-title">Headquarters</h3>
                <p class="support-info-text">A4S Footwear System<br>UiTM Cawangan Johor, Kampus Segamat<br>85000 Segamat, Johor, Malaysia</p>
                <h3 class="support-info-title">Direct Contact</h3>
                <p class="support-info-text"><strong>Email:</strong> support@a4sfootwear.com<br><strong>Phone:</strong> +60 10-597 7407</p>
                <h3 class="support-info-title">Operating Hours</h3>
                <p class="support-info-text">Monday - Friday: 9:00 AM - 6:00 PM<br>Weekend: Closed</p>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="support-card">
                <h2 class="section-title">Send a Message</h2>
                <form action="contact.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control-sharp" value="<?php echo htmlspecialchars($cust_name); ?>" placeholder="Your name" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control-sharp" value="<?php echo htmlspecialchars($cust_email); ?>" placeholder="your@email.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control-sharp" placeholder="e.g. Question about sizing" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">How can we help?</label>
                        <textarea name="message" class="form-control-sharp" rows="6" placeholder="Describe your inquiry in detail..." required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn-submit">Submit Inquiry</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-lg-5">
            <div class="support-card bg-light-custom">
                <h2 class="section-title">Return Policy</h2>
                <p class="support-info-text">We accept returns within <strong>14 days of delivery</strong> for unworn, unwashed gear in its original packaging.</p>
                <h3 class="support-info-title">Exceptions</h3>
                <p class="support-info-text">Limited edition drops, clearance items, and visibly worn gear are non-refundable unless verified defective upon arrival.</p>
                <h3 class="support-info-title">The Process</h3>
                <p class="support-info-text">Once your request is submitted with required photographic evidence, our team will review it. If approved, we will email you a printable return shipping label.</p>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="support-card border-top border-5" style="border-top-color: #d90429 !important;">
                <h2 class="section-title">Request a Refund</h2>
                <form action="contact.php" method="POST" enctype="multipart/form-data" id="refundForm">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label text-danger">Eligible Orders</label>
                            <?php if ($custID > 0): ?>
                                <select name="order_number" class="form-control-sharp" style="cursor:pointer;" required>
                                    <option value="" disabled selected>Select an order...</option>
                                    <?php if (!empty($customer_orders)): ?>
                                        <?php foreach($customer_orders as $ord): ?>
                                            <?php $display_id = str_pad($ord['orderID'], 5, '0', STR_PAD_LEFT); ?>
                                            <option value="<?php echo $ord['orderID']; ?>">
                                                #ORD-<?php echo $display_id; ?> (RM <?php echo number_format($ord['total_amount'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No eligible orders found.</option>
                                    <?php endif; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="order_number" class="form-control-sharp" placeholder="Please login first" disabled required>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="form-label text-danger">Reason for Return</label>
                            <select name="reason" class="form-control-sharp" style="cursor:pointer;" required>
                                <option value="" disabled selected>Select a reason...</option>
                                <option value="Wrong Size">Wrong Size</option>
                                <option value="Damaged / Defective">Damaged / Defective</option>
                                <option value="Wrong Item Received">Wrong Item Received</option>
                                <option value="Item Not As Described">Item Not As Described</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description of Issue</label>
                        <textarea name="details" class="form-control-sharp" rows="4" placeholder="Please explain the issue. If the item is defective, describe the damage clearly..." required></textarea>
                    </div>
                    <div class="form-group p-3 border rounded" style="background: rgba(217, 4, 41, 0.05); border-color: rgba(217, 4, 41, 0.2) !important;">
                        <label class="form-label text-danger">Upload Evidence (1 to 5 Photos) *</label>
                        <input type="file" name="refund_images[]" id="refundImages" class="form-control-sharp p-2" accept=".jpg, .jpeg, .png, .webp" multiple required <?php echo ($custID > 0) ? '' : 'disabled'; ?>>
                        <small class="text-muted mt-2 d-block" style="font-family:'Helvetica Neue', sans-serif;">Hold down CTRL (or CMD) to select multiple images. Maximum 5 allowed.</small>
                    </div>
                    <button type="submit" name="submit_refund" class="btn-submit btn-danger-custom" <?php echo ($custID > 0 && !empty($customer_orders)) ? '' : 'disabled'; ?>>
                        <?php echo ($custID > 0 && !empty($customer_orders)) ? 'Submit Return Request' : 'No Eligible Orders'; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('refundForm').addEventListener('submit', function(e) {
        const fileInput = document.getElementById('refundImages');
        const numFiles = fileInput.files.length;
        
        if (numFiles < 1 || numFiles > 5) {
            e.preventDefault(); 
            Swal.fire({
                title: 'Image Limit Exceeded',
                text: 'Please select between 1 and 5 photos as evidence for your return request.',
                icon: 'warning',
                confirmButtonColor: '#d90429',
                background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
                color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
            });
        }
    });
</script>

<?php if($message_sent): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Message Sent!',
                text: 'Our support team has received your general inquiry and will respond within 24 hours.',
                icon: 'success',
                confirmButtonColor: '#000',
                background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
                color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
            });
        });
    </script>
<?php endif; ?>

<?php if($refund_sent): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Refund Request Submitted',
                text: 'Your return request and images have been securely uploaded. Please check your email for the next steps.',
                icon: 'info',
                confirmButtonColor: '#d90429',
                background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
                color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
            });
        });
    </script>
<?php endif; ?>

<?php if(!empty($db_error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Submission Failed',
                text: 'Database Error: <?php echo addslashes($db_error_message); ?>',
                icon: 'error',
                confirmButtonColor: '#d90429',
                background: document.body.classList.contains('dark-mode') ? '#1e212b' : '#fff',
                color: document.body.classList.contains('dark-mode') ? '#f8fafc' : '#111'
            });
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>