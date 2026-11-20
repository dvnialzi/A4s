<?php
// 1. START SESSION & CONNECT TO DATABASE
session_start();

if (!file_exists('db.php')) {
    die("Database connection file missing.");
}
require 'db.php'; 

$step = 1;
$swal_msg = "";
$swal_icon = "";

// STEP 1: VERIFY EMAIL EXISTS IN CUSTOMER DATABASE
if (isset($_POST['verify_email'])) {
    $email = trim($_POST['email']);
    
    // Check Customer Table ONLY
    $stmtC = $conn->prepare("SELECT email FROM customer WHERE email = ?");
    $stmtC->bind_param("s", $email);
    $stmtC->execute();
    if ($stmtC->get_result()->num_rows > 0) {
        $_SESSION['reset_email'] = $email;
        $step = 2; // Move to Step 2
    } else {
        $swal_msg = "We couldn't find a customer account associated with that email.";
        $swal_icon = "error";
    }
}

// STEP 2: UPDATE THE PASSWORD
if (isset($_POST['update_password'])) {
    if (isset($_SESSION['reset_email'])) {
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];
        
        if ($new_pass === $confirm_pass) {
            // Hash the new password securely
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $email = $_SESSION['reset_email'];
            
            // Update the CUSTOMER database ONLY
            $update = $conn->prepare("UPDATE customer SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed_pass, $email);
            
            if ($update->execute()) {
                // Clear the session memory
                unset($_SESSION['reset_email']);
                
                // Trigger success popup and redirect
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Password Reset!',
                            text: 'Your password has been successfully updated. You can now log in.',
                            icon: 'success',
                            confirmButtonColor: '#000',
                            background: '#fff',
                            color: '#111'
                        }).then(() => {
                            window.location.href = 'index.php';
                        });
                    });
                </script>";
            } else {
                $swal_msg = "Database error. Please try again.";
                $swal_icon = "error";
                $step = 2;
            }
        } else {
            $swal_msg = "Your passwords do not match!";
            $swal_icon = "error";
            $step = 2;
        }
    } else {
        // Security fallback if session expires
        $step = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - A4S Footwear</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Modern Reset */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, 'Inter', sans-serif; background: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; color: #111; }
        
        .reset-container {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }

        .brand-title {
            font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif;
            font-size: 2.5rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 5px;
            text-align: center;
        }
        .brand-subtitle {
            text-align: center;
            color: #888;
            font-size: 0.95rem;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }

        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #555;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #ccc;
            background: #fafafa;
            font-family: 'Helvetica Neue', Helvetica, sans-serif;
            font-size: 1rem;
            border-radius: 0;
            outline: none;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #000;
            background: #fff;
        }

        .btn-submit {
            width: 100%;
            background: #000;
            color: #fff;
            border: none;
            padding: 18px;
            font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif;
            font-size: 1.2rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 2px;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #d90429;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            transition: 0.2s;
        }
        .back-link:hover { color: #000; }
    </style>
</head>
<body>

    <div class="reset-container">
        <h1 class="brand-title">A4S Recovery</h1>
        
        <?php if ($step == 1): ?>
            <!-- STEP 1: ENTER EMAIL -->
            <p class="brand-subtitle">Enter your customer email address to reset your password.</p>
            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="account@example.com" required>
                </div>
                <button type="submit" name="verify_email" class="btn-submit">Verify Account</button>
            </form>
            
        <?php else: ?>
            <!-- STEP 2: ENTER NEW PASSWORD -->
            <p class="brand-subtitle">Account verified (<?php echo htmlspecialchars($_SESSION['reset_email']); ?>). Create your new password below.</p>
            <form method="POST" action="forgot_password.php">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                </div>
                <button type="submit" name="update_password" class="btn-submit">Save Password</button>
            </form>
        <?php endif; ?>

        <a href="index.php" class="back-link">&larr; Back to Login</a>
    </div>

    <!-- SweetAlert2 Error Handling -->
    <?php if(!empty($swal_msg)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Error',
                text: '<?php echo addslashes($swal_msg); ?>',
                icon: '<?php echo $swal_icon; ?>',
                confirmButtonColor: '#000',
                background: '#fff',
                color: '#111'
            });
        });
    </script>
    <?php endif; ?>

</body>
</html>