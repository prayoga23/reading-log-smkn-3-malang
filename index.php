<?php
include "inc_koneksi.php";
session_start();

$role_redirects = [
    "Siswa" => "siswa/dashboard_siswa.php",
    "Guru" => "guru/dashboard_guru.php",
    "Super Admin" => "superadmin/dashboard_superadmin.php",
    // "Daftar" => "register/halaman_daftar_akun.php" Semisal Mau Dipakek Tinggal Hapus Comment //
];

function redirect_berdasarkan_role(string $jabatan, array $role_redirects): void {
    if (isset($role_redirects[$jabatan])) {
        header("Location: " . $role_redirects[$jabatan]);
        exit();
    }
}

if (isset($_SESSION["jabatan"])) {
    redirect_berdasarkan_role($_SESSION["jabatan"], $role_redirects);
}

$nis_nip = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nis_nip = bersihkan_input($_POST["nis_nip"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT * FROM users WHERE nis_nip = ? LIMIT 1";
    $stmt = $koneksi->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $nis_nip);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if ((int) $user["is_active"] !== 1) {
                $error = "Akun Anda belum aktif. Silakan hubungi administrator.";
            } elseif (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["nama"] = $user["nama"];
                $_SESSION["jabatan"] = $user["jabatan"];

                redirect_berdasarkan_role($user["jabatan"], $role_redirects);
            } else {
                $error = "Password yang Anda masukkan salah.";
            }
        } else {
            $error = "Akun tidak ditemukan. Periksa kembali NISN/NIP/ID login Anda.";
        }

        $stmt->close();
    } else {
        $error = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="logo/1. logo smk negeri 3 malang.png">
    <title>Selamat Datang Di Aplikasi Reading Log | SMKN 3 Malang</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #1a1a2e;
            position: relative;
            overflow: hidden;
        }

        /* Collage background: bg 1 on top, bg 2 on bottom */
        .bg-collage {
            position: fixed;
            inset: 0;
            z-index: 0;
            display: flex;
            flex-direction: column;
        }

        .bg-collage-top,
        .bg-collage-bottom {
            flex: 1;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        .bg-collage-top {
            background-image: url("bg1.jpeg");
        }

        .bg-collage-bottom {
            background-image: url("bg2.jpeg");
        }

        /* Dark overlay for readability */
        .bg-collage::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                180deg,
                rgba(0, 0, 0, 0.35) 0%,
                rgba(0, 0, 0, 0.55) 48%,
                rgba(0, 0, 0, 0.35) 100%
            );
            z-index: 1;
        }

        body::before {
            content: "";
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            top: -100px;
            left: -100px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(30px);
            }
        }

        .login-wrapper {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            background: linear-gradient(135deg, #64B5F6 0%, #42A5F5 100%);
            color: white;
            text-align: center;
            padding: 40px 30px;
        }

        .logo-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 14px 28px;
            margin-bottom: 18px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.12);
        }

        .logo-box img {
            height: 64px;
            width: auto;
            object-fit: contain;
            display: block;
        }

        .logo-divider {
            width: 1px;
            height: 52px;
            background: rgba(0,0,0,0.13);
            border-radius: 2px;
        }

        .logo-section h2 {
            font-size: 22px;
            opacity: 0.95;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .logo-section h1 {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .form-section {
            padding: 35px 30px;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            border-left: 4px solid #c33;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        .input-wrapper svg {
            position: absolute;
            left: 12px;
            width: 18px;
            height: 18px;
            color: #999;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: none;
            background: transparent;
            color: #999;
            cursor: pointer;
            padding: 0;
        }

        .password-toggle:hover,
        .password-toggle:focus {
            color: #4e73df;
            outline: none;
        }

        .password-toggle svg {
            position: static;
            width: 18px;
            height: 18px;
            color: currentColor;
        }

        .input-group input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .input-group input:focus {
            border-color: #4e73df;
            background: white;
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }

        .input-group input::placeholder {
            color: #999;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(78, 115, 223, 0.6);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .info-text {
            margin-top: 18px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        @media (max-width: 480px) {
            .login-wrapper {
                max-width: 100%;
            }

            .logo-section h1 {
                font-size: 32px;
            }

            .logo-section h2 {
                font-size: 22px;
            }

            .form-section {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-collage">
        <div class="bg-collage-top"></div>
        <div class="bg-collage-bottom"></div>
    </div>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="logo/1. logo smk negeri 3 malang.png" alt="Logo SMKN 3 Malang">
                    <div class="logo-divider"></div>
                    <img src="logo/2. Universitas Negeri Malang Logo.png" alt="Logo Universitas Negeri Malang">
                </div>
                <h2>Reading Log</h2>
                <h1>SMKN 3 Malang</h1>
            </div>

            <div class="form-section">
                <?php if (!empty($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group">
                        <label for="nis_nip">NIPD / NIP / ID Login</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <input
                                type="text"
                                id="nis_nip"
                                name="nis_nip"
                                placeholder="Masukkan NIPD, NIP, atau ID login"
                                value="<?php echo htmlspecialchars($nis_nip, ENT_QUOTES, "UTF-8"); ?>"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                            <button type="button" class="password-toggle" id="password_toggle" aria-label="Lihat password" aria-pressed="false">
                                <svg id="icon_eye_open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg id="icon_eye_closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.82 21.82 0 0 1 5.06-6.94"></path>
                                    <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.73 21.73 0 0 1-3.17 4.66"></path>
                                    <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                    <path d="M1 1l22 22"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("password_toggle");
        const iconEyeOpen = document.getElementById("icon_eye_open");
        const iconEyeClosed = document.getElementById("icon_eye_closed");

        passwordToggle.addEventListener("click", () => {
            const isPasswordHidden = passwordInput.type === "password";

            passwordInput.type = isPasswordHidden ? "text" : "password";
            passwordToggle.setAttribute("aria-label", isPasswordHidden ? "Sembunyikan password" : "Lihat password");
            passwordToggle.setAttribute("aria-pressed", isPasswordHidden ? "true" : "false");
            iconEyeOpen.style.display = isPasswordHidden ? "none" : "block";
            iconEyeClosed.style.display = isPasswordHidden ? "block" : "none";
        });
    </script>
</body>
 </html>
