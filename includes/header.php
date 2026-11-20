<!DOCTYPE HTML>
<html>
<head>
<title>A4S Footwear - Premium</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/animate.css">
<link rel="stylesheet" href="css/icomoon.css">
<link rel="stylesheet" href="css/ionicons.min.css">
<link rel="stylesheet" href="css/bootstrap.min.css">
<link rel="stylesheet" href="css/magnific-popup.css">
<link rel="stylesheet" href="css/flexslider.css">
<link rel="stylesheet" href="css/owl.carousel.min.css">
<link rel="stylesheet" href="css/owl.theme.default.min.css">
<link rel="stylesheet" href="css/bootstrap-datepicker.css">
<link rel="stylesheet" href="fonts/flaticon/font/flaticon.css">
<link rel="stylesheet" href="css/style.css">

<script src="js/jquery.min.js"></script>

<?php
if (!isset($conn)) { require 'db.php'; }

// DYNAMIC THEME DETECTOR
$active_theme = 'none';
$theme_class = 'theme-standard'; 

$theme_sql = "SELECT setting_value FROM store_settings WHERE setting_key = 'active_theme'";
$theme_res = $conn->query($theme_sql);
if ($theme_res && $theme_res->num_rows > 0) {
    $active_theme = strtolower($theme_res->fetch_assoc()['setting_value']);
    
    if (strpos($active_theme, 'winter') !== false || strpos($active_theme, 'snow') !== false) {
        $theme_class = 'theme-winter';
    } elseif (strpos($active_theme, 'summer') !== false || strpos($active_theme, 'warm') !== false) {
        $theme_class = 'theme-summer';
    } elseif (strpos($active_theme, 'autumn') !== false || strpos($active_theme, 'fall') !== false) {
        $theme_class = 'theme-autumn';
    }
}

// SMART CART COUNTER
$cart_count = 0;
if(isset($_SESSION['custID']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    $stmt_cart = $conn->prepare("SELECT SUM(quantity) as total FROM shopping_cart WHERE custID = ?");
    if ($stmt_cart) {
        $stmt_cart->bind_param("i", $_SESSION['custID']);
        $stmt_cart->execute();
        $cart_res = $stmt_cart->get_result()->fetch_assoc();
        $cart_count = $cart_res['total'] ?? 0;
    }
}
?>

<style>
    body, #page { font-family: 'Helvetica Neue', Helvetica, 'Inter', Arial, sans-serif; background-color: #ffffff; color: #111; }
    h1, h2, h3, h4, h5, h6 { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: -0.5px; color: #000; }
    .price, .price span, .product-entry .desc .price, .cart-detail ul li span { font-family: 'Helvetica Neue', Helvetica, 'Inter', Arial, sans-serif !important; font-weight: 500 !important; letter-spacing: 0px !important; }
    
    .colorlib-nav { background: #ffffff !important; padding: 15px 0; border-bottom: 1px solid #ebebeb; position: sticky; top: 0; z-index: 1000; }
    #colorlib-logo a { font-family: 'Futura Condensed', 'Futura', 'Oswald', sans-serif; font-weight: 700; font-size: 34px; letter-spacing: -1px; color: #000 !important; text-transform: uppercase; }
    .colorlib-nav ul li a { font-family: 'Helvetica Neue', Helvetica, 'Inter', sans-serif; font-weight: 500; font-size: 16px; color: #000 !important; transition: 0.2s ease; }
    .colorlib-nav ul li a:hover { color: #666 !important; text-decoration: none; }
    
    .nav-logout-link { font-family: 'Helvetica Neue', Helvetica, sans-serif !important; font-weight: 600 !important; font-size: 12px !important; color: #888 !important; text-transform: uppercase; letter-spacing: 1px; transition: color 0.3s ease !important; display: inline-flex; align-items: center; }
    .nav-logout-link:hover { color: #d90429 !important; text-decoration: none; }
    
    /* STANDARD CLEAN LOGIN BUTTON - LIGHT GREEN */
    .btn-login-modern {
        background: #28a745;
        color: #fff !important;
        padding: 8px 18px !important;
        border-radius: 30px;
        font-family: 'Helvetica Neue', Helvetica, sans-serif;
        font-weight: 600;
        font-size: 12px !important;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: opacity 0.2s ease;
        border: none;
        text-decoration: none !important;
    }
    .btn-login-modern:hover {
        opacity: 0.85;
        color: #fff !important;
    }

    .btn-primary { background: #000 !important; border: 1px solid #000 !important; color: #fff !important; border-radius: 0 !important; font-weight: 600; letter-spacing: 0.5px; }
    .btn-primary:hover { background: #333 !important; border-color: #333 !important; }
    .form-control { border-radius: 0 !important; border: 1px solid #ddd; background: #fafafa; font-family: 'Helvetica Neue', Helvetica, 'Inter', sans-serif; font-weight: 400; }
    .form-control:focus { border-color: #000 !important; box-shadow: none !important; }
    .cart span.badge { font-family: 'Helvetica Neue', Helvetica, sans-serif; background: #000 !important; color: #fff !important; font-weight: 700; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; }

    #search-results { background: #fff; border: 1px solid #eee; border-radius: 0 0 4px 4px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .search-dropdown-item { display: flex; align-items: center; padding: 12px 15px; border-bottom: 1px solid #eee; color: #111; text-decoration: none; background-color: #fff; transition: background-color 0.2s; }
    .search-dropdown-item:hover { background-color: #f8f9fa; color: #111; text-decoration: none; }
    .search-dropdown-img { width: 50px; height: 50px; object-fit: cover; margin-right: 15px; border-radius: 4px; border: 1px solid #ddd; }
    .search-dropdown-text { font-size: 14px; font-family: 'Futura Condensed', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #111; }
    .search-dropdown-price { font-family: 'Helvetica Neue', sans-serif; color: #888; font-weight: 500; font-size: 13px; }
    .search-dropdown-empty { padding: 15px; font-family: 'Helvetica Neue', sans-serif; color: #888; text-align: center; background-color: #fff; font-size: 14px; }
    .search-dropdown-viewall { display: flex; align-items: center; justify-content: space-between; padding: 15px 20px; background: #f8f9fa; color: #000; text-decoration: none; font-family: 'Helvetica Neue', sans-serif; text-transform: uppercase; font-weight: 600; font-size: 12px; letter-spacing: 1px; transition: all 0.3s ease; border-top: 1px solid #eee; }
    .search-dropdown-viewall:hover { background-color: #fff; color: #d90429; text-decoration: none; }

    body.dark-mode, body.dark-mode #page { background-color: #0a0a0a !important; color: #e5e5e5 !important; }
    body.dark-mode .colorlib-nav { background-color: #000000 !important; border-bottom: 1px solid #222 !important; }
    body.dark-mode #colorlib-logo a, body.dark-mode .colorlib-nav ul li a, body.dark-mode h1, body.dark-mode h2, body.dark-mode h3 { color: #ffffff !important; }
    body.dark-mode .colorlib-nav ul li a:hover { color: #88c8bc !important; }
    body.dark-mode .btn-primary { background: #ffffff !important; color: #000000 !important; border-color: #ffffff !important; }
    body.dark-mode .btn-primary:hover { background: #cccccc !important; border-color: #cccccc !important; }
    body.dark-mode .bg-white, body.dark-mode .card, body.dark-mode .contact-wrap, body.dark-mode .colorlib-form { background-color: #111 !important; border-color: #222 !important; }
    body.dark-mode .form-control { background-color: #111 !important; border: 1px solid #333 !important; color: #fff !important; }
    body.dark-mode .form-control:focus { border-color: #fff !important; }
    body.dark-mode .cart span.badge { background: #fff !important; color: #000 !important; }
    body.dark-mode .table td, body.dark-mode .table th { border-color: #222 !important; }
    
    /* Dark Mode Login Button */
    body.dark-mode .btn-login-modern { background: #28a745; color: #fff !important; }
    body.dark-mode .btn-login-modern:hover { opacity: 0.85; color: #fff !important; }

    body.dark-mode #search-results { background: #1a1a1a; border-color: #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    body.dark-mode .search-dropdown-item { background-color: #1a1a1a; border-bottom-color: #333; color: #fff; }
    body.dark-mode .search-dropdown-item:hover { background-color: #252525; color: #fff; }
    body.dark-mode .search-dropdown-img { border-color: #444; }
    body.dark-mode .search-dropdown-text { color: #fff; }
    body.dark-mode .search-dropdown-price { color: #aaa; }
    body.dark-mode .search-dropdown-empty { background-color: #1a1a1a; color: #aaa; }
    body.dark-mode .search-dropdown-viewall { background: #111; color: #fff; border-top-color: #333; }
    body.dark-mode .search-dropdown-viewall:hover { background-color: #000; color: #d90429; }

    @keyframes seasonalFall {
        0% { transform: translateY(-5vh) translateX(0) rotate(0deg); opacity: 1; }
        100% { transform: translateY(105vh) translateX(40px) rotate(360deg); opacity: 0; }
    }
    
    body.theme-summer::before {
        content: ''; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; 
        background: radial-gradient(circle at 50% 0%, rgba(255, 183, 3, 0.08) 0%, rgba(0,0,0,0) 60%); 
        pointer-events: none; z-index: 9998;
    }
    body.theme-summer.dark-mode::before {
        background: radial-gradient(circle at 50% 0%, rgba(255, 183, 3, 0.04) 0%, rgba(0,0,0,0) 60%); 
    }
</style>
</head>

<body class="<?php echo htmlspecialchars($theme_class); ?>">
    
<div class="colorlib-loader"></div>

<div id="page">
    <nav class="colorlib-nav" role="navigation">
        <div class="top-menu">
            <div class="container-fluid px-5">
                <div class="row align-items-center">
                    
                    <div class="col-sm-4 col-md-3">
                        <div id="colorlib-logo"><a href="index.php">A4S</a></div>
                    </div>
                    
                    <div class="col-sm-8 col-md-6 text-center menu-1">
                        <ul style="margin: 0; padding: 0;">
                            <li style="display: inline-block; margin: 0 15px;"><a href="index.php">Home</a></li>
                            <li style="display: inline-block; margin: 0 15px;"><a href="men.php">Men</a></li>
                            <li style="display: inline-block; margin: 0 15px;"><a href="women.php">Women</a></li>
                            <li style="display: inline-block; margin: 0 15px;"><a href="contact.php">Support</a></li>
                        </ul>
                    </div>

                    <div class="col-sm-12 col-md-3 text-right menu-1 d-flex justify-content-end align-items-center">
                        <ul style="margin: 0; padding: 0; display: flex; align-items: center;">
                            
                            <li style="margin-left: 15px; position: relative;" class="search-wrap">
                                <form action="advanced_search.php" method="GET" style="margin: 0;">
                                    <div class="input-group" style="width: 200px;">
                                        <input type="search" name="q" class="form-control" id="search-input" autocomplete="off" placeholder="Search..." style="font-size: 13px; height: 35px; background: transparent;">
                                        <div id="search-results" class="position-absolute w-100 shadow-sm" style="top: 100%; left: 0; z-index: 1000; display: none; background: #fff; text-align: left; border: 1px solid #eee;"></div>
                                    </div>
                                </form>
                            </li>

                            <li style="margin-left: 15px;">
                                <a href="#" id="customerDarkModeToggle" style="cursor: pointer; font-size: 18px;" title="Toggle Theme">
                                    <i class="icon-moon"></i>
                                </a>
                            </li>

                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                                <li style="margin-left: 15px;" title="Profile">
                                    <a href="my_profile.php" style="font-size: 18px;"><i class="icon-user"></i></a>
                                </li>
                                <li style="margin-left: 15px;" title="Orders">
                                    <a href="my_orders.php" style="font-size: 18px;"><i class="icon-clipboard"></i></a>
                                </li>
                                <li class="cart" style="margin-left: 15px;" title="Cart">
                                    <a href="cart.php" style="font-size: 18px; position: relative;">
                                        <i class="icon-shopping-cart"></i>
                                        <?php if($cart_count > 0): ?>
                                            <span class='badge' style='position: absolute; top: -10px; right: -15px; font-size: 11px; padding: 0;'><?php echo $cart_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li style="margin-left: 20px;">
                                    <a href="login.php?logout=1" class="nav-logout-link"><i class="icon-sign-out me-1" style="font-size: 14px;"></i> Logout</a>
                                </li>
                            <?php else: ?>
                                <!-- CLEAN LOGIN PILL BUTTON -->
                                <li style="margin-left: 20px; display: flex; align-items: center;">
                                    <a href="login.php" class="btn-login-modern">
                                        <i class="icon-user" style="font-size: 14px;"></i> Sign In
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        if(localStorage.getItem('a4s_customer_dark_mode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
        
        const darkModeToggle = document.getElementById('customerDarkModeToggle');
        if(darkModeToggle) {
            darkModeToggle.addEventListener('click', function(e) {
                e.preventDefault();
                document.body.classList.toggle('dark-mode');
                if(document.body.classList.contains('dark-mode')) {
                    localStorage.setItem('a4s_customer_dark_mode', 'enabled');
                } else {
                    localStorage.setItem('a4s_customer_dark_mode', 'disabled');
                }
            });
        }

        const activeTheme = "<?php echo $theme_class; ?>";
        if (activeTheme === 'theme-winter') { createParticles('❄', 35, '#a0d2eb'); } 
        else if (activeTheme === 'theme-autumn') { createParticles('🍁', 20, '#d97a04'); }

        function createParticles(char, count, color) {
            const container = document.createElement('div');
            container.style.cssText = 'position:fixed; top:0; left:0; width:100vw; height:100vh; pointer-events:none; z-index:9999; overflow:hidden;';
            document.body.appendChild(container);
            for(let i=0; i<count; i++) {
                let p = document.createElement('div');
                p.innerText = char;
                let startLeft = Math.random() * 100;
                let animDuration = Math.random() * 8 + 6; 
                let animDelay = Math.random() * 5;
                p.style.cssText = `position: absolute; color: ${color}; font-size: ${Math.random() * 15 + 10}px; top: -50px; left: ${startLeft}vw; opacity: ${Math.random() * 0.7 + 0.3}; animation: seasonalFall ${animDuration}s linear ${animDelay}s infinite; text-shadow: 0 2px 4px rgba(0,0,0,0.1);`;
                container.appendChild(p);
            }
        }

        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');

        if(searchInput && searchResults) {
            searchInput.addEventListener('keyup', function() {
                const query = this.value.trim();
                if(query.length > 0) {
                    fetch('ajax_search.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'query=' + encodeURIComponent(query) })
                    .then(response => response.text())
                    .then(data => { searchResults.innerHTML = data; searchResults.style.display = 'block'; })
                    .catch(error => console.error('Search failed:', error));
                } else {
                    searchResults.style.display = 'none'; searchResults.innerHTML = '';
                }
            });
        }
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-wrap')) {
                if(searchResults) searchResults.style.display = 'none';
            }
        });
    });
    </script>