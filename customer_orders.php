<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

// Get ONLY THIS CUSTOMER'S orders
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Smart Dine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-header {
            background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            padding: 30px 40px;
            border-radius: 15px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .orders-header h1 {
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .orders-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
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
                        <a class="nav-link" href="customer_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_menu.php">
                            <i class="bi bi-list"></i> Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="customer_orders.php">
                            <i class="bi bi-receipt"></i> My Orders
                        </a>
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
        <!-- Orders Header -->
        <div class="orders-header">
            <h1>📋 My Orders</h1>
            <p>View and track all your orders</p>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">All Orders</h5>
            </div>
            <div class="card-body">
                <?php if (count($orders) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Ordered At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
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
                                                echo $status_icons[$order['status']] . ' ' . ucfirst($order['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="customer_order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-eye"></i> View Details
                                        </a>
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

        <!-- Order Statistics -->
        <?php 
            $total_orders = count($orders);
            $completed = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'");
            $completed->execute([$_SESSION['user_id']]);
            $completed_count = $completed->fetch()['count'];
            
            $pending = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status != 'completed'");
            $pending->execute([$_SESSION['user_id']]);
            $pending_count = $pending->fetch()['count'];
        ?>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">📦 Total Orders</h6>
                        <h3 style="color: var(--primary-color); font-weight: bold;"><?php echo $total_orders; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">⏳ Active Orders</h6>
                        <h3 style="color: #f39c12; font-weight: bold;"><?php echo $pending_count; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">✅ Completed Orders</h6>
                        <h3 style="color: #27ae60; font-weight: bold;"><?php echo $completed_count; ?></h3>
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
</body>
</html>