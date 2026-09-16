<?php
// Helper untuk Generate Password Hash
// Jalankan via browser: localhost/inc/generate_password.php?pass=passwordmu

if (isset($_GET['pass'])) {
    $password = $_GET['pass'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "<h1>Password Hash Generator</h1>";
    echo "<p>Password: <strong>" . htmlspecialchars($password) . "</strong></p>";
    echo "<p>Hash: <code>" . htmlspecialchars($hash) . "</code></p>";
    echo "<p>Salin hash di atas ke INSERT SQL Anda!</p>";
} else {
    echo "<h1>Password Hash Generator</h1>";
    echo "<p>Gunakan format: <code>localhost/inc/generate_password.php?pass=passwordmu</code></p>";
    echo "<p>Contoh: <code>localhost/inc/generate_password.php?pass=siswa123</code></p>";
}
?>
