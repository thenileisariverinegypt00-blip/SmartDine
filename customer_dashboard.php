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

// Get customer statistics
$user_id = $_SESSION['user_id'];

// Total orders
$total_orders = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$total_orders->execute([$user_id]);
$total_orders_count = $total_orders->fetch()['count'];

// Pending orders
$pending_orders = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status != 'completed'");
$pending_orders->execute([$user_id]);
$pending_orders_count = $pending_orders->fetch()['count'];

// Completed orders
$completed_orders = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
$completed_orders->execute([$user_id]);
$completed_orders_count = $completed_orders->fetch()['count'];

// Total spent
$total_spent = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND status = 'completed'");
$total_spent->execute([$user_id]);
$total_spent_amount = $total_spent->fetch()['total'] ?? 0;

// Recent orders
$recent_orders = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent_orders->execute([$user_id]);
$recent_orders_data = $recent_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Dine Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-section {
            background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            padding: 50px 40px;
            border-radius: 15px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 2;
        }

        .welcome-greeting {
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: slideInLeft 0.8s ease-out;
        }

        .welcome-emoji {
            font-size: 4rem;
            display: inline-block;
            margin-right: 15px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .welcome-description {
            font-size: 1.3rem;
            margin-top: 20px;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 0.5px;
            animation: fadeIn 1s ease-out 0.3s backwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .description-accent {
            color: #fff8dc;
            font-weight: bold;
        }

        .welcome-subtext {
            margin-top: 20px;
            font-size: 1.1rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInUp 0.8s ease-out 0.5s backwards;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-emoji {
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="customer_dashboard.php" style="cursor: pointer;">🍽️ Smart Dine - Customer</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="customer_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_menu.php">
                            <i class="bi bi-list"></i> Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_orders.php">
                            <i class="bi bi-receipt"></i> My Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-primary position-relative" onclick="toggleCart()" style="margin-left: 10px;">
                            <i class="bi bi-cart"></i> Cart
                            <?php if (count($_SESSION['cart']) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo count($_SESSION['cart']); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid p-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <div class="welcome-greeting">
                    <span class="welcome-emoji">👋</span>Welcome, <span class="description-accent"><?php echo htmlspecialchars($_SESSION['username']); ?></span>!
                </div>
                <div class="welcome-description">
                    Welcome to <span class="description-accent">Smart Dine</span>! Browse our delicious menu, place orders, and enjoy your favorite meals. 🍽️
                </div>
                <div class="welcome-subtext">
                    <span class="stat-emoji">✨</span>
                    <span>Explore our menu and place your order anytime</span>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">📋 Total Orders</h6>
                            <p class="stat-number"><?php echo $total_orders_count; ?></p>
                        </div>
                        <i class="bi bi-receipt" style="font-size: 2.5rem; color: #d4a574; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">⏳ Active Orders</h6>
                            <p class="stat-number warning"><?php echo $pending_orders_count; ?></p>
                        </div>
                        <i class="bi bi-hourglass-split" style="font-size: 2.5rem; color: #e74c3c; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">✅ Completed Orders</h6>
                            <p class="stat-number"><?php echo $completed_orders_count; ?></p>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #27ae60; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">💰 Total Spent</h6>
                            <p class="stat-number">₱<?php echo number_format($total_spent_amount, 0); ?></p>
                        </div>
                        <i class="bi bi-cash-coin" style="font-size: 2.5rem; color: #27ae60; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">⚡ Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <a href="customer_menu.php" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle"></i> Browse Menu
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="customer_orders.php" class="btn btn-info w-100 text-white">
                                    <i class="bi bi-receipt"></i> My Orders
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="customer_checkout.php" class="btn btn-success w-100" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                    <i class="bi bi-credit-card"></i> Checkout
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="logout.php" class="btn btn-danger w-100">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">📍 Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_orders_data) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders_data as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="badge status-<?php echo $order['status']; ?>">
                                                    <?php 
                                                        $status_icons = [
                                                            'pending' => '⏳',
                                                            'preparing' => '👨‍🍳',
                                                            'ready' => '✅',
                                                            'completed' => '🎉'
                                                        ];
                                                        echo ($status_icons[$order['status']] ?? '') . ' ' . ucfirst($order['status']);
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="customer_order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <i class="bi bi-info-circle"></i> <strong>No orders yet!</strong> 
                                <a href="customer_menu.php">Start ordering now</a> and your orders will appear here.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Smart Dine - Restaurant Management System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleCart() {
            window.location.href = 'customer_menu.php';
        }
    </script>
</body>
</html>