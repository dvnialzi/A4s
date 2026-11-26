<?php
session_start();
require 'db.php'; 

// SMART ROUTING: Handle Logout Request
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}

// SMART ROUTING: Prevent logged-in users from seeing the login screen
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin') {
        header("Location: admin/index.php"); 
        exit();
    } elseif ($_SESSION['role'] === 'customer') {
        header("Location: index.php"); 
        exit();
    }
}

$swal_msg = "";
$swal_icon = "";
$active_tab = "login"; 

// PROCESS SIGNUP
if (isset($_POST['signup'])) {
    $active_tab = "signup"; 
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $address = ""; 

    $stmtC = $conn->prepare("SELECT email FROM customer WHERE email = ?");
    $stmtC->bind_param("s", $email);
    $stmtC->execute();
    $resC = $stmtC->get_result();

    $stmtA = $conn->prepare("SELECT email FROM admin WHERE email = ?");
    $stmtA->bind_param("s", $email);
    $stmtA->execute();
    $resA = $stmtA->get_result();

    if ($resC->num_rows > 0 || $resA->num_rows > 0) {
        $swal_msg = "This email is already registered! Please log in instead.";
        $swal_icon = "error";
    } else {
        $sql = "INSERT INTO customer (username, password, email, addresscust) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $user, $pass, $email, $address);
        
        if ($stmt->execute()) {
            $swal_msg = "Account created successfully! You may now log in.";
            $swal_icon = "success";
            $active_tab = "login"; 
        } else {
            $swal_msg = "Database Error: Could not create account.";
            $swal_icon = "error";
        }
    }
}

// PROCESS LOGIN
if (isset($_POST['login'])) {
    $active_tab = "login";
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    // Check Admin First
    $sqlAdmin = "SELECT * FROM admin WHERE email = ?";
    $stmtAdmin = $conn->prepare($sqlAdmin);
    $stmtAdmin->bind_param("s", $email);
    $stmtAdmin->execute();
    $resultAdmin = $stmtAdmin->get_result();

    if ($rowAdmin = $resultAdmin->fetch_assoc()) {
        $stored = $rowAdmin['password'];
        $isHashed = password_get_info($stored)['algoName'] !== 'unknown';

        if ($isHashed) {
            $valid = password_verify($pass, $stored);
        } else {
            // Older accounts saved their password as plain text. Accept it this one time,
            // then replace it with a hash below so it is never stored as plain text again.
            $valid = ($stored !== '' && hash_equals($stored, $pass));
        }

        if ($valid && (!$isHashed || password_needs_rehash($stored, PASSWORD_DEFAULT))) {
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $upgrade = $conn->prepare("UPDATE admin SET password = ? WHERE adminID = ?");
            $upgrade->bind_param("si", $newHash, $rowAdmin['adminID']);
            $upgrade->execute();
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['adminID'] = $rowAdmin['adminID'];
            $_SESSION['username'] = $rowAdmin['username'];
            $_SESSION['role'] = !empty($rowAdmin['role']) ? $rowAdmin['role'] : 'admin'; 
            header("Location: admin/index.php"); 
            exit();
        } else { 
            $swal_msg = "Incorrect Password!";
            $swal_icon = "error";
        }
    } else {
        // Check Customer
        $sqlCust = "SELECT * FROM customer WHERE email = ?";
        $stmtCust = $conn->prepare($sqlCust);
        $stmtCust->bind_param("s", $email);
        $stmtCust->execute();
        $resultCust = $stmtCust->get_result();

        if ($rowCust = $resultCust->fetch_assoc()) {
            if (password_verify($pass, $rowCust['password'])) {
                session_regenerate_id(true);
                $_SESSION['custID'] = $rowCust['custID'];
                $_SESSION['username'] = $rowCust['username'];
                $_SESSION['role'] = 'customer'; 
                header("Location: index.php"); 
                exit();
            } else { 
                $swal_msg = "Incorrect Password!";
                $swal_icon = "error";
            }
        } else { 
            $swal_msg = "Account not found! Check your email or create a new account.";
            $swal_icon = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4S Footwear - Gateway</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, 'Inter', sans-serif; background: #fff; display: flex; height: 100vh; overflow: hidden; color: #111; }
        
        .split-left { width: 50%; background: url('images/item-1.jpg') center/cover no-repeat; position: relative; }
        .split-left::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.2); }
        .split-right { width: 50%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px; overflow-y: auto; background: #fff; }
        .auth-container { width: 100%; max-width: 420px; }
        .brand-title { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: 3.5rem; text-transform: uppercase; font-weight: 800; letter-spacing: -1px; margin-bottom: 5px; }
        .brand-subtitle { color: #888; font-size: 1rem; margin-bottom: 40px; letter-spacing: 0.5px; }
        .auth-tabs { display: flex; gap: 30px; margin-bottom: 30px; border-bottom: 2px solid #eee; }
        .auth-tab { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: 1.3rem; text-transform: uppercase; font-weight: 700; cursor: pointer; padding-bottom: 10px; color: #bbb; transition: 0.3s; letter-spacing: 1px; position: relative; }
        .auth-tab.active { color: #000; }
        .auth-tab.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background: #000; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #555; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 14px 15px; border: 1px solid #ccc; background: #fafafa; font-family: 'Helvetica Neue', Helvetica, sans-serif; font-size: 1rem; border-radius: 0; outline: none; transition: all 0.3s; }
        .form-control:focus { border-color: #000; background: #fff; }
        .btn-submit { width: 100%; background: #000; color: #fff; border: none; padding: 18px; font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-size: 1.2rem; text-transform: uppercase; font-weight: 700; letter-spacing: 2px; cursor: pointer; transition: background 0.3s; margin-top: 10px; }
        .btn-submit:hover { background: #d90429; }
        .forgot-link { display: block; text-align: center; margin-top: 20px; color: #888; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; text-decoration: none; transition: 0.2s; }
        .forgot-link:hover { color: #d90429; }
        .form-wrapper { display: none; animation: fadeIn 0.4s ease forwards; }
        .form-wrapper.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .split-left { display: none; } .split-right { width: 100%; padding: 20px; } .brand-title { font-size: 2.8rem; } }
    </style>
</head>
<body>

    <div class="split-left"></div>

    <div class="split-right">
        <div class="auth-container">
            
            <h1 class="brand-title">A4S Footwear</h1>
            <p class="brand-subtitle">Engineered for the elements. Designed for the streets.</p>
            
            <div class="auth-tabs">
                <div class="auth-tab <?php echo $active_tab == 'login' ? 'active' : ''; ?>" onclick="switchTab('login')">Log In</div>
                <div class="auth-tab <?php echo $active_tab == 'signup' ? 'active' : ''; ?>" onclick="switchTab('signup')">Create Account</div>
            </div>

            <!-- LOGIN FORM -->
            <form id="form-login" class="form-wrapper <?php echo $active_tab == 'login' ? 'active' : ''; ?>" method="POST" action="login.php">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
                <a href="index.php" class="forgot-link" style="color: #111; font-weight: bold; border-top: 1px solid #eee; padding-top: 15px; margin-top: 30px;">&larr; Return to Storefront</a>
            </form>

            <!-- SIGNUP FORM -->
            <form id="form-signup" class="form-wrapper <?php echo $active_tab == 'signup' ? 'active' : ''; ?>" method="POST" action="login.php">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Create a secure password" required>
                </div>
                <button type="submit" name="signup" class="btn-submit">Join A4S</button>
                <a href="index.php" class="forgot-link" style="color: #111; font-weight: bold; border-top: 1px solid #eee; padding-top: 15px; margin-top: 30px;">&larr; Return to Storefront</a>
            </form>

        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.getElementById('form-login').classList.remove('active');
            document.getElementById('form-signup').classList.remove('active');
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));

            if(tab === 'login') {
                document.getElementById('form-login').classList.add('active');
                document.querySelectorAll('.auth-tab')[0].classList.add('active');
            } else {
                document.getElementById('form-signup').classList.add('active');
                document.querySelectorAll('.auth-tab')[1].classList.add('active');
            }
        }
    </script>

    <?php if(!empty($swal_msg)): ?>
    <script>
        Swal.fire({
            title: '<?php echo $swal_icon == "error" ? "Action Failed" : "Welcome!"; ?>',
            text: '<?php echo addslashes($swal_msg); ?>',
            icon: '<?php echo $swal_icon; ?>',
            confirmButtonColor: '#000',
            background: '#fff',
            color: '#111'
        });
    </script>
    <?php endif; ?>

</body>
</html>