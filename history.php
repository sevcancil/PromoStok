<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = $_GET['id'];

// Ürün bilgilerini çek
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) { die("Ürün bulunamadı."); }

// Geçmiş kayıtlarını çek (Kullanıcı adıyla birleştirerek)
$logSql = "SELECT sl.*, u.username 
           FROM stock_logs sl 
           JOIN users u ON sl.user_id = u.id 
           WHERE sl.product_id = ? 
           ORDER BY sl.created_at DESC";
$logStmt = $pdo->prepare($logSql);
$logStmt->execute([$product_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geçmiş: <?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header>
    <div style="display:flex; align-items:center;">
        <a href="index.php" style="color:white; font-size:20px; margin-right:15px;"><i class="fa fa-arrow-left"></i></a>
        <h3>Hareket Geçmişi</h3>
    </div>
</header>

<div style="padding: 15px;">
    <div class="product-card" style="margin-bottom: 20px;">
        <h4 style="margin-top:10px;"><?= htmlspecialchars($product['name']) ?></h4>
        <p style="color:#666; font-size:14px;">Depo: <?= htmlspecialchars($product['warehouse']) ?> / Raf: <?= htmlspecialchars($product['shelf']) ?></p>
        <div class="qty-display">Güncel Stok: <?= $product['quantity'] ?></div>
    </div>

    <?php if(count($logs) > 0): ?>
    <div style="background:white; border-radius:10px; padding:10px; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
        <table class="history-table">
            <thead>
                <tr>
                    <th>Miktar</th>
                    <th>Açıklama / Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                <tr>
                    <td style="width: 80px;">
                        <?php if($log['amount'] > 0): ?>
                            <span class="log-plus">+<?= $log['amount'] ?></span>
                        <?php else: ?>
                            <span class="log-minus"><?= $log['amount'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:500;"><?= htmlspecialchars($log['note']) ?></span>
                        <span class="log-note"><i class="fa fa-user"></i> <?= htmlspecialchars($log['username']) ?></span>
                        <span class="log-date"><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="text-align:center; color:#888; margin-top:20px;">Henüz bir hareket kaydı yok.</p>
    <?php endif; ?>
</div>

</body>
</html>