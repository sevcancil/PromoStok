<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. ÜRÜN EKLEME
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $qty = (int)$_POST['quantity'];
    $shelf = mb_strtoupper(trim($_POST['shelf']), 'UTF-8');
    
    // Depo belirleme
    $warehouse_input = trim($_POST['manual_warehouse']);
    $warehouse_select = $_POST['warehouse_select'];
    $final_warehouse = !empty($warehouse_input) ? $warehouse_input : $warehouse_select;
    $final_warehouse = mb_strtoupper($final_warehouse, 'UTF-8');

    // Resim Yükleme
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir);
        $fileName = time() . '_' . $_FILES['image']['name'];
        if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, warehouse, shelf, quantity, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $final_warehouse, $shelf, $qty, $imagePath]);
    
    // İlk kayıt logu da atabiliriz (Opsiyonel)
    $lastId = $pdo->lastInsertId();
    $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, user_id, amount, note) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$lastId, $_SESSION['user_id'], $qty, "Yeni ürün girişi"]);

    header("Location: index.php");
    exit;
}

// 2. STOK GÜNCELLEME VE LOGLAMA
if (isset($_POST['product_id']) && isset($_POST['amount']) && isset($_POST['action'])) {
    $id = $_POST['product_id'];
    $amount = (int)$_POST['amount'];
    $action = $_POST['action'];
    $note = isset($_POST['note']) ? trim($_POST['note']) : ''; // Notu al

    if ($amount > 0) {
        $change = ($action === 'remove') ? -1 * $amount : $amount;
        
        // Stoğu Güncelle
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->execute([$change, $id]);

        // Log Kaydı Oluştur
        // Not boşsa otomatik bir şeyler yazalım
        if(empty($note)) {
            $note = ($action === 'add') ? "Stok ekleme" : "Stok düşümü";
        }

        $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, user_id, amount, note) VALUES (?, ?, ?, ?)");
        $logStmt->execute([$id, $_SESSION['user_id'], $change, $note]);
    }
    
    header("Location: index.php");
    exit;
}
?>