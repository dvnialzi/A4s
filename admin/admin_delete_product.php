<?php
session_start();
require '../db.php'; 

// Upgraded Security: Kick out anyone who isn't an ADMIN or SUPER_ADMIN
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $prodID = $_GET['id'];
    
    // SOFT DELETE: Flip the switch to 0 instead of destroying the data
    $sql = "UPDATE product SET is_active = 0 WHERE prodID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $prodID);
    
    if ($stmt->execute()) {
        header("Location: admin_products.php");
        exit();
    } else {
        echo "Error archiving product: " . $conn->error;
    }
} else {
    header("Location: admin_products.php");
    exit();
}
?>