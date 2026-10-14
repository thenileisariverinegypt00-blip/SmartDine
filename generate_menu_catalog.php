<?php
session_start();
include 'db_config.php';
require_once('includes/TCPDF/tcpdf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

class TCPDF_CUSTOM extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 24);
        $this->SetTextColor(139, 69, 19);
        $this->Cell(0, 15, '🍽️ SMART DINE MENU', 0, 1, 'C');
        
        $this->SetFont('helvetica', '', 11);
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
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . ' | Generated: ' . date('M d, Y'), 0, 0, 'C');
    }
}

$pdf = new TCPDF_CUSTOM('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(15, 30, 15);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

$categories = ['Appetizers', 'Main Course', 'Desserts', 'Beverages'];

foreach ($categories as $category) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(139, 69, 19);
    $pdf->Cell(0, 10, $category, 0, 1);
    
    $items = $conn->query("SELECT * FROM menu_items WHERE category = '$category' AND available = 1 ORDER BY name");
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    foreach ($items as $item) {
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(120, 6, htmlspecialchars($item['name']), 0, 0);
        $pdf->Cell(0, 6, '₱' . number_format($item['price'], 2), 0, 1, 'R');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $description = $item['description'] ?? 'No description available';
        $pdf->MultiCell(0, 4, htmlspecialchars($description), 0, 'L');
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);
    }
    
    $pdf->Ln(3);
    $pdf->SetDrawColor(212, 165, 116);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);
}

$pdf->Output('Menu_Catalog_' . date('Y-m-d') . '.pdf', 'D');
?>
