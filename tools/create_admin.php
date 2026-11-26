<?php
// Creates a super admin account with a properly hashed password.
// Run it from the command line, never from a browser:
//
//   php tools/create_admin.php "username" "email@example.com" "a-long-password"
//
// On XAMPP for Windows:
//   C:\xampp\php\php.exe tools\create_admin.php "username" "email@example.com" "a-long-password"

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../db.php';

$username = $argv[1] ?? '';
$email    = $argv[2] ?? '';
$pass     = $argv[3] ?? '';

if ($username === '' || $email === '' || $pass === '') {
    fwrite(STDERR, "Usage: php tools/create_admin.php \"username\" \"email@example.com\" \"a-long-password\"\n");
    exit(1);
}
if (strlen($pass) < 8) {
    fwrite(STDERR, "Use a password of at least 8 characters.\n");
    exit(1);
}

$check = $conn->prepare("SELECT adminID FROM admin WHERE username = ? OR email = ?");
$check->bind_param("ss", $username, $email);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    fwrite(STDERR, "An admin with that username or email already exists.\n");
    exit(1);
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO admin (username, password, email, role) VALUES (?, ?, ?, 'super_admin')");
$stmt->bind_param("sss", $username, $hash, $email);
$stmt->execute();

echo "Created super admin \"$username\" ($email). Log in at login.php with this email and password.\n";
