<?php
session_start();
// Redirect the user to login page if he is not logged in.
if(!isset($_SESSION['loggedIn'])){
    header('Location: login.php');
    exit();
}

require_once('inc/config/constants.php');
require_once('inc/config/db.php');

// Check if sale data is provided
if (!isset($_POST['saleData'])) {
    die("No sale data provided");
}

$saleData = json_decode($_POST['saleData'], true);

if (!$saleData) {
    die("Invalid sale data - JSON decode failed. Received: " . htmlspecialchars($_POST['saleData']));
}

// Validate required fields
$requiredFields = ['saleID', 'itemNumber', 'itemName', 'quantity', 'unitPrice', 'saleDate'];
foreach ($requiredFields as $field) {
    if (!isset($saleData[$field]) || empty($saleData[$field])) {
        die("Missing required field: $field");
    }
}

// Check format parameter
$format = isset($_POST['format']) ? $_POST['format'] : 'html';

// Generate receipt based on format
switch ($format) {
    case 'pdf':
        generatePDFReceipt($saleData);
        break;
    case 'doc':
        generateDOCReceipt($saleData);
        break;
    case 'txt':
        generateTXTReceipt($saleData);
        break;
    case 'csv':
        generateCSVReceipt($saleData);
        break;
    case 'print':
    default:
        generateHTMLReceipt($saleData);
        break;
}

// Get current date and time
$currentDate = date('Y-m-d H:i:s');

// Generate receipt HTML
$receiptHtml = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FATUCKS ENTERPRISE - Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 12px;
            color: #666;
        }
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th,
        .items-table td {
            border-bottom: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .items-table th {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .total-section {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        @media print {
            body {
                background: white;
                padding: 10px;
            }
            .receipt-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="company-name">FATUCKS ENTERPRISE</div>
            <div class="company-address">Main Grafton Regent Highway, Kossoh Town</div>
            <div class="company-address">Phone: +232 78 733611</div>
        </div>

        <div class="receipt-title">SALES RECEIPT</div>

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Receipt No:</span>
                <span>' . $saleData['saleID'] . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span>' . date('d/m/Y', strtotime($saleData['saleDate'])) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time:</span>
                <span>' . date('H:i:s') . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer:</span>
                <span>' . ($saleData['customerName'] ?? 'Walk-in Customer') . '</span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . $saleData['itemName'] . '</td>
                    <td>' . $saleData['quantity'] . '</td>
                    <td>NLE ' . number_format($saleData['unitPrice'], 2) . '</td>
                    <td>NLE ' . number_format($saleData['unitPrice'] * $saleData['quantity'], 2) . '</td>
                </tr>
            </tbody>
        </table>';

if (isset($saleData['discount']) && $saleData['discount'] > 0) {
    $subtotal = $saleData['unitPrice'] * $saleData['quantity'];
    $discountAmount = $subtotal * ($saleData['discount'] / 100);
    $total = $subtotal - $discountAmount;

    $receiptHtml .= '
        <div class="total-section">
            <div class="detail-row">
                <span class="detail-label">Subtotal:</span>
                <span>NLE ' . number_format($subtotal, 2) . '</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Discount (' . $saleData['discount'] . '%):</span>
                <span>-NLE ' . number_format($discountAmount, 2) . '</span>
            </div>
            <div class="total-row">
                <span>TOTAL:</span>
                <span>NLE ' . number_format($total, 2) . '</span>
            </div>
        </div>';
} else {
    $total = $saleData['unitPrice'] * $saleData['quantity'];
    $receiptHtml .= '
        <div class="total-section">
            <div class="total-row">
                <span>TOTAL:</span>
                <span>NLE ' . number_format($total, 2) . '</span>
            </div>
        </div>';
}

$receiptHtml .= '
        <div class="footer">
            <div>Thank you for shopping with FATUCKS ENTERPRISE!</div>
            <div style="margin-top: 10px; font-size: 10px;">
                This is a computer generated receipt.<br>
                No signature required.
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>';

// Output the receipt
echo $receiptHtml;

// Function to generate HTML receipt (for printing)
function generateHTMLReceipt($saleData) {
    global $receiptHtml;
    echo $receiptHtml;
}

// Function to generate PDF receipt
function generatePDFReceipt($saleData) {
    // Set headers for PDF download
    $filename = 'receipt_' . $saleData['saleID'] . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Create basic PDF content (simple text-based PDF)
    $pdfContent = "%PDF-1.4\n";
    $pdfContent .= "1 0 obj\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Type /Catalog\n";
    $pdfContent .= "/Pages 2 0 R\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "endobj\n";

    $pdfContent .= "2 0 obj\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Type /Pages\n";
    $pdfContent .= "/Kids [3 0 R]\n";
    $pdfContent .= "/Count 1\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "endobj\n";

    $pdfContent .= "3 0 obj\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Type /Page\n";
    $pdfContent .= "/Parent 2 0 R\n";
    $pdfContent .= "/MediaBox [0 0 612 792]\n";
    $pdfContent .= "/Contents 4 0 R\n";
    $pdfContent .= "/Resources << /Font << /F1 5 0 R >> >>\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "endobj\n";

    // Create text content for PDF
    $content = "BT\n";
    $content .= "/F1 24 Tf\n";
    $content .= "50 750 Td\n";
    $content .= "(FATUCKS ENTERPRISE) Tj\n";
    $content .= "0 -30 Td\n";
    $content .= "(SALES RECEIPT) Tj\n";
    $content .= "/F1 12 Tf\n";
    $content .= "0 -40 Td\n";
    $content .= "(Receipt No: " . $saleData['saleID'] . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Date: " . date('d/m/Y', strtotime($saleData['saleDate'])) . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Customer: " . ($saleData['customerName'] ?? 'Walk-in Customer') . ") Tj\n";
    $content .= "0 -30 Td\n";
    $content .= "(Item: " . $saleData['itemName'] . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Quantity: " . $saleData['quantity'] . ") Tj\n";
    $content .= "0 -20 Td\n";
    $content .= "(Unit Price: NLE " . number_format($saleData['unitPrice'], 2) . ") Tj\n";

    $total = $saleData['unitPrice'] * $saleData['quantity'];
    if (isset($saleData['discount']) && $saleData['discount'] > 0) {
        $discountAmount = $total * ($saleData['discount'] / 100);
        $total = $total - $discountAmount;
        $content .= "0 -20 Td\n";
        $content .= "(Discount: " . $saleData['discount'] . "%) Tj\n";
    }

    $content .= "0 -30 Td\n";
    $content .= "/F1 14 Tf\n";
    $content .= "(TOTAL: NLE " . number_format($total, 2) . ") Tj\n";
    $content .= "ET\n";

    $pdfContent .= "4 0 obj\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Length " . strlen($content) . "\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "stream\n";
    $pdfContent .= $content;
    $pdfContent .= "endstream\n";
    $pdfContent .= "endobj\n";

    $pdfContent .= "5 0 obj\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Type /Font\n";
    $pdfContent .= "/Subtype /Type1\n";
    $pdfContent .= "/BaseFont /Helvetica\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "endobj\n";

    $pdfContent .= "xref\n";
    $pdfContent .= "0 6\n";
    $pdfContent .= "0000000000 65535 f \n";
    $pdfContent .= "0000000010 00000 n \n";
    $pdfContent .= "0000000053 00000 n \n";
    $pdfContent .= "0000000125 00000 n \n";
    $pdfContent .= sprintf("%011d 00000 n \n", strlen($pdfContent) - 200);
    $pdfContent .= sprintf("%011d 00000 n \n", strlen($pdfContent) - 100);
    $pdfContent .= "trailer\n";
    $pdfContent .= "<<\n";
    $pdfContent .= "/Size 6\n";
    $pdfContent .= "/Root 1 0 R\n";
    $pdfContent .= ">>\n";
    $pdfContent .= "startxref\n";
    $pdfContent .= (strlen($pdfContent) - 50) . "\n";
    $pdfContent .= "%%EOF\n";

    echo $pdfContent;
}

// Function to generate DOC/Word receipt
function generateDOCReceipt($saleData) {
    $filename = 'receipt_' . $saleData['saleID'] . '.doc';
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Create Word document content (HTML with Word-specific headers)
    $docContent = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';
    $docContent .= '<head><title>FATUCKS ENTERPRISE - Receipt ' . $saleData['saleID'] . '</title>';
    $docContent .= '<meta charset="utf-8">';
    $docContent .= '<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>90</w:Zoom><w:DoNotPromoteQF/><w:DoNotOptimizeForBrowser/></w:WordDocument></xml><![endif]-->';
    $docContent .= '<style>';
    $docContent .= 'body { font-family: Arial, sans-serif; margin: 20px; }';
    $docContent .= '.header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }';
    $docContent .= '.company-name { font-size: 24px; font-weight: bold; }';
    $docContent .= '.receipt-title { font-size: 18px; font-weight: bold; text-align: center; margin: 20px 0; }';
    $docContent .= '.detail-row { margin-bottom: 5px; }';
    $docContent .= '.detail-label { font-weight: bold; }';
    $docContent .= '.total-section { border-top: 2px solid #333; padding-top: 10px; margin-top: 20px; }';
    $docContent .= '.total-row { font-weight: bold; font-size: 16px; margin-bottom: 10px; }';
    $docContent .= '</style>';
    $docContent .= '</head><body>';

    $docContent .= '<div class="header">';
    $docContent .= '<div class="company-name">FATUCKS ENTERPRISE</div>';
    $docContent .= '<div>Main Grafton Regent Highway, Kossoh Town</div>';
    $docContent .= '<div>Phone: +232 78 733611</div>';
    $docContent .= '</div>';

    $docContent .= '<div class="receipt-title">SALES RECEIPT</div>';

    $docContent .= '<div>';
    $docContent .= '<div class="detail-row"><span class="detail-label">Receipt No:</span> <span>' . $saleData['saleID'] . '</span></div>';
    $docContent .= '<div class="detail-row"><span class="detail-label">Date:</span> <span>' . date('d/m/Y', strtotime($saleData['saleDate'])) . '</span></div>';
    $docContent .= '<div class="detail-row"><span class="detail-label">Time:</span> <span>' . date('H:i:s') . '</span></div>';
    $docContent .= '<div class="detail-row"><span class="detail-label">Customer:</span> <span>' . ($saleData['customerName'] ?? 'Walk-in Customer') . '</span></div>';
    $docContent .= '</div>';

    $docContent .= '<table border="1" style="width: 100%; border-collapse: collapse; margin: 20px 0;">';
    $docContent .= '<tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>';
    $docContent .= '<tr>';
    $docContent .= '<td>' . $saleData['itemName'] . '</td>';
    $docContent .= '<td>' . $saleData['quantity'] . '</td>';
    $docContent .= '<td>NLE ' . number_format($saleData['unitPrice'], 2) . '</td>';
    $docContent .= '<td>NLE ' . number_format($saleData['unitPrice'] * $saleData['quantity'], 2) . '</td>';
    $docContent .= '</tr>';
    $docContent .= '</table>';

    $total = $saleData['unitPrice'] * $saleData['quantity'];
    if (isset($saleData['discount']) && $saleData['discount'] > 0) {
        $discountAmount = $total * ($saleData['discount'] / 100);
        $finalTotal = $total - $discountAmount;
        $docContent .= '<div class="total-section">';
        $docContent .= '<div class="detail-row"><span class="detail-label">Subtotal:</span> <span>NLE ' . number_format($total, 2) . '</span></div>';
        $docContent .= '<div class="detail-row"><span class="detail-label">Discount (' . $saleData['discount'] . '%):</span> <span>-NLE ' . number_format($discountAmount, 2) . '</span></div>';
        $docContent .= '<div class="total-row"><span>TOTAL:</span> <span>NLE ' . number_format($finalTotal, 2) . '</span></div>';
        $docContent .= '</div>';
    } else {
        $docContent .= '<div class="total-section">';
        $docContent .= '<div class="total-row"><span>TOTAL:</span> <span>NLE ' . number_format($total, 2) . '</span></div>';
        $docContent .= '</div>';
    }

    $docContent .= '<div style="text-align: center; margin-top: 30px; font-size: 12px;">';
    $docContent .= '<div>Thank you for shopping with FATUCKS ENTERPRISE!</div>';
    $docContent .= '<div style="margin-top: 10px;">This is a computer generated receipt.<br>No signature required.</div>';
    $docContent .= '</div>';

    $docContent .= '</body></html>';

    echo $docContent;
}

// Function to generate TXT receipt
function generateTXTReceipt($saleData) {
    $filename = 'receipt_' . $saleData['saleID'] . '.txt';
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    $txtContent = "================================\n";
    $txtContent .= "    FATUCKS ENTERPRISE\n";
    $txtContent .= "   SALES RECEIPT\n";
    $txtContent .= "================================\n\n";

    $txtContent .= "Receipt No: " . $saleData['saleID'] . "\n";
    $txtContent .= "Date: " . date('d/m/Y', strtotime($saleData['saleDate'])) . "\n";
    $txtContent .= "Time: " . date('H:i:s') . "\n";
    $txtContent .= "Customer: " . ($saleData['customerName'] ?? 'Walk-in Customer') . "\n\n";

    $txtContent .= "Item: " . $saleData['itemName'] . "\n";
    $txtContent .= "Quantity: " . $saleData['quantity'] . "\n";
    $txtContent .= "Unit Price: NLE " . number_format($saleData['unitPrice'], 2) . "\n";
    $txtContent .= "Line Total: NLE " . number_format($saleData['unitPrice'] * $saleData['quantity'], 2) . "\n\n";

    $total = $saleData['unitPrice'] * $saleData['quantity'];
    if (isset($saleData['discount']) && $saleData['discount'] > 0) {
        $discountAmount = $total * ($saleData['discount'] / 100);
        $finalTotal = $total - $discountAmount;
        $txtContent .= "Subtotal: NLE " . number_format($total, 2) . "\n";
        $txtContent .= "Discount (" . $saleData['discount'] . "%): -NLE " . number_format($discountAmount, 2) . "\n";
        $txtContent .= "TOTAL: NLE " . number_format($finalTotal, 2) . "\n\n";
    } else {
        $txtContent .= "TOTAL: NLE " . number_format($total, 2) . "\n\n";
    }

    $txtContent .= "================================\n";
    $txtContent .= "Thank you for shopping with\n";
    $txtContent .= "     FATUCKS ENTERPRISE!\n";
    $txtContent .= "================================\n";
    $txtContent .= "This is a computer generated receipt.\n";
    $txtContent .= "No signature required.\n";

    echo $txtContent;
}

// Function to generate CSV receipt
function generateCSVReceipt($saleData) {
    $filename = 'receipt_' . $saleData['saleID'] . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Create CSV content
    $csvContent = "FATUCKS ENTERPRISE,SALES RECEIPT,,\n";
    $csvContent .= "Receipt No:," . $saleData['saleID'] . ",,\n";
    $csvContent .= "Date:," . date('d/m/Y', strtotime($saleData['saleDate'])) . ",,\n";
    $csvContent .= "Time:," . date('H:i:s') . ",,\n";
    $csvContent .= "Customer:," . ($saleData['customerName'] ?? 'Walk-in Customer') . ",,\n";
    $csvContent .= ",,,\n";
    $csvContent .= "Item,Quantity,Unit Price,Total\n";
    $csvContent .= '"' . $saleData['itemName'] . '",' . $saleData['quantity'] . ',NLE ' . number_format($saleData['unitPrice'], 2) . ',NLE ' . number_format($saleData['unitPrice'] * $saleData['quantity'], 2) . "\n";
    $csvContent .= ",,,\n";

    $total = $saleData['unitPrice'] * $saleData['quantity'];
    if (isset($saleData['discount']) && $saleData['discount'] > 0) {
        $discountAmount = $total * ($saleData['discount'] / 100);
        $finalTotal = $total - $discountAmount;
        $csvContent .= "Subtotal,,,NLE " . number_format($total, 2) . "\n";
        $csvContent .= "Discount (" . $saleData['discount'] . "%),,,-NLE " . number_format($discountAmount, 2) . "\n";
        $csvContent .= "TOTAL,,, NLE " . number_format($finalTotal, 2) . "\n";
    } else {
        $csvContent .= "TOTAL,,, NLE " . number_format($total, 2) . "\n";
    }

    $csvContent .= ",,,\n";
    $csvContent .= "Thank you for shopping with FATUCKS ENTERPRISE!, , ,\n";
    $csvContent .= "This is a computer generated receipt., , ,\n";
    $csvContent .= "No signature required., , ,\n";

    echo $csvContent;
}
?>
