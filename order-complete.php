<?php
session_start();
include 'includes/header.php';
?>

<div class="breadcrumbs">
    <div class="container">
        <div class="row">
            <div class="col">
                <p class="bread"><span><a href="store.php">Home</a></span> / <span>Purchase Complete</span></p>
            </div>
        </div>
    </div>
</div>

<div class="colorlib-product">
    <div class="container">
        <div class="row row-pb-lg">
            <div class="col-sm-10 offset-md-1">
                <div class="process-wrap">
                    <div class="process text-center active">
                        <p><span>01</span></p>
                        <h3>Shopping Cart</h3>
                    </div>
                    <div class="process text-center active">
                        <p><span>02</span></p>
                        <h3>Checkout</h3>
                    </div>
                    <div class="process text-center active">
                        <p><span>03</span></p>
                        <h3>Order Complete</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-10 offset-sm-1 text-center">
                <p class="icon-addcart"><i class="icon-check" style="font-size: 80px; color: #88c8bc;"></i></p>
                <h2 class="mb-4" style="font-weight: 700;">Thank you for purchasing!</h2>
                <p class="text-muted mb-5">Your order has been placed successfully and is now processing. We will send you an update shortly.</p>
                <p>
                    <a href="store.php" class="btn btn-primary btn-outline-primary btn-lg" style="border-radius: 50px; margin-right: 15px;"><i class="icon-shopping-cart"></i> Continue Shopping</a>
                    <a href="my_orders.php" class="btn btn-primary btn-lg" style="border-radius: 50px;"><i class="icon-truck"></i> View My Orders</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>