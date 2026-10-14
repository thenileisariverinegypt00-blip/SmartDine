<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header('Location: login.php');
    exit();
}

// Get statistics (limited for users)
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")->fetch()['count'];
$ready_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'ready'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Smart Dine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="user_dashboard.php" style="cursor: pointer;">🍽️ Smart Dine - Staff</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="user_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_orders.php">
                            <i class="bi bi-receipt"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_tables.php">
                            <i class="bi bi-chair"></i> Tables
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
                <p class="text-muted">Staff Member Dashboard - Smart Dine</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Orders</h6>
                            <p class="stat-number"><?php echo $total_orders; ?></p>
                        </div>
                        <i class="bi bi-receipt" style="font-size: 2.5rem; color: #d4a574; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pending Orders</h6>
                            <p class="stat-number warning"><?php echo $pending_orders; ?></p>
                        </div>
                        <i class="bi bi-hourglass-split" style="font-size: 2.5rem; color: #e74c3c; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Ready Orders</h6>
                            <p class="stat-number"><?php echo $ready_orders; ?></p>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #27ae60; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Your Role</h6>
                            <p class="stat-number" style="font-size: 1.5rem;">Staff</p>
                        </div>
                        <i class="bi bi-person-badge" style="font-size: 2.5rem; color: #d4a574; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="user_orders.php" class="btn btn-primary w-100">
                                    <i class="bi bi-receipt"></i> Create New Order
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="user_tables.php" class="btn btn-info w-100 text-white">
                                    <i class="bi bi-chair"></i> View Tables
                                </a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="logout.php" class="btn btn-danger w-100">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Table</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $orders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");
                                    if ($orders->rowCount() > 0) {
                                        foreach ($orders as $order):
                                        ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo $order['table_number']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <a href="user_order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary btn-small">View</a>
                                            </td>
                                        </tr>
                                        <?php
                                        endforeach;
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No orders yet</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
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