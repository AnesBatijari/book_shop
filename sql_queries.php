<?php
//This function is used to create the tables in the database
//In real projects this is not wrriten here but because this is challenge I will leave it here for testing
function create_tables($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        price DECIMAL(10, 2) NOT NULL
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        product_id INT NOT NULL,
        sale_date DATETIME NOT NULL,
        FOREIGN KEY (customer_id) REFERENCES customers(id),
        FOREIGN KEY (product_id) REFERENCES products(id)
    )");
}

//This function is used to insert data into the database from json file and display the data on table
function insert_data($conn, $data) {
    foreach ($data as $entry) {
        $customer_id = insert_or_get_customer($conn, $entry['customer_name'], $entry['customer_mail']);
        $product_id = insert_or_get_product($conn, $entry['product_name'], $entry['product_price']);

        $stmt = $conn->prepare("SELECT id FROM sales WHERE customer_id = ? AND product_id = ? AND sale_date = ?");
        $stmt->bind_param("iis", $customer_id, $product_id, $entry['sale_date']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO sales (customer_id, product_id, sale_date) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $customer_id, $product_id, $entry['sale_date']);
            $stmt->execute();
        }
    }
    
    $query = "SELECT customers.name AS customer, products.name AS product, products.price, sales.sale_date FROM sales
              INNER JOIN customers ON sales.customer_id = customers.id
              INNER JOIN products ON sales.product_id = products.id";
                          
    $result = $conn->query($query);

    return $result->fetch_all(MYSQLI_ASSOC);
}

//This function is used to insert or get customer from the database
//If the customer is already in the database it will return the id of the customer
//If the customer is not in the database it will insert the customer and return the id of the customer
function insert_or_get_customer($conn, $name, $email) {
    $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();

    if ($id) return $id;

    $stmt = $conn->prepare("INSERT INTO customers (name, email) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $email);
    $stmt->execute();
    return $stmt->insert_id;
}

//This function does the same thing as the insert_or_get_customer function but for products
function insert_or_get_product($conn, $name, $price) {
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();

    if ($id) return $id;

    $stmt = $conn->prepare("INSERT INTO products (name, price) VALUES (?, ?)");
    $stmt->bind_param("sd", $name, $price);
    $stmt->execute();
    return $stmt->insert_id;
}

//This function is used to filter the data
function filter_data($conn, $filters) {
    $query = "SELECT customers.name AS customer, products.name AS product, products.price, sales.sale_date FROM sales
              INNER JOIN customers ON sales.customer_id = customers.id
              INNER JOIN products ON sales.product_id = products.id WHERE 1=1";

    if (!empty($filters['customer'])) {
        $query .= " AND customers.id = " . (int)$filters['customer'];
    }
    if (!empty($filters['product'])) {
        $query .= " AND products.id = " . (int)$filters['product'];
    }
    if (!empty($filters['price'])) {
        [$min, $max] = explode('-', $filters['price']);
        $query .= " AND products.price BETWEEN $min AND $max";
    }

    $result = $conn->query($query);

    return $result->fetch_all(MYSQLI_ASSOC);
}

//This function is used to get the data from database to populate the select options
function get_filter_options($conn) {
    $tables = ['customers', 'products'];
    $options = ['customers' => [], 'products' => []];

    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            $query = "SELECT id, name FROM $table";
            $options[$table] = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        }
    }

    return $options;
}
?>