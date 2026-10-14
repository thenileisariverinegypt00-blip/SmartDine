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
    <title>Reports & Analytics - Smart Dine Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .report-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-top: 4px solid #d4a574;
            transition: all 0.3s;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
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
                        <a class="nav-link active" href="admin_reports.php">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_menu.php">
                            <i class="bi bi-list"></i> Menu
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
        <h1 class="page-title">📊 Reports & Analytics</h1>

        <!-- Report Types -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="report-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-receipt" style="font-size: 3rem; color: #d4a574;"></i>
                        <h5 class="card-title mt-3">Menu Catalog</h5>
                        <p class="card-text text-muted">Print your complete menu with all items, descriptions, and prices</p>
                        <a href="generate_menu_catalog.php" class="btn btn-primary" target="_blank">
                            <i class="bi bi-download"></i> Download PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="report-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-graph-up" style="font-size: 3rem; color: #d4a574;"></i>
                        <h5 class="card-title mt-3">Sales Report</h5>
                        <p class="card-text text-muted">Generate detailed sales reports for a date range</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#salesReportModal">
                            <i class="bi bi-calendar"></i> Select Dates
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="report-card card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-pdf" style="font-size: 3rem; color: #d4a574;"></i>
                        <h5 class="card-title mt-3">Order Invoices</h5>
                        <p class="card-text text-muted">Download individual order invoices</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#invoiceModal">
                            <i class="bi bi-search"></i> Find Order
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recent Orders (Completed)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Table</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $orders = $conn->query("SELECT * FROM orders WHERE status = 'completed' ORDER BY created_at DESC LIMIT 20");
                            foreach ($orders as $order):
                            ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo $order['table_number']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-success">₱<?php echo number_format($order['total_amount'], 2); ?></span></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="generate_order_invoice.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-download"></i> Invoice
                                    </a>
                                    <a href="generate_receipt.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info text-white" target="_blank">
                                        <i class="bi bi-printer"></i> Receipt
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Report Modal -->
    <div class="modal fade" id="salesReportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Sales Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="salesReportForm">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required value="<?php echo date('Y-m-01'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateSalesReport()">
                        <i class="bi bi-download"></i> Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Modal -->
    <div class="modal fade" id="invoiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Download Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Order ID</label>
                        <input type="number" class="form-control" id="order_id" placeholder="Enter order ID" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="downloadInvoice()">
                        <i class="bi bi-download"></i> Download Invoice
                    </button>
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
        function generateSalesReport() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            
            if (start_date && end_date) {
                window.open(`generate_sales_report.php?start_date=${start_date}&end_date=${end_date}`, '_blank');
            } else {
                alert('Please select both dates');
            }
        }

        function downloadInvoice() {
            const order_id = document.getElementById('order_id').value;
            
            if (order_id) {
                window.open(`generate_order_invoice.php?id=${order_id}`, '_blank');
            } else {
                alert('Please enter an order ID');
            }
        }
    </script>
</body>
</html>