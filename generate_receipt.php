<?php
session_start();
include 'db_config.php';
require_once('includes/TCPDF/tcpdf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$order_id = $_GET['id'] ?? 0;

$order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$order->execute([$order_id]);
$order_data = $order->fetch(PDO::FETCH_ASSOC);

if ($_SESSION['role'] == 'customer' && $order_data['user_id'] != $_SESSION['user_id']) {
    die('Unauthorized access');
}

$items = $conn->prepare("SELECT oi.*, mi.name FROM order_items oi 
                        JOIN menu_items mi ON oi.menu_item_id = mi.id 
                        WHERE oi.order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll(PDO::FETCH_ASSOC);

class TCPDF_RECEIPT extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

$pdf = new TCPDF_RECEIPT('P', 'mm', array(80, 200), true, 'UTF-8', false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(5, 5, 5);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 5, 'SMART DINE', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 3, 'Restaurant Receipt', 0, 1, 'C');
$pdf->Ln(2);

// Receipt Number and Date
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(40, 3, 'Receipt #' . str_pad($order_data['id'], 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(40, 3, date('M d, Y H:i:s', strtotime($order_data['created_at'])), 0, 1);
$pdf->Ln(2);

// Divider
$pdf->SetDrawColor(200, 200, 200);
$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// Items
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(40, 3, 'ITEM', 0, 0);
$pdf->Cell(15, 3, 'QTY', 0, 0, 'R');
$pdf->Cell(20, 3, 'TOTAL', 0, 1, 'R');

$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->SetFont('helvetica', '', 7);
$total = 0;

foreach ($order_items as $item) {
    $subtotal = $item['quantity'] * $item['price'];
    $total += $subtotal;
    
    $pdf->Cell(40, 3, substr($item['name'], 0, 18), 0, 0);
    $pdf->Cell(15, 3, $item['quantity'], 0, 0, 'R');
    $pdf->Cell(20, 3, '₱' . number_format($subtotal, 2), 0, 1, 'R');
}

$pdf->Line(5, $pdf->GetY(), 75, $pdf->GetY());
$pdf->Ln(2);

// Total
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(55, 4, 'TOTAL:', 0, 0, 'R');
$pdf->Cell(20, 4, '₱' . number_format($total, 2), 0, 1, 'R');
$pdf->Ln(3);

// Table Info
$pdf->SetFont('helvetica', '', 7);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 3, 'Table: ' . $order_data['table_number'], 0, 1, 'C');
$pdf->Cell(0, 3, htmlspecialchars($order_data['customer_name'] ?? ''), 0, 1, 'C');
$pdf->Ln(2);

// Thank You
$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 3, 'Thank You!', 0, 1, 'C');
$pdf->Cell(0, 3, 'Visit Again', 0, 1, 'C');

$pdf->Output('Receipt_' . $order_data['id'] . '.pdf', 'D');
?>
