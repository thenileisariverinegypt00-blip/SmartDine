<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: customer_menu.php');
    exit();
}

$error = '';
$success = '';
$order_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['place_order'])) {
        $table_number = $_POST['table_number'] ?? 0;
        $customer_name = $_POST['customer_name'] ?? $_SESSION['username'];

        if (!empty($table_number)) {
            try {
                // Check if table is still available
                $check_table = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE table_number = ? AND status != 'completed'");
                $check_table->execute([$table_number]);
                $table_occupied = $check_table->fetch()['count'];

                if ($table_occupied > 0) {
                    $error = 'Sorry! Table ' . $table_number . ' is now occupied. Please select another table.';
                } else {
                    // Start transaction
                    $conn->beginTransaction();

                    // Create order with user_id
                    $stmt = $conn->prepare("INSERT INTO orders (user_id, table_number, customer_name, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $table_number, $customer_name, 'pending']);
                    $order_id = $conn->lastInsertId();

                    // Add items to order
                    $total_amount = 0;
                    $insert_item = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");

                    foreach ($_SESSION['cart'] as $item_id => $quantity) {
                        $item = $conn->query("SELECT price FROM menu_items WHERE id = $item_id")->fetch();
                        $price = $item['price'];
                        $subtotal = $price * $quantity;
                        $total_amount += $subtotal;

                        $insert_item->execute([$order_id, $item_id, $quantity, $price]);
                    }

                    // Update order total
                    $update_order = $conn->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
                    $update_order->execute([$total_amount, $order_id]);

                    // Commit transaction
                    $conn->commit();

                    // Clear cart
                    $_SESSION['cart'] = [];

                    $success = 'Order placed successfully! Order ID: #' . $order_id;
                }
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Error placing order: ' . $e->getMessage();
            }
        } else {
            $error = 'Please select a table number';
        }
    }
}

// Get occupied tables
$occupied_tables = [];
$occupied_query = $conn->query("SELECT table_number FROM orders WHERE status != 'completed' GROUP BY table_number");
foreach ($occupied_query as $occupied) {
    $occupied_tables[] = $occupied['table_number'];
}

// Calculate cart total
$cart_total = 0;
$cart_items_count = 0;
foreach ($_SESSION['cart'] as $item_id => $quantity):
    $item = $conn->query("SELECT price FROM menu_items WHERE id = $item_id")->fetch();
    $cart_total += $item['price'] * $quantity;
    $cart_items_count++;
endforeach;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Smart Dine</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-status-badge {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            margin-left: 0.5rem;
        }
        .table-available {
            background-color: #d4edda;
            color: #155724;
        }
        .table-occupied {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-info {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f0f8ff;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
        }
        .table-grid-legend {
            margin-top: 1.5rem;
            padding: 1rem;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 0.5rem;
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
                        <a class="nav-link" href="customer_menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customer_orders.php">My Orders</a>
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
        <h1 class="page-title">Checkout</h1>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <a href="customer_orders.php" class="btn btn-sm btn-primary">View My Orders</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Order Summary -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Order Summary</h5>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($_SESSION['cart'] as $item_id => $quantity):
                                        $item = $conn->query("SELECT name, price FROM menu_items WHERE id = $item_id")->fetch();
                                        $subtotal = $item['price'] * $quantity;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo $quantity; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱<?php echo number_format($subtotal, 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end">
                            <h4>Total Items: <span class="badge bg-info"><?php echo $cart_items_count; ?></span></h4>
                            <h3>Grand Total: <span class="badge bg-success" style="font-size: 1.2rem;">₱<?php echo number_format($cart_total, 2); ?></span></h3>
                        </div>
                    </div>
                </div>

                <!-- Table Availability Legend -->
                <div class="table-grid-legend">
                    <h6 style="margin-bottom: 1rem; font-weight: bold;">
                        <i class="bi bi-info-circle"></i> Table Status
                    </h6>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #d4edda;"></div>
                        <span><strong>Green</strong> - Available (You can choose)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #f8d7da;"></div>
                        <span><strong>Red</strong> - Occupied (Cannot choose)</span>
                    </div>
                </div>
            </div>

            <!-- Delivery Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Delivery Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person"></i> Customer Name
                                </label>
                                <input type="text" class="form-control" name="customer_name" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-chair"></i> Table Number
                                </label>
                                <select class="form-select" name="table_number" id="tableSelect" required onchange="updateTableInfo()">
                                    <option value="">Select a table</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <?php $is_occupied = in_array($i, $occupied_tables); ?>
                                        <option value="<?php echo $i; ?>" <?php echo $is_occupied ? 'disabled' : ''; ?>>
                                            <?php 
                                                echo 'Table ' . $i;
                                                if ($is_occupied) {
                                                    echo ' (Occupied) ❌';
                                                } else {
                                                    echo ' (Available) ✅';
                                                }
                                            ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <div id="tableInfo" class="table-info" style="display: none;">
                                    <strong>Table Status:</strong>
                                    <div id="tableStatusText"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-cash-coin"></i> Payment Method
                                </label>
                                <select class="form-select" required>
                                    <option value="">Select payment method</option>
                                    <option value="cash">Cash Payment</option>
                                    <option value="card">Card Payment</option>
                                    <option value="online">Online Payment</option>
                                </select>
                            </div>

                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Order Total:</strong> ₱<?php echo number_format($cart_total, 2); ?>
                            </div>

                            <button type="submit" name="place_order" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-check-circle"></i> Place Order
                            </button>

                            <a href="customer_menu.php" class="btn btn-secondary w-100 mt-2">
                                <i class="bi bi-arrow-left"></i> Back to Menu
                            </a>
                        </form>
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
        // JavaScript array of occupied tables (from PHP)
        const occupiedTables = <?php echo json_encode($occupied_tables); ?>;

        function updateTableInfo() {
            const tableSelect = document.getElementById('tableSelect');
            const tableInfo = document.getElementById('tableInfo');
            const tableStatusText = document.getElementById('tableStatusText');
            const selectedTable = tableSelect.value;

            if (selectedTable) {
                const isOccupied = occupiedTables.includes(parseInt(selectedTable));
                
                if (isOccupied) {
                    tableInfo.style.display = 'block';
                    tableInfo.className = 'table-info';
                    tableInfo.style.backgroundColor = '#f8d7da';
                    tableInfo.style.borderLeftColor = '#dc3545';
                    tableStatusText.innerHTML = '<span style="color: #721c24;"><i class="bi bi-x-circle"></i> <strong>Occupied</strong> - This table is currently being used. Please select another table.</span>';
                } else {
                    tableInfo.style.display = 'block';
                    tableInfo.className = 'table-info';
                    tableInfo.style.backgroundColor = '#d4edda';
                    tableInfo.style.borderLeftColor = '#28a745';
                    tableStatusText.innerHTML = '<span style="color: #155724;"><i class="bi bi-check-circle"></i> <strong>Available</strong> - This table is available for your order.</span>';
                }
            } else {
                tableInfo.style.display = 'none';
            }
        }
    </script>
</body>
</html>