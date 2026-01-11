<?php
require_once('inc/config/constants.php');
require_once('inc/config/db.php');

// Function to get the highest item number
function getHighestItemNumber() {
    global $conn;
    $sql = "SELECT MAX(CAST(itemNumber AS UNSIGNED)) as max_item FROM item";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['max_item'] ? (int)$result['max_item'] : 0;
}

// Function to extract products from Excel data
function extractProductsFromExcel($excelData) {
    $products = [];
    $lines = explode("\n", $excelData);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'FATUCKS ENTERPRISE') !== false || strpos($line, 'Product') !== false) {
            continue; // Skip headers and empty lines
        }

        // Parse the tab-separated values
        $columns = preg_split('/\t+/', $line);
        $columnCount = count($columns);

        // Skip lines that don't have enough columns
        if ($columnCount < 4) {
            continue;
        }

        // Extract data based on column positions
        $productName = trim($columns[0]);
        $costPrice = isset($columns[1]) ? trim($columns[1]) : '';
        $cartoonPrice = isset($columns[2]) ? trim($columns[2]) : '';
        $unitPrice = isset($columns[3]) ? trim($columns[3]) : '';

        // Skip if no product name
        if (empty($productName) || $productName == '') {
            continue;
        }

        // Handle unit prices with "/" separator (create multiple products)
        $unitPrices = [];

        // Check unit price column first
        if (!empty($unitPrice) && strpos($unitPrice, '/') !== false) {
            $priceParts = explode('/', $unitPrice);
            foreach ($priceParts as $index => $price) {
                $price = trim($price);
                if (is_numeric($price) && (float)$price > 0) {
                    $unitPrices[] = [
                        'price' => (float)$price,
                        'suffix' => $index > 0 ? $index : ''
                    ];
                }
            }
        } elseif (!empty($unitPrice) && is_numeric($unitPrice) && (float)$unitPrice > 0) {
            $unitPrices[] = [
                'price' => (float)$unitPrice,
                'suffix' => ''
            ];
        }

        // If no unit prices found in unit price column, try cost price
        if (empty($unitPrices) && !empty($costPrice)) {
            if (strpos($costPrice, '/') !== false) {
                $priceParts = explode('/', $costPrice);
                foreach ($priceParts as $index => $price) {
                    $price = trim($price);
                    if (is_numeric($price) && (float)$price > 0) {
                        $unitPrices[] = [
                            'price' => (float)$price,
                            'suffix' => $index > 0 ? $index : ''
                        ];
                    }
                }
            } elseif (is_numeric($costPrice) && (float)$costPrice > 0) {
                $unitPrices[] = [
                    'price' => (float)$costPrice,
                    'suffix' => ''
                ];
            }
        }

        // Skip if no valid prices found
        if (empty($unitPrices)) {
            continue;
        }

        // Process cartoon price from Cartoon Price column
        $finalCartoonPrice = 0;
        if (!empty($cartoonPrice) && is_numeric($cartoonPrice) && (float)$cartoonPrice > 0) {
            $finalCartoonPrice = (float)$cartoonPrice;
        }

        // Create products for each unit price variant
        foreach ($unitPrices as $priceData) {
            $itemName = $productName . $priceData['suffix'];
            $unitPriceValue = $priceData['price'];

            // Set cartoon price if not specified (use from column or calculate)
            $itemCartoonPrice = $finalCartoonPrice > 0 ? $finalCartoonPrice : $unitPriceValue * 10;

            $products[] = [
                'name' => $itemName,
                'stock' => 50, // Default stock
                'unitPrice' => $unitPriceValue,
                'cartoonPrice' => $itemCartoonPrice
            ];
        }
    }

    return array_filter($products); // Remove empty entries
}

// Main import process
try {
    echo "<h1>FATUCKS ENTERPRISE - Excel Product Import</h1>";
    echo "<style>body{font-family:Arial,sans-serif;margin:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;} .success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}</style>";

    // Get Excel data from POST request
    $excelData = isset($_POST['excelData']) ? $_POST['excelData'] : '';

    if (empty($excelData)) {
        echo "<form method='POST'>";
        echo "<h3>Paste Excel Data Below:</h3>";
        echo "<textarea name='excelData' rows='20' cols='100' placeholder='Paste your Excel data here...'></textarea><br><br>";
        echo "<input type='submit' value='Import Products'>";
        echo "</form>";
        exit;
    }

    // Extract products from Excel data
    $products = extractProductsFromExcel($excelData);

    if (empty($products)) {
        echo "<div class='error'>❌ No valid products found in Excel data.</div>";
        exit;
    }

    // Get highest item number
    $highestItemNumber = getHighestItemNumber();
    $nextItemNumber = $highestItemNumber + 1;

    echo "<h3>Import Summary:</h3>";
    echo "<p><strong>Highest existing item number:</strong> {$highestItemNumber}</p>";
    echo "<p><strong>Starting new imports from:</strong> {$nextItemNumber}</p>";
    echo "<p><strong>Products to import:</strong> " . count($products) . "</p>";

    echo "<table>";
    echo "<tr><th>Item #</th><th>Product Name</th><th>Stock</th><th>Unit Price (NLE)</th><th>Cartoon Price (NLE)</th><th>Status</th></tr>";

    $imported = 0;
    $skipped = 0;

    foreach ($products as $product) {
        $itemNumber = $nextItemNumber++;

        // Check if item already exists
        $checkSql = "SELECT COUNT(*) as count FROM item WHERE itemName = :name";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute(['name' => $product['name']]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

        if ($exists) {
            echo "<tr><td>{$itemNumber}</td><td>{$product['name']}</td><td>{$product['stock']}</td><td>NLE {$product['unitPrice']}</td><td>NLE {$product['cartoonPrice']}</td><td class='error'>SKIPPED (Already exists)</td></tr>";
            $skipped++;
            continue;
        }

        // Insert the product
        $insertSql = "INSERT INTO item (itemNumber, itemName, stock, unitPrice, cartoonPrice, status, description) VALUES (:itemNumber, :itemName, :stock, :unitPrice, :cartoonPrice, 'Active', '')";
        $insertStmt = $conn->prepare($insertSql);

        try {
            $insertStmt->execute([
                'itemNumber' => (string)$itemNumber,
                'itemName' => $product['name'],
                'stock' => $product['stock'],
                'unitPrice' => $product['unitPrice'],
                'cartoonPrice' => $product['cartoonPrice']
            ]);

            echo "<tr><td>{$itemNumber}</td><td>{$product['name']}</td><td>{$product['stock']}</td><td>NLE {$product['unitPrice']}</td><td>NLE {$product['cartoonPrice']}</td><td class='success'>IMPORTED ✓</td></tr>";
            $imported++;

        } catch (PDOException $e) {
            echo "<tr><td>{$itemNumber}</td><td>{$product['name']}</td><td>{$product['stock']}</td><td>NLE {$product['unitPrice']}</td><td>NLE {$product['cartoonPrice']}</td><td class='error'>ERROR: {$e->getMessage()}</td></tr>";
        }
    }

    echo "</table>";
    echo "<br><h3>Final Results:</h3>";
    echo "<p class='success'>✅ Successfully imported: {$imported} products</p>";
    if ($skipped > 0) {
        echo "<p class='error'>⚠️ Skipped (already exist): {$skipped} products</p>";
    }

    echo "<br><p><strong>Format used:</strong> (item_number product_name stock NLE unit_price cartoon_price)</p>";
    echo "<p><strong>Example:</strong> ({$highestItemNumber} Colgate Herbal 50 NLE 45 NLE 450)</p>";

    echo "<br><a href='index.php'>← Back to Inventory System</a>";

} catch (PDOException $e) {
    echo "<div class='error'>❌ Database Error: " . $e->getMessage() . "</div>";
}
?>
