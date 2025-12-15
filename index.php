<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$search = isset($_GET['q']) ? $_GET['q'] : '';
$active_tab = isset($_GET['depo']) ? $_GET['depo'] : 'TÜMÜ';

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (name LIKE ? OR warehouse LIKE ? OR shelf LIKE ? OR quantity LIKE ?)";
    $fill = "%$search%";
    $params = [$fill, $fill, $fill, $fill];
} elseif ($active_tab != 'TÜMÜ') {
    $sql .= " AND warehouse = ?";
    $params[] = $active_tab;
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$warehouses = $pdo->query("SELECT DISTINCT warehouse FROM products ORDER BY warehouse ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PromoStok</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<header>
    <div class="header-top">
        <h3>PromoStok</h3>
        <a href="logout.php" style="color:white;"><i class="fa fa-sign-out-alt"></i></a>
    </div>
    <form action="" method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Ara (Ürün, Raf, Depo)..." value="<?= htmlspecialchars($search) ?>">
    </form>
</header>

<div class="tabs-wrapper">
    <a href="index.php" class="tab-link <?= ($active_tab == 'TÜMÜ' && !$search) ? 'active' : '' ?>">TÜMÜ</a>
    <?php foreach($warehouses as $wh): ?>
        <a href="?depo=<?= urlencode($wh) ?>" class="tab-link <?= ($active_tab == $wh && !$search) ? 'active' : '' ?>"><?= htmlspecialchars($wh) ?></a>
    <?php endforeach; ?>
</div>

<div class="product-grid">
    <?php foreach($products as $p): ?>
    <div class="product-card">
        <?php if($p['image']): ?>
            <img src="uploads/<?= $p['image'] ?>" class="product-img" alt="Ürün">
        <?php else: ?>
            <div style="height:150px; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;">Resim Yok</div>
        <?php endif; ?>
        
        <div class="product-info">
            <div style="display:flex; justify-content:center; gap:5px; margin-bottom:5px;">
                <span class="badge"><?= htmlspecialchars($p['warehouse']) ?></span>
                <?php if(!empty($p['shelf'])): ?>
                    <span class="badge shelf-badge"><?= htmlspecialchars($p['shelf']) ?></span>
                <?php endif; ?>
            </div>
            
            <h4><?= htmlspecialchars($p['name']) ?></h4>
            <div class="qty-display"><?= $p['quantity'] ?> Adet</div>
            
            <button class="btn btn-primary" onclick="openStockModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">İşlem Yap</button>
            <a href="history.php?id=<?= $p['id'] ?>" class="btn btn-outline">Geçmişi Gör</a>
            <form action="process.php" method="POST" onsubmit="return confirm('Bu ürünü ve görselini tamamen silmek istediğine emin misin?');" style="margin-top:5px;">
    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
    <button type="submit" name="delete_product" class="btn btn-danger" style="padding: 5px; font-size: 13px;">Sil</button>
</form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<a href="#" class="fab" onclick="document.getElementById('addModal').classList.add('show')"><i class="fa fa-plus"></i></a>

<div id="addModal" class="modal">
    <div class="modal-content">
        <h3>Yeni Ürün Ekle</h3>
        <form action="process.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                    <input type="file" name="image" id="hiddenImageInput" accept="image/*" style="display:none;" onchange="previewFile()">

<div class="form-group">
    <label style="display:block; margin-bottom:5px; font-weight:500;">Ürün Görseli:</label>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn btn-primary" onclick="openCamera()" style="flex:1;">
            <i class="fa fa-camera"></i> Fotoğraf Çek
        </button>
        <button type="button" class="btn btn-success" onclick="openGallery()" style="flex:1;">
            <i class="fa fa-images"></i> Galeriden Seç
        </button>
    </div>
    <div id="fileNameShow" style="margin-top:5px; font-size:13px; color:#666; font-style:italic;">
        Henüz görsel seçilmedi.
    </div>
</div>
            </div>
            <div class="form-group"><input type="text" name="name" class="form-control" placeholder="Ürün Adı" required></div>
            <div class="form-group"><input type="text" name="shelf" class="form-control" placeholder="Raf Kodu (Örn: A-12)"></div>
            <div class="form-group"><input type="number" name="quantity" class="form-control" placeholder="Başlangıç Adedi" required inputmode="numeric"></div>
            <div class="form-group">
                <select name="warehouse_select" class="form-control" onchange="checkManual(this)">
                    <option value="">Depo Seç...</option>
                    <?php foreach($warehouses as $wh): ?>
                        <option value="<?= $wh ?>"><?= $wh ?></option>
                    <?php endforeach; ?>
                    <option value="other">+ Yeni Depo Ekle</option>
                </select>
                <input type="text" name="manual_warehouse" id="manualWh" class="form-control" placeholder="Yeni Depo Adı" style="display:none; margin-top:5px;">
            </div>
            <button type="submit" name="add_product" class="btn btn-primary">Kaydet</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('addModal').classList.remove('show')" style="margin-top:10px;">İptal</button>
        </form>
    </div>
</div>

<div id="stockModal" class="modal">
    <div class="modal-content">
        <h3 id="stockModalTitle">Stok Güncelle</h3>
        <form action="process.php" method="POST">
            <input type="hidden" name="product_id" id="stockId">
            <div class="form-group">
                <input type="number" name="amount" class="form-control" placeholder="Adet Girin" required inputmode="numeric" style="text-align:center; font-size:18px;">
            </div>
            
            <div class="form-group">
                <input type="text" name="note" class="form-control" placeholder="Açıklama / Not (Örn: İK'ya verildi)" autocomplete="off">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="action" value="remove" class="btn btn-danger"><i class="fa fa-minus"></i> ÇIKAR</button>
                <button type="submit" name="action" value="add" class="btn btn-success"><i class="fa fa-plus"></i> EKLE</button>
            </div>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('stockModal').classList.remove('show')" style="margin-top:10px;">Vazgeç</button>
        </form>
    </div>
</div>

<script>
function checkManual(select) {
    const m = document.getElementById('manualWh');
    if(select.value === 'other') { m.style.display = 'block'; m.required = true; } 
    else { m.style.display = 'none'; m.value = ''; m.required = false; }
}
function openStockModal(id, name) {
    document.getElementById('stockId').value = id;
    document.getElementById('stockModalTitle').innerText = name;
    document.getElementById('stockModal').classList.add('show');
}
function openCamera() {
    const input = document.getElementById('hiddenImageInput');
    // Arka kamerayı zorlamak için environment
    input.setAttribute('capture', 'environment');
    input.click();
}

function openGallery() {
    const input = document.getElementById('hiddenImageInput');
    // Galeri için capture özelliğini kaldırıyoruz
    input.removeAttribute('capture');
    input.click();
}

function previewFile() {
    const input = document.getElementById('hiddenImageInput');
    const display = document.getElementById('fileNameShow');
    if(input.files && input.files[0]) {
        display.innerText = "Seçilen: " + input.files[0].name;
        display.style.color = "green";
    }
}
</script>
</body>
</html>