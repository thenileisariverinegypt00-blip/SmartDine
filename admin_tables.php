<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables Management - Smart Dine Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">🍽️ Smart Dine - Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_menu.php">
                            <i class="bi bi-list"></i> Menu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_orders.php">
                            <i class="bi bi-receipt"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_tables.php">
                            <i class="bi bi-chair"></i> Tables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php">
                            <i class="bi bi-people"></i> Users
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
        <h1 class="page-title">Restaurant Tables Management</h1>

        <div class="tables-grid">
            <?php
            $orders = $conn->query("SELECT * FROM orders WHERE status != 'completed'");
            $occupied_tables = [];
            foreach ($orders as $order) {
                $occupied_tables[$order['table_number']] = $order['id'];
            }

            for ($i = 1; $i <= 12; $i++):
            ?>
                <div class="table-card <?php echo isset($occupied_tables[$i]) ? 'occupied' : 'available'; ?>">
                    <h4><i class="bi bi-chair"></i> Table <?php echo $i; ?></h4>
                    <?php if (isset($occupied_tables[$i])): ?>
                        <p class="status"><i class="bi bi-check-circle-fill"></i> Occupied</p>
                        <a href="admin_order_detail.php?id=<?php echo $occupied_tables[$i]; ?>" class="btn btn-sm btn-primary">View Order</a>
                    <?php else: ?>
                        <p class="status"><i class="bi bi-circle"></i> Available</p>
                        <form method="POST" action="admin_orders.php?action=create" style="display:inline;">
                            <input type="hidden" name="table_number" value="<?php echo $i; ?>">
                            <button type="submit" class="btn btn-sm btn-success">New Order</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Smart Dine - Restaurant Management System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>