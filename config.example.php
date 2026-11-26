<?php
// Copy this file to config.php and fill in your own database details.
// config.php is listed in .gitignore, so your real password never goes to GitHub.
//
// On XAMPP (your own computer) you don't need config.php at all:
// db.php falls back to localhost / root / no password / dbshoes.
//
// On InfinityFree, find these under Client Area > your account > MySQL Databases.

return [
    'db_host' => 'localhost',      // InfinityFree: something like sqlXXX.infinityfree.com
    'db_user' => 'root',           // InfinityFree: your MySQL username, like if0_12345678
    'db_pass' => '',               // InfinityFree: your MySQL password
    'db_name' => 'dbshoes',        // InfinityFree: your database name, like if0_12345678_dbshoes
];
