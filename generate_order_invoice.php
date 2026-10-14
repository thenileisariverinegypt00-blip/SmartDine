<?php
session_start();
include 'db_config.php';
require_once('includes/TCPDF/tcpdf.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['id'] ?? 0;

// Fetch order data
$order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$order_id]);
$order_data = $order->fetch(PDO::FETCH_ASSOC);

// Check if order exists
if (!$order_data) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Order Not Found - Smart Dine</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                max-width: 500px;
            }
            .error-icon {
                font-size: 4rem;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            .error-title {
                color: #8b4513;
                font-weight: bold;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 30px;
            }
            .btn-back {
                background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
                color: white;
                border: none;
                padding: 10px 30px;
                border-radius: 5px;
                text-decoration: none;
                font-weight: bold;
            }
            .btn-back:hover {
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="bi bi-exclamation-circle"></i>
            </div>
            <h2 class="error-title">Order Not Found</h2>
            <p class="error-message">
                The order ID <strong>#<?php echo htmlspecialchars($order_id); ?></strong> does not exist in the system.
            </p>
            <a href="admin_reports.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Go Back to Reports
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If customer, check if they own this order
if ($_SESSION['role'] == 'customer' && $order_data['user_id'] != $_SESSION['user_id']) {
    die('Unauthorized access');
}

// Fetch order items
$items = $conn->prepare("SELECT oi.*, mi.name FROM order_items oi 
                        JOIN menu_items mi ON oi.menu_item_id = mi.id 
                        WHERE oi.order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll(PDO::FETCH_ASSOC);

// Check if order has items
if (empty($order_items)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Items in Order - Smart Dine</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                max-width: 500px;
            }
            .error-icon {
                font-size: 4rem;
                color: #f39c12;
                margin-bottom: 20px;
            }
            .error-title {
                color: #8b4513;
                font-weight: bold;
                margin-bottom: 15px;
            }
            .error-message {
                color: #666;
                margin-bottom: 30px;
            }
            .btn-back {
                background: linear-gradient(135deg, #d4a574 0%, #8b4513 100%);
                color: white;
                border: none;
                padding: 10px 30px;
                border-radius: 5px;
                text-decoration: none;
                font-weight: bold;
            }
            .btn-back:hover {
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="bi bi-info-circle"></i>
            </div>
            <h2 class="error-title">No Items in Order</h2>
            <p class="error-message">
                Order #<?php echo htmlspecialchars($order_id); ?> has no items. Please add items to the order before generating an invoice.
            </p>
            <a href="admin_reports.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Go Back to Reports
            </a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Create new PDF document
class TCPDF_CUSTOM extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(139, 69, 19); // Dark brown
        $this->Cell(0, 15, '🍽️ SMART DINE', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Restaurant Management System', 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetDrawColor(212, 165, 116);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Generated on ' . date('M d, Y H:i'), 0, 0, 'C');
    }
}

$pdf = new TCPDF_CUSTOM('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 10, 'ORDER INVOICE', 0, 1, 'C');
$pdf->Ln(3);

// Order Info Section
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$pdf->SetFillColor(212, 165, 116);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 7, 'Order Information', 0, 0, 'L', true);
$pdf->Cell(95, 7, 'Customer Information', 0, 1, 'L', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(50, 50, 50);

$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(95, 6, 'Order ID: #' . $order_data['id'], 0, 0, 'L', true);
$pdf->Cell(95, 6, 'Name: ' . htmlspecialchars($order_data['customer_name'] ?? 'N/A'), 0, 1, 'L', true);

$pdf->Cell(95, 6, 'Table: ' . $order_data['table_number'], 0, 0, 'L', true);
$pdf->Cell(95, 6, 'Table Number: ' . $order_data['table_number'], 0, 1, 'L', true);

$pdf->Cell(95, 6, 'Date: ' . date('M d, Y', strtotime($order_data['created_at'])), 0, 0, 'L', true);
$pdf->Cell(95, 6, 'Status: ' . ucfirst($order_data['status']), 0, 1, 'L', true);

$pdf->Cell(95, 6, 'Time: ' . date('H:i:s', strtotime($order_data['created_at'])), 0, 0, 'L', true);
$pdf->Cell(95, 6, '', 0, 1, 'L', true);

$pdf->Ln(5);

// Items Table Header
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(139, 69, 19);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(70, 8, 'Item Name', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Quantity', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Unit Price', 1, 0, 'R', true);
$pdf->Cell(40, 8, 'Subtotal', 1, 1, 'R', true);

// Items Table Body
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$total = 0;

foreach ($order_items as $item) {
    $subtotal = $item['quantity'] * $item['price'];
    $total += $subtotal;
    
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(70, 7, htmlspecialchars($item['name']), 1, 0, 'L', true);
    $pdf->Cell(25, 7, $item['quantity'], 1, 0, 'C', true);
    $pdf->Cell(35, 7, '₱' . number_format($item['price'], 2), 1, 0, 'R', true);
    $pdf->Cell(40, 7, '₱' . number_format($subtotal, 2), 1, 1, 'R', true);
}

// Total Section
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetFillColor(212, 165, 116);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(130, 10, 'TOTAL AMOUNT:', 1, 0, 'R', true);
$pdf->Cell(40, 10, '₱' . number_format($order_data['total_amount'], 2), 1, 1, 'R', true);

$pdf->Ln(10);

// Signature Section
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 8, 'ACKNOWLEDGMENT & SIGNATURE', 0, 1);

$pdf->Ln(5);

// Two column signature section
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

// Left column - Customer Signature
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(95, 6, 'Customer/Authorized Person:', 0, 0, 'L');
$pdf->Cell(95, 6, 'Server/Manager:', 0, 1, 'L');

$pdf->Ln(35);

// Signature lines
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(15, $pdf->GetY(), 105, $pdf->GetY());
$pdf->Line(110, $pdf->GetY(), 200, $pdf->GetY());

$pdf->Ln(3);

// Name lines
$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(95, 4, 'Signature / Printed Name', 0, 0, 'C');
$pdf->Cell(95, 4, 'Signature / Printed Name', 0, 1, 'C');

$pdf->Ln(8);

// Notes Section
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, 'I hereby acknowledge receipt of the above mentioned items in good condition. This invoice is valid for 30 days from the date of issue.', 0, 'L');

$pdf->Ln(5);

// Thank You Message
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 10, 'Thank you for dining with Smart Dine!', 0, 1, 'C');

// Output PDF
$pdf->Output('Invoice_Order_' . $order_data['id'] . '.pdf', 'D');
?>
