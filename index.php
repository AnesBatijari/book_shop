<?php
include 'database.php';
include 'sql_queries.php';

$options = get_filter_options($conn);
$sales = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_database'])) {
        create_tables($conn);
        if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['json_file']['tmp_name'];
            $data = json_decode(file_get_contents($file_tmp), true);
            $sales = insert_data($conn, $data);
        }
    }

}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['update_table'])) {
    $filters = [
        'customer' => $_GET['customer'] ?? '',
        'product' => $_GET['product'] ?? '',
        'price' => $_GET['price'] ?? ''
    ];
    $sales = filter_data($conn, $filters);  
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Shop Sales</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body class='container'>
    <div class="title">
        <p>Book Shop</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="json_file" accept="application/json" required>
            <button name="update_database">Insert data from JSON</button>
        </form>       
    </div>
    <form method="get">
        <select name="customer">
            <option value="">Select Customer</option>
            <?php foreach ($options['customers'] as $customer): ?>
                <option value="<?= $customer['id'] ?>" <?= isset($_GET['customer']) && $_GET['customer'] == $customer['id'] ? 'selected' : '' ?>><?= $customer['name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="product">
            <option value="">Select Product</option>
            <?php foreach ($options['products'] as $product): ?>
                <option value="<?= $product['id'] ?>" <?= isset($_GET['product']) && $_GET['product'] == $product['id'] ? 'selected' : '' ?>><?= $product['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="price">
            <option value="">Select Price Range</option>
            <?php for ($i = 0; $i <= 50; $i += 10): ?>
                <option value="<?= $i . '-' . ($i + 9.99) ?>" <?= isset($_GET['price']) && $_GET['price'] == $i . '-' . ($i + 9.99) ? 'selected' : '' ?>>
                    <?= $i ?> - <?= $i + 9.99 ?>
                </option>
            <?php endfor; ?>
        </select>
        <button name="update_table">Filter</button>
    </form>
    <table border="1">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Product</th>
                <th>Price</th>
                <th>Sale Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($sales)): ?>
            <?php $total = 0; ?>
            <?php foreach ($sales as $sale): ?>
                <tr>
                    <td><?= $sale['customer'] ?></td>
                    <td><?= $sale['product'] ?></td>
                    <td><?= $sale['price'] ?></td>
                    <td><?= $sale['sale_date'] ?></td>
                </tr>
                <?php $total += $sale['price']; ?>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2">Total</td>
                    <td colspan="2"><?= $total ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="3">No sales data available.</td>
                </tr> 
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>