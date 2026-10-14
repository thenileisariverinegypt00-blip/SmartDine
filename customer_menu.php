<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

// Initialize cart in session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $item_id = $_POST['item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    if ($item_id > 0 && $quantity > 0) {
        // Check if item already in cart
        if (isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id] += $quantity;
        } else {
            $_SESSION['cart'][$item_id] = $quantity;
        }
        $cart_message = 'Item added to cart!';
    }
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {
    $item_id = $_POST['item_id'] ?? 0;
    if (isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
    }
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
}

$items = $conn->query("SELECT * FROM menu_items WHERE available = 1 ORDER BY category, name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Smart Dine Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .cart-badge {
            position: relative;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .menu-card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-top: 3px solid #d4a574;
            transition: all 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .quantity-input {
            width: 60px;
            height: 38px;
        }
        .alert-cart {
            animation: slideDown 0.5s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .sidebar-cart {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -5px 0 20px rgba(0,0,0,0.2);
            transition: right 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
        }
        .sidebar-cart.open {
            right: 0;
        }
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            z-index: 999;
        }
        .cart-overlay.show {
            display: block;
        }
        .cart-header {
            background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-item-info {
            flex: 1;
        }
        .cart-item-name {
            font-weight: bold;
            color: #8b4513;
            margin-bottom: 5px;
        }
        .cart-item-qty {
            font-size: 0.9rem;
            color: #666;
        }
        .cart-item-price {
            font-weight: bold;
            color: #d4a574;
            margin-left: 10px;
        }
        .cart-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .cart-footer {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 2px solid #eee;
            padding: 20px;
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1.2rem;
            font-weight: bold;
            color: #8b4513;
        }
        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(212, 165, 116, 0.3);
        }
        @media (max-width: 576px) {
            .sidebar-cart {
                width: 100%;
                right: -100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="customer_dashboard.php">🍽️ Smart Dine - Customer</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="customer_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="customer_menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-primary position-relative cart-badge" onclick="toggleCart()">
                            <i class="bi bi-cart"></i> Cart
                            <?php if (count($_SESSION['cart']) > 0): ?>
                                <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Cart Overlay -->
    <div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>

    <!-- Shopping Cart Sidebar -->
    <div class="sidebar-cart" id="sidebarCart">
        <div class="cart-header">
            <h4 style="margin: 0;">🛒 Shopping Cart</h4>
            <button class="cart-close" onclick="toggleCart()">&times;</button>
        </div>

        <div id="cartItems" style="min-height: 300px;">
            <?php if (empty($_SESSION['cart'])): ?>
                <div class="cart-empty">
                    <i class="bi bi-cart-x" style="font-size: 3rem; color: #ddd;"></i>
                    <p style="margin-top: 10px;">Your cart is empty</p>
                </div>
            <?php else: ?>
                <?php
                $cart_total = 0;
                foreach ($_SESSION['cart'] as $item_id => $quantity):
                    $item = $conn->query("SELECT * FROM menu_items WHERE id = $item_id")->fetch();
                    $subtotal = $item['price'] * $quantity;
                    $cart_total += $subtotal;
                ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="cart-item-qty">Qty: <?php echo $quantity; ?></div>
                    </div>
                    <div>
                        <div class="cart-item-price">₱<?php echo number_format($subtotal, 2); ?></div>
                        <form method="POST" style="margin-top: 5px;">
                            <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                            <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger" onclick="return confirm('Remove item?')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['cart'])): ?>
            <div class="cart-footer">
                <div class="cart-total">
                    <span>Total:</span>
                    <span>₱<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <button class="checkout-btn" onclick="checkout()">
                    <i class="bi bi-credit-card"></i> Checkout
                </button>
                <form method="POST" style="margin-top: 10px;">
                    <button type="submit" name="clear_cart" class="btn btn-secondary w-100" onclick="return confirm('Clear cart?')">
                        Clear Cart
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <div class="container-fluid p-4">
        <?php if (isset($cart_message)): ?>
            <div class="alert alert-success alert-cart alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $cart_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <h1 class="page-title">Menu - Browse & Order</h1>

        <!-- Group by Category -->
        <?php
        $categories = ['Appetizers', 'Main Course', 'Desserts', 'Beverages'];
        foreach ($categories as $category):
        ?>
        <h2 style="color: var(--dark-color); margin-top: 30px; margin-bottom: 20px;">
            <i class="bi bi-tag"></i> <?php echo $category; ?>
        </h2>

        <div class="row">
            <?php
            $category_items = $conn->query("SELECT * FROM menu_items WHERE category = '$category' AND available = 1 ORDER BY name");
            foreach ($category_items as $item):
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card menu-card h-100">
                    <div class="card-body">
                        <h5 class="card-title" style="color: #8b4513;">
                            <i class="bi bi-cup-hot"></i> <?php echo htmlspecialchars($item['name']); ?>
                        </h5>
                        <p class="card-text text-muted" style="font-size: 0.9rem; height: 60px; overflow: hidden;">
                            <?php echo htmlspecialchars($item['description'] ?? 'No description available'); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-success" style="font-size: 1.1rem;">
                                ₱<?php echo number_format($item['price'], 2); ?>
                            </span>
                        </div>

                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <input type="number" name="quantity" class="form-control quantity-input" min="1" value="1" required>
                            <button type="submit" name="add_to_cart" class="btn btn-primary flex-grow-1">
                                <i class="bi bi-cart-plus"></i> Add
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Smart Dine - Restaurant Management System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCart() {
            const sidebar = document.getElementById('sidebarCart');
            const overlay = document.getElementById('cartOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        }

        function checkout() {
            // Redirect to checkout page
            window.location.href = 'customer_checkout.php';
        }

        // Close cart when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebarCart');
            const cart = document.querySelector('.cart-badge');
            
            if (!sidebar.contains(event.target) && !cart.contains(event.target)) {
                if (sidebar.classList.contains('open')) {
                    toggleCart();
                }
            }
        });
    </script>
</body>
</html>