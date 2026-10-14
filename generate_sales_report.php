<?php
session_start();
include 'db_config.php';
require_once('includes/TCPDF/tcpdf.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$orders = $conn->prepare("SELECT * FROM orders 
                         WHERE DATE(created_at) BETWEEN ? AND ? 
                         AND status = 'completed'
                         ORDER BY created_at DESC");
$orders->execute([$start_date, $end_date]);
$orders_data = $orders->fetchAll(PDO::FETCH_ASSOC);

$stats = $conn->query("SELECT 
                       COUNT(*) as total_orders,
                       SUM(total_amount) as total_revenue,
                       AVG(total_amount) as avg_order_value
                       FROM orders 
                       WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                       AND status = 'completed'")->fetch(PDO::FETCH_ASSOC);

class TCPDF_CUSTOM extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(139, 69, 19);
        $this->Cell(0, 15, '📊 SMART DINE SALES REPORT', 0, 1, 'C');
        $this->Ln(3);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage(), 0, 0, 'C');
    }
}

$pdf = new TCPDF_CUSTOM('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Report Period
$pdf->SetFont('helvetica', '', 11);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 6, 'Report Period: ' . date('M d, Y', strtotime($start_date)) . ' to ' . date('M d, Y', strtotime($end_date)), 0, 1, 'C');
$pdf->Cell(0, 6, 'Generated: ' . date('M d, Y H:i:s'), 0, 1, 'C');
$pdf->Ln(5);

// Summary Statistics
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 8, 'SUMMARY STATISTICS', 0, 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(212, 165, 116);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(50, 7, 'Metric', 1, 0, 'L', true);
$pdf->Cell(140, 7, 'Value', 1, 1, 'L', true);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(50, 6, 'Total Orders', 1, 0, 'L', true);
$pdf->Cell(140, 6, $stats['total_orders'], 1, 1, 'L', true);

$pdf->Cell(50, 6, 'Total Revenue', 1, 0, 'L', true);
$pdf->Cell(140, 6, '₱' . number_format($stats['total_revenue'] ?? 0, 2), 1, 1, 'L', true);

$pdf->Cell(50, 6, 'Average Order Value', 1, 0, 'L', true);
$pdf->Cell(140, 6, '₱' . number_format($stats['avg_order_value'] ?? 0, 2), 1, 1, 'L', true);

$pdf->Ln(5);

// Orders Table
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 8, 'DETAILED ORDERS', 0, 1);
$pdf->Ln(2);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(139, 69, 19);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(25, 7, 'Order ID', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'Table', 1, 0, 'C', true);
$pdf->Cell(50, 7, 'Customer', 1, 0, 'L', true);
$pdf->Cell(35, 7, 'Amount', 1, 0, 'R', true);
$pdf->Cell(40, 7, 'Date', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);

foreach ($orders_data as $order) {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(25, 6, '#' . $order['id'], 1, 0, 'C', true);
    $pdf->Cell(30, 6, $order['table_number'], 1, 0, 'C', true);
    $pdf->Cell(50, 6, htmlspecialchars(substr($order['customer_name'] ?? 'N/A', 0, 20)), 1, 0, 'L', true);
    $pdf->Cell(35, 6, '₱' . number_format($order['total_amount'], 2), 1, 0, 'R', true);
    $pdf->Cell(40, 6, date('M d, H:i', strtotime($order['created_at'])), 1, 1, 'C', true);
}

$pdf->Ln(8);

// Signature Section
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(139, 69, 19);
$pdf->Cell(0, 8, 'AUTHORIZATION & SIGNATURE', 0, 1);

$pdf->Ln(5);

// Prepared By Section
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'PREPARED BY:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(95, 4, 'Name: ________________________', 0, 0, 'L');
$pdf->Cell(95, 4, 'Date: ________________________', 0, 1, 'L');

$pdf->Ln(35);

// Prepared By Signature Line
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(15, $pdf->GetY(), 105, $pdf->GetY());

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(90, 3, 'Signature', 0, 1, 'L');

$pdf->Ln(10);

// Reviewed/Approved By Section
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6, 'REVIEWED & APPROVED BY:', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(95, 4, 'Name: ________________________', 0, 0, 'L');
$pdf->Cell(95, 4, 'Position: ________________________', 0, 1, 'L');

$pdf->Ln(35);

// Approved By Signature Line
$pdf->SetDrawColor(0, 0, 0);
$pdf->Line(15, $pdf->GetY(), 105, $pdf->GetY());

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(90, 3, 'Signature', 0, 1, 'L');

$pdf->Ln(8);

// Notes Section
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, 'This is an official sales report for Smart Dine Restaurant. The above information is accurate as of the date of generation. Signatures indicate verification and approval of the report data.', 0, 'L');

$pdf->Output('Sales_Report_' . date('Y-m-d') . '.pdf', 'D');
?>
