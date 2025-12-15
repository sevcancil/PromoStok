<?php
session_start();
require 'db.php';

if(isset($_POST['login'])) {
    $username = trim($_POST['username']); // Boşlukları temizleyelim
    $password = $_POST['password'];

    // ADIM 1: Sadece kullanıcı adına göre veriyi çekiyoruz (Şifreyi SQL'de sormuyoruz)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // ADIM 2: Kullanıcı bulunduysa VE şifre hash ile eşleşiyorsa
    if ($user && password_verify($password, $user['password'])) {
        
        // Güvenlik: Oturum sabitleme saldırılarını önlemek için ID yenile
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username']; // Lazım olabilir diye ekledim
        $_SESSION['role'] = $user['role']; // Admin/Personel ayrımı için lazım olacak
        
        header("Location: index.php");
        exit; // Header sonrası kod çalışmasını durdurmak önemlidir
    } else {
        $error = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Promostok</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2 style="text-align:center; margin-bottom:20px;">PromoStok</h2>
            
            <?php if(isset($error)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="Kullanıcı Adı" required autocomplete="off">
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="Şifre" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary">Giriş Yap</button>
            </form>
        </div>
    </div>
</body>
</html>