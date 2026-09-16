<?php
include "../inc_koneksi.php";
session_start();

$success = "";
$error = "";

if (isset($_POST['daftar'])) {
    $nama = bersihkan_input($_POST['nama']);
    $jabatan = bersihkan_input($_POST['jabatan']);
    $kelas = bersihkan_input($_POST['kelas']);
    $jurusan = bersihkan_input($_POST['jurusan']);
    $email = bersihkan_input($_POST['email']);
    $nis_nip = bersihkan_input($_POST['nis_nip']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if NISN/NIP already exists
    $check_sql = "SELECT id FROM users WHERE nis_nip = ?";
    $check_stmt = $koneksi->prepare($check_sql);
    $check_stmt->bind_param("s", $nis_nip);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "NISN/NIP sudah terdaftar!";
    } else {
        // Insert new user
        $insert_sql = "INSERT INTO users (nama, jabatan, kelas, jurusan, email, nis_nip, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $koneksi->prepare($insert_sql);
        $insert_stmt->bind_param("sssssss", $nama, $jabatan, $kelas, $jurusan, $email, $nis_nip, $password);
        
        if ($insert_stmt->execute()) {
            $success = "Akun berhasil dibuat! Silakan login.";
        } else {
            $error = "Gagal membuat akun: " . $koneksi->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daftar Akun - Reading Log</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .container {
            width: 600px;
            background: whitesmoke;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo h1 {
            color: #0f4c81;
            font-size: 35px;
            margin-bottom: 5px;
        }

        .logo p {
            color: #666;
            font-size: 14px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            display: block;
            margin-bottom: 7px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
            font-size: 14px;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
        }

        .btn {
            background: #2563eb;
            color: white;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        .login {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #555;
        }

        .login a {
            color: #2563eb;
            text-decoration: none;
            font-weight: bold;
        }

        .login a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media(max-width:450px) {
            .container {
                width: 90%;
                padding: 25px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="logo">
            <h1>Reading Log</h1>
            <p>Halaman Daftar Akun</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="input-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Masukkan nama lengkap" required>
            </div>

            <div class="input-group">
                <label>Jabatan</label>
                <select name="jabatan" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Guru">Guru</option>
                    <option value="Siswa">Siswa</option>
                    <option value="Super Admin">Super Admin</option>
                </select>
            </div>

            <div class="input-group">
                <label>Kelas</label>
                <input type="text" name="kelas" placeholder="Masukkan kelas" required>
            </div>

            <div class="input-group">
                <label>Jurusan</label>
                <select name="jurusan" required>
                    <option value="">-- Pilih Jurusan --</option>
                    <option value="Kuliner">Kuliner</option>
                    <option value="Perhotelan">Perhotelan</option>
                    <option value="Tata Kecantikan">Tata Kecantikan</option>
                    <option value="Desain dan Produksi Busana">Desain dan Produksi Busana</option>
                    <option value="Teknik Komputer Jaringan">Teknik Komputer Jaringan</option>
                </select>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Masukkan email" required>
            </div>

            <div class="input-group">
                <label>NISN/NIP</label>
                <input type="text" name="nis_nip" placeholder="Masukkan NISN/NIP" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Masukkan password" required>
            </div>

            <button type="submit" name="daftar" class="btn">
                Daftar Akun
            </button>
        </form>
            <div class="login">
                Sudah punya akun?
                <a href="../index.php">Login</a>
            </div>
    </div>

</body>

</html>