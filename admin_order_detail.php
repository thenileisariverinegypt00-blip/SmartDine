<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_item'])) {
        $menu_item_id = $_POST['menu_item_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 1;

        if ($menu_item_id > 0) {
            try {
                $item = $conn->prepare("SELECT price FROM menu_items WHERE id = ?");
                $item->execute([$menu_item_id]);
                $menu_item = $item->fetch();
                $price = $menu_item['price'];

                $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $menu_item_id, $quantity, $price]);

                // Update order total
                $total = $conn->query("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = $order_id")->fetch()['total'];
                $update = $conn->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
                $update->execute([$total, $order_id]);

                header('Location: admin_order_detail.php?id=' . $order_id);
                exit();
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }

    if (isset($_POST['remove_item'])) {
        try {
            $stmt = $conn->prepare("DELETE FROM order_items WHERE id = ?");
            $stmt->execute([$_POST['item_id']]);

            // Update order total
            $total = $conn->query("SELECT SUM(quantity * price) as total FROM order_items WHERE order_id = $order_id")->fetch()['total'] ?? 0;
            $update = $conn->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
            $update->execute([$total, $order_id]);

            header('Location: admin_order_detail.php?id=' . $order_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    if (isset($_POST['update_status'])) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$_POST['status'], $order_id]);
            header('Location: admin_order_detail.php?id=' . $order_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

$order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$order_id]);
$order_data = $order->fetch();

$items = $conn->prepare("SELECT oi.*, mi.name FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id WHERE oi.order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll();

$menu_items = $conn->query("SELECT * FROM menu_items WHERE available = 1 ORDER BY category");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Smart Dine Admin</title>
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
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_orders.php">Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid p-4">
        <h1 class="page-title">Order #<?php echo $order_data['id']; ?> - Table <?php echo $order_data['table_number']; ?></h1>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="order-info">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($order_data['customer_name'] ?? 'N/A'); ?></p>
                    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($order_data['created_at'])); ?></p>
                    <p><strong>Updated:</strong> <?php echo date('M d, Y H:i', strtotime($order_data['updated_at'])); ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <form method="POST" class="card p-3">
                    <label class="form-label"><strong>Order Status:</strong></label>
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <option value="pending" <?php echo $order_data['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="preparing" <?php echo $order_data['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                            <option value="ready" <?php echo $order_data['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                            <option value="completed" <?php echo $order_data['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add Items -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title">Add Items to Order</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-5">
                        <select name="menu_item_id" class="form-select" required>
                            <option value="">Select Item</option>
                            <?php foreach ($menu_items as $item): ?>
                                <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?> - ₱<?php echo number_format($item['price'], 2); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="quantity" class="form-control" placeholder="Quantity" min="1" value="1" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="add_item" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Order Items Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-sm btn-danger" onclick="return confirm('Remove item?')">
                                            <i class="bi bi-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <h4>Total: <span class="badge bg-success" style="font-size: 1.2rem;">₱<?php echo number_format($order_data['total_amount'], 2); ?></span></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="admin_orders.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Smart Dine - Restaurant Management System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>