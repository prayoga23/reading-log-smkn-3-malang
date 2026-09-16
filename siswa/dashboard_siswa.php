<?php
session_start();
include __DIR__ . '/../inc_koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Siswa') {
    header('Location: ../index.php');
    exit;
}

// Fungsi escape HTML
function esc($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Ambil data siswa dari database
$user_id = $_SESSION['user_id'];
$stmt = $koneksi->prepare('SELECT id, nama, email, nis_nip, kelas, jurusan FROM users WHERE id = ? AND jabatan = ?');
$jabatan = 'Siswa';
$stmt->bind_param('is', $user_id, $jabatan);
$stmt->execute();
$result = $stmt->get_result();
$siswa = $result->fetch_assoc();
$stmt->close();

// Jika data siswa tidak ditemukan, redirect ke login
if (!$siswa) {
    header('Location: ../index.php');
    exit;
}

// Ambil data resume literasi siswa
$resume_stmt = $koneksi->prepare('SELECT COUNT(*) as total_resume FROM resume_literasi WHERE user_id = ?');
$resume_stmt->bind_param('i', $user_id);
$resume_stmt->execute();
$resume_result = $resume_stmt->get_result();
$resume_data = $resume_result->fetch_assoc();
$total_resume = $resume_data['total_resume'] ?? 0;
$resume_stmt->close();

// Ambil data wali kelas berdasarkan kelas siswa
$wali_kelas = '-';
if (!empty($siswa['kelas'])) {
    $wali_stmt = $koneksi->prepare('SELECT wali_kelas FROM kelas WHERE nama_kelas = ?');
    $wali_stmt->bind_param('s', $siswa['kelas']);
    $wali_stmt->execute();
    $wali_result = $wali_stmt->get_result();
    if ($wali_row = $wali_result->fetch_assoc()) {
        $wali_kelas = $wali_row['wali_kelas'];
    }
    $wali_stmt->close();
}

// Ambil data resume literasi siswa untuk ditampilkan
$resume_list_stmt = $koneksi->prepare('SELECT r.id, r.tanggal, r.kegiatan, r.judul, r.nama_pemateri_penerbit, r.resume_text, r.dokumentasi_link, r.created_at, pg.feedback, pg.created_at as feedback_date, u.nama as guru_nama FROM resume_literasi r LEFT JOIN penilaian_guru pg ON r.id = pg.resume_id LEFT JOIN users u ON pg.guru_id = u.id WHERE r.user_id = ? ORDER BY r.tanggal DESC');
$resume_list_stmt->bind_param('i', $user_id);
$resume_list_stmt->execute();
$resume_list_result = $resume_list_stmt->get_result();
$resume_list = [];
while ($row = $resume_list_result->fetch_assoc()) {
    $resume_list[] = $row;
}
$resume_list_stmt->close();

// Ambil data untuk grafik (per bulan tahun ini)
$current_year = date('Y');
$chart_data_monthly = [];
$chart_labels_monthly = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// Query untuk data per bulan
$chart_stmt = $koneksi->prepare('SELECT MONTH(tanggal) as bulan, COUNT(*) as jumlah FROM resume_literasi WHERE user_id = ? AND YEAR(tanggal) = ? GROUP BY MONTH(tanggal) ORDER BY bulan');
$chart_stmt->bind_param('ii', $user_id, $current_year);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();

// Inisialisasi array dengan 0 untuk semua bulan
$monthly_data = array_fill(1, 12, 0);

while ($row = $chart_result->fetch_assoc()) {
    $monthly_data[$row['bulan']] = $row['jumlah'];
}

// Konversi ke array untuk JavaScript
$chart_data_monthly = array_values($monthly_data);
$chart_stmt->close();

// Data untuk grafik mingguan (7 hari terakhir)
$chart_labels_weekly = [];
$chart_data_weekly = array_fill(0, 7, 0);

// Generate label untuk 7 hari terakhir
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    $chart_labels_weekly[] = $day_name;
}

// Query untuk data per hari (7 hari terakhir)
$weekly_stmt = $koneksi->prepare('SELECT DATE(tanggal) as tanggal_hari, COUNT(*) as jumlah FROM resume_literasi WHERE user_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(tanggal) ORDER BY tanggal_hari');
$weekly_stmt->bind_param('i', $user_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

// Mapping data ke array 7 hari terakhir
$weekly_data_map = [];
while ($row = $weekly_result->fetch_assoc()) {
    $weekly_data_map[$row['tanggal_hari']] = $row['jumlah'];
}

// Isi data weekly sesuai dengan 7 hari terakhir
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    if (isset($weekly_data_map[$date])) {
        $chart_data_weekly[6 - $i] = $weekly_data_map[$date];
    }
}
$weekly_stmt->close();

// Data untuk grafik tahunan (5 tahun terakhir)
$chart_labels_yearly = [];
$chart_data_yearly = [];

// Generate label untuk 5 tahun terakhir
for ($i = 4; $i >= 0; $i--) {
    $year = date('Y') - $i;
    $chart_labels_yearly[] = $year;
}

// Query untuk data per tahun (5 tahun terakhir)
$yearly_stmt = $koneksi->prepare('SELECT YEAR(tanggal) as tahun, COUNT(*) as jumlah FROM resume_literasi WHERE user_id = ? AND YEAR(tanggal) >= ? GROUP BY YEAR(tanggal) ORDER BY tahun');
$start_year = date('Y') - 4;
$yearly_stmt->bind_param('ii', $user_id, $start_year);
$yearly_stmt->execute();
$yearly_result = $yearly_stmt->get_result();

// Mapping data ke array tahunan
$yearly_data_map = [];
while ($row = $yearly_result->fetch_assoc()) {
    $yearly_data_map[$row['tahun']] = $row['jumlah'];
}

// Isi data yearly sesuai dengan 5 tahun terakhir
foreach ($chart_labels_yearly as $year) {
    $chart_data_yearly[] = $yearly_data_map[$year] ?? 0;
}
$yearly_stmt->close();

// Handle POST request untuk simpan resume
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_resume') {
    $nama = bersihkan_input($_POST['nama'] ?? '');
    $tanggal = bersihkan_input($_POST['tanggal'] ?? '');
    $kelas = bersihkan_input($_POST['kelas'] ?? '');
    $kegiatan = bersihkan_input($_POST['kegiatan'] ?? '');
    $judul = bersihkan_input($_POST['judul'] ?? '');
    $nama_pemateri_penerbit = bersihkan_input($_POST['nama_pemateri_penerbit'] ?? '');
    $resume_text = bersihkan_input($_POST['resumeText'] ?? '');
    $resume_html = isset($_POST['resumeHTML']) ? $_POST['resumeHTML'] : ''; // Jangan escape HTML content
    $dokumentasi_link = bersihkan_input($_POST['dokumentasi_link'] ?? '');

    // Validasi semua field harus diisi
    if (empty($nama) || empty($tanggal) || empty($kelas) || empty($kegiatan) || empty($judul) || empty($resume_text)) {
        $response['message'] = 'Semua kolom harus diisi!';
    } else {
        // Hitung jumlah kata
        $word_count = count(array_filter(explode(' ', trim($resume_text))));
        
        if ($word_count < 100) {
            $response['message'] = 'Resume minimal 100 kata!';
        } else {
            // Buat tabel jika belum ada
            $koneksi->query(
                "CREATE TABLE IF NOT EXISTS resume_literasi (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id INT UNSIGNED NOT NULL,
                    nama VARCHAR(150) NOT NULL,
                    tanggal DATETIME NOT NULL,
                    kelas VARCHAR(100) NOT NULL,
                    kegiatan VARCHAR(100) NOT NULL,
                    judul VARCHAR(255) NOT NULL,
                    nama_pemateri_penerbit VARCHAR(255) DEFAULT NULL,
                    resume_text LONGTEXT NOT NULL,
                    resume_html LONGTEXT,
                    dokumentasi_link VARCHAR(500) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
            
            // Tambah kolom dokumentasi_link jika belum ada
            $koneksi->query("ALTER TABLE resume_literasi ADD COLUMN IF NOT EXISTS dokumentasi_link VARCHAR(500) DEFAULT NULL AFTER resume_html");
            
            // Tambah kolom nama_pemateri_penerbit jika belum ada
            $koneksi->query("ALTER TABLE resume_literasi ADD COLUMN IF NOT EXISTS nama_pemateri_penerbit VARCHAR(255) DEFAULT NULL AFTER judul");
            
            // Buat tabel penilaian_guru jika belum ada
            $koneksi->query(
                "CREATE TABLE IF NOT EXISTS penilaian_guru (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    resume_id INT UNSIGNED NOT NULL,
                    guru_id INT UNSIGNED NOT NULL,
                    score INT DEFAULT NULL,
                    feedback LONGTEXT NOT NULL,
                    saran LONGTEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uniq_resume_guru (resume_id, guru_id),
                    FOREIGN KEY (resume_id) REFERENCES resume_literasi(id) ON DELETE CASCADE,
                    FOREIGN KEY (guru_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
            
            // Tambah kolom score jika belum ada (untuk tabel yang sudah ada)
            $koneksi->query("ALTER TABLE penilaian_guru ADD COLUMN IF NOT EXISTS score INT DEFAULT NULL AFTER guru_id");

            // Insert data resume
            $stmt = $koneksi->prepare('INSERT INTO resume_literasi (user_id, nama, tanggal, kelas, kegiatan, judul, nama_pemateri_penerbit, resume_text, resume_html, dokumentasi_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssssssss', $user_id, $nama, $tanggal, $kelas, $kegiatan, $judul, $nama_pemateri_penerbit, $resume_text, $resume_html, $dokumentasi_link);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Resume berhasil disimpan!';
            } else {
                $response['message'] = 'Gagal menyimpan resume: ' . $koneksi->error;
            }
            $stmt->close();
        }
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handler update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json');
    $prof_response = ['success' => false, 'message' => ''];

    $new_nama     = bersihkan_input($_POST['new_nama'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($new_nama)) {
        $prof_response['message'] = 'Nama tidak boleh kosong.';
        echo json_encode($prof_response); exit;
    }

    if ($new_password !== '' && $new_password !== $confirm_pass) {
        $prof_response['message'] = 'Konfirmasi password tidak cocok.';
        echo json_encode($prof_response); exit;
    }

    if ($new_password !== '' && strlen($new_password) < 6) {
        $prof_response['message'] = 'Password minimal 6 karakter.';
        echo json_encode($prof_response); exit;
    }

    if ($new_password !== '') {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare('UPDATE users SET nama = ?, password = ? WHERE id = ?');
        $stmt->bind_param('ssi', $new_nama, $hashed, $user_id);
    } else {
        $stmt = $koneksi->prepare('UPDATE users SET nama = ? WHERE id = ?');
        $stmt->bind_param('si', $new_nama, $user_id);
    }

    if ($stmt && $stmt->execute()) {
        $_SESSION['nama'] = $new_nama;
        $prof_response['success'] = true;
        $prof_response['message'] = 'Profil berhasil diperbarui.';
        $prof_response['new_nama'] = $new_nama;
    } else {
        $prof_response['message'] = 'Gagal memperbarui profil.';
    }
    if ($stmt) $stmt->close();

    echo json_encode($prof_response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="../logo/1. logo smk negeri 3 malang.png">
    <title>Dashboard Siswa Reading Log | SMKN 3 Malang</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', sans-serif;
        }

        body {
            background: #eef4ff;
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 24px 28px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            border-radius: 20px 20px 0 0;
        }

        .modal-header h2 {
            color: #2563eb;
            font-size: 22px;
        }

        .modal-close-btn {
            background: #e5e7eb;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 24px;
            color: #475569;
            transition: all 0.2s;
        }

        .modal-close-btn:hover {
            background: #dc2626;
            color: white;
        }

        .modal-body {
            padding: 28px;
        }

        .modal-meta {
            margin-bottom: 24px;
            padding: 16px;
            background: #f1f5f9;
            border-radius: 12px;
        }

        .modal-meta-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 8px;
        }

        .modal-meta-top p {
            margin: 0;
            flex: 1;
        }

        .modal-score-badge {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #dbeafe;
            border: 1px solid #5a87f0ff;
            border-radius: 999px;
            color: #1d4ed8;
            font-weight: 700;
            white-space: nowrap;
        }

        .modal-meta p {
            margin: 8px 0;
            color: #475569;
        }

        .modal-meta strong {
            color: #2563eb;
        }

        .modal-resume-content {
            line-height: 1.8;
            color: #1f2937;
            font-size: 16px;
        }

        .modal-resume-content p {
            margin-bottom: 16px;
        }

        .modal-resume-content h1,
        .modal-resume-content h2 {
            color: #2563eb;
            margin-top: 20px;
            margin-bottom: 12px;
        }

        .modal-resume-content ul,
        .modal-resume-content ol {
            margin-left: 24px;
            margin-bottom: 16px;
        }

        .btn-lihat {
            background: #2563eb;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-lihat:hover {
            background: #1d4ed8;
        }

        <?php include __DIR__ . '/../includes/sidebar_css.php'; ?>

        /* MAIN */
        .main {
            margin-left: 270px;
            padding: 30px;
        }

        /* HEADER */
        .header {
            background: white;
            padding: 20px;
            border-radius: 18px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 8px;
        }

        .header h1 {
            color: #0f4c81;
        }

        .logout-link {
            display: inline-block;
            text-decoration: none;
            background: #dc2626;
            color: white;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: bold;
        }

        .logout-link:hover {
            background: #b91c1c;
        }

        /* CONTENT */
        .content {
            display: none;
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .active {
            display: block;
        }

        /* FORM */
        .form-group {
            margin-bottom: 20px;
        }

        /* EDITOR STYLING */
        .editor-container {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #fafbfc;
            transition: border-color 0.3s;
        }

        .editor-container:focus-within {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .editor-toolbar {
            background: #f3f4f6;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .toolbar-group {
            display: flex;
            gap: 4px;
            align-items: center;
        }

        .toolbar-divider {
            width: 1px;
            height: 24px;
            background: #d1d5db;
            margin: 0 4px;
        }

        .editor-btn {
            padding: 8px 12px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
        }

        .editor-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .editor-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .editor-content {
            min-height: 300px;
            padding: 16px;
            font-size: 16px;
            line-height: 1.6;
            color: #1f2937;
            outline: none;
            background: white;
            word-wrap: break-word;
        }

        .editor-content p {
            margin: 0 0 12px 0;
            min-height: 1.5em;
        }

        .editor-content ol,
        .editor-content ul {
            margin: 12px 0;
            padding-left: 24px;
        }

        .editor-content li {
            margin: 6px 0;
        }

        .editor-content h1 {
            font-size: 28px;
            font-weight: bold;
            margin: 16px 0 12px 0;
        }

        .editor-content h2 {
            font-size: 24px;
            font-weight: bold;
            margin: 14px 0 10px 0;
        }

        .editor-footer {
            background: #f9fafb;
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #6b7280;
        }

        .word-counter {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .counter-item {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .counter-item strong {
            color: #2563eb;
            font-size: 14px;
        }

        .word-requirement {
            padding: 8px 12px;
            border-radius: 6px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 600;
            font-size: 12px;
        }

        .word-requirement.completed {
            background: #dcfce7;
            color: #166534;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 5px rgba(37, 99, 235, 0.3);
        }

        textarea {
            resize: none;
        }

        /* BUTTON */
        .btn {
            background: #2563eb;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #1d4ed8;
        }

        /* ALERT */
        .alert {
            margin-top: 15px;
            padding: 12px;
            border-radius: 10px;
            display: none;
            font-weight: bold;
        }

        .success {
            background: #d1fae5;
            color: #065f46;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* TRACK RECORD */
        .profile-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: #f8fbff;
            padding: 20px;
            border-radius: 15px;
            border-left: 6px solid #2563eb;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        .card h3 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
        }

        /* RESPONSIVE */
        @media(max-width:768px) {

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .menu {
                display: flex;
                gap: 10px;
            }

            .menu button {
                font-size: 14px;
            }

            .header-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .modal-meta-top {
                flex-direction: column;
                align-items: stretch;
            }

            .modal-score-badge {
                width: fit-content;
            }

        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar">

        <?php include __DIR__ . '/../includes/sidebar_logo.php'; ?>

        <h2>Reading Log SMKN 3 Malang</h2>

        <div class="sidebar-menu-area">
            <div class="menu">
                <button onclick="showPage('resume')">
                    Input Resume
                </button>

                <button onclick="showPage('track')">
                    Track Record
                </button>
            </div>
        </div>

        <button class="sidebar-profile-btn" onclick="openProfileModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
        </button>
    </div>

    <?php
    $profile_nama = esc($siswa['nama'] ?? '');
    include __DIR__ . '/../includes/profile_modal.php';
    ?>

    <!-- MAIN -->
    <div class="main">

        <div class="header">
            <div class="header-top">
                <h1>Dashboard Siswa</h1>
                <a class="logout-link" href="../logout.php">Log Out</a>
            </div>
            <p>
                Selamat datang, <strong><?php echo esc($siswa['nama'] ?? '-'); ?></strong> &mdash;
                <?php echo esc($siswa['kelas'] ?? '-'); ?> &bull;
                <?php echo esc($siswa['jurusan'] ?? '-'); ?>
            </p>
        </div>

        <!-- HALAMAN INPUT RESUME -->
        <div id="resume" class="content active">

            <h2 style="margin-bottom:25px;color:#2563eb;">
                Input Resume Literasi
            </h2>

            <form id="resumeForm" onsubmit="submitResume(event)">

                <div class="form-group">
                    <label>Nama Siswa</label>
                    <input type="text" id="nama" required readonly style="background: #f0f0f0; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>NIPD</label>
                    <input type="text" id="nis_nip" required readonly style="background: #f0f0f0; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="email" required readonly style="background: #f0f0f0; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Tanggal / Jam (Timestamp)</label>
                    <input type="datetime-local" id="tanggal" required>
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" id="kelas" required readonly style="background: #f0f0f0; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Jenis Kegiatan</label>
                    <select id="kegiatan" required>
                        <option value="">-- Pilih Jenis Kegiatan --</option>
                        <option>Membaca Buku</option>
                        <option>Membaca Artikel</option>
                        <option>Membaca Novel</option>
                        <option>Membaca Berita</option>
                        <option>Menyimak Materi</option>
                        <option>Seminar</option>
                        <option>Diskusi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tema Kegiatan / Judul Bacaan</label>
                    <input type="text" id="judul" placeholder="Masukkan tema kegiatan / judul bacaan" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Pemateri / Nama Penerbit</label>
                    <input type="text" id="nama_pemateri_penerbit" placeholder="Masukkan nama pemateri / penerbit">
                </div>
                
                <div class="form-group">
                    <label>📎 Link Dokumentasi (Opsional - Google Drive, dll)</label>
                    <input type="url" id="dokumentasi_link" placeholder="https://drive.google.com/...">
                </div>

                <div class="form-group">
                    <label>Hasil Resume</label>
                    <div class="editor-container">
                        <div class="editor-toolbar">
                            <div class="toolbar-group">
                                <button type="button" class="editor-btn" onclick="execCmd('formatBlock','<h2>')" title="Heading">H2</button>
                                <button type="button" class="editor-btn" onclick="execCmd('bold')" title="Bold"><strong>B</strong></button>
                                <button type="button" class="editor-btn" onclick="execCmd('italic')" title="Italic"><em>I</em></button>
                                <button type="button" class="editor-btn" onclick="execCmd('underline')" title="Underline"><u>U</u></button>
                            </div>
                            <div class="toolbar-divider"></div>
                            <div class="toolbar-group">
                                <button type="button" class="editor-btn" onclick="execCmd('insertUnorderedList')" title="Bullet List">• List</button>
                                <button type="button" class="editor-btn" onclick="execCmd('insertOrderedList')" title="Numbered List">1. List</button>
                            </div>
                            <div class="toolbar-divider"></div>
                            <div class="toolbar-group">
                                <button type="button" class="editor-btn" onclick="execCmd('removeFormat')" title="Clear Format">Clear</button>
                            </div>
                        </div>
                        <div id="resumeEditor" class="editor-content" contenteditable="true" placeholder="Mulai ketik resume Anda di sini... Minimal 100 kata" onkeyup="updateWordCount()"></div>
                        <div class="editor-footer">
                            <div class="word-counter">
                                <div class="counter-item">
                                    <span>Kata:</span>
                                    <strong id="wordCount">0</strong>
                                </div>
                                <div class="counter-item">
                                    <span>Karakter:</span>
                                    <strong id="charCount">0</strong>
                                </div>
                            </div>
                            <div class="word-requirement" id="wordRequirement">
                                ⚠️ Minimal 100 kata diperlukan
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn">
                    Simpan Resume
                </button>

                <div id="successAlert" class="alert success">
                    Resume berhasil disimpan!
                </div>

                <div id="errorAlert" class="alert error">
                    <span id="errorMessage"></span>
                </div>

            </form>

        </div>

        <!-- HALAMAN TRACK RECORD -->
        <div id="track" class="content">

            <h2 style="margin-bottom:25px;color:#2563eb;">
                Track Record Literasi Siswa
            </h2>

            <div class="profile-box">

                <div class="card">
                    <h3>Nama</h3>
                    <p><?php echo esc($siswa['nama'] ?? '-'); ?></p>
                </div>

                <div class="card">
                    <h3>NIPD</h3>
                    <p><?php echo esc($siswa['nis_nip'] ?? '-'); ?></p>
                </div>

                <div class="card">
                    <h3>Kelas</h3>
                    <p><?php echo esc($siswa['kelas'] ?? '-'); ?></p>
                </div>

                <div class="card">
                    <h3>Jurusan</h3>
                    <p><?php echo esc($siswa['jurusan'] ?? '-'); ?></p>
                </div>

                <div class="card">
                    <h3>Wali Kelas</h3>
                    <p><?php echo esc($wali_kelas); ?></p>
                </div>

                <div class="card">
                    <h3>Total Bacaan</h3>
                    <p style="font-size:30px;font-weight:bold;color:#2563eb;">
                        <?php echo $total_resume; ?>
                    </p>
                </div>
                
                <?php 
                // Hitung total resume yang sudah diberi feedback
                $count_feedback = 0;
                foreach ($resume_list as $resume) {
                    if (!empty($resume['feedback'])) {
                        $count_feedback++;
                    }
                }
                ?>
                
                <div class="card">
                    <h3>Sudah Diberi Feedback</h3>
                    <p style="font-size:30px;font-weight:bold;color:#059669;">
                        <?php echo $count_feedback; ?>
                    </p>
                    <small style="color: #6b7280;">
                        dari <?php echo $total_resume; ?> resume literasi
                    </small>
                </div>

            </div>

            <!-- FILTER -->
            <div class="form-group" style="margin-bottom:30px;">
                <label>Laporan Literasi Sekolah Tahun 2026</label>

                <select id="filterGrafik">
                    <option value="minggu">Data Mingguan</option>
                    <option value="bulan">Data Bulanan</option>
                    <option value="tahun">Data Tahunan</option>
                    </option>
                </select>
            </div>

            <!-- GRAFIK -->
            <div class="chart-container">
                <canvas id="literasiChart"></canvas>
            </div>

            <!-- DAFTAR RESUME YANG SUDAH DIUPLOAD -->
            <div class="resume-list-section" style="margin-top: 40px;">
                <h3 style="margin-bottom: 20px; color: #2563eb;">Daftar Resume Literasi</h3>
                
                <?php if (empty($resume_list)): ?>
                    <div class="empty-state" style="text-align: center; padding: 30px; background: #f8fafc; border-radius: 12px; color: #64748b;">
                        <p style="font-size: 16px;">Belum ada resume literasi yang diupload.</p>
                        <p style="font-size: 14px; margin-top: 8px;">Silakan buat resume pertama Anda di halaman "Input Resume".</p>
                    </div>
                <?php else: ?>
                    <div class="resume-table-wrapper" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">No</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Tanggal</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Kegiatan</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Tema / Judul</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Pemateri / Penerbit</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Ringkasan</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Status Feedback</th>
                                    <th style="padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Dibuat</th>
                                    <th style="padding: 14px 16px; text-align: center; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resume_list as $index => $resume): ?>
                                    <?php 
                                    $has_feedback = !empty($resume['feedback']);
                                    $status_class = $has_feedback ? 'status-sudah' : 'status-belum';
                                    $status_text = $has_feedback ? 'Sudah Di Beri Feedback' : 'Belum Di Beri Feedback';
                                    $status_color = $has_feedback ? '#059669' : '#dc2626';
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                        <td style="padding: 14px 16px; color: #475569;"><?php echo $index + 1; ?></td>
                                        <td style="padding: 14px 16px; color: #475569;"><?php echo date('d/m/Y', strtotime($resume['tanggal'])); ?></td>
                                        <td style="padding: 14px 16px; color: #475569;"><?php echo esc($resume['kegiatan']); ?></td>
                                        <td style="padding: 14px 16px; color: #475569; font-weight: 500;"><?php echo esc($resume['judul']); ?></td>
                                        <td style="padding: 14px 16px; color: #475569;"><?php echo esc($resume['nama_pemateri_penerbit'] ?? '-'); ?></td>
                                        <td style="padding: 14px 16px; color: #475569;">
                                            <?php 
                                            $text = strip_tags($resume['resume_text']);
                                            echo strlen($text) > 100 ? substr($text, 0, 100) . '...' : $text;
                                            ?>
                                        </td>
                                        <td style="padding: 14px 16px; color: <?php echo $status_color; ?>; font-weight: 600;">
                                            <?php echo $status_text; ?>
                                            <?php if ($has_feedback): ?>
                                                <br>
                                                <small style="font-size: 12px; font-weight: normal; color: #6b7280;">
                                                    oleh: <?php echo esc($resume['guru_nama'] ?? 'Guru'); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 14px 16px; color: #64748b; font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($resume['created_at'])); ?></td>
                                        <td style="padding: 14px 16px; text-align: center;">
                                            <button class="btn-lihat" onclick="openResumeModal(<?php echo $index; ?>)">
                                                Lihat Resume
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- MODAL LIHAT RESUME -->
    <div id="resumeModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Detail Resume Literasi</h2>
                <button class="modal-close-btn" onclick="closeResumeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-meta">
                    <div class="modal-meta-top">
                        <p><strong>Tanggal:</strong> <span id="modalTanggal"></span></p>
                    </div>
                    <p><strong>Kegiatan:</strong> <span id="modalKegiatan"></span></p>
                    <p><strong>Tema / Judul:</strong> <span id="modalJudul"></span></p>
                    <p><strong>Pemateri / Penerbit:</strong> <span id="modalPemateriPenerbit"></span></p>
                    <p id="modalDokumentasiWrapper" style="display: none;"><strong>Link Dokumentasi:</strong> <a id="modalDokumentasi" href="#" target="_blank"></a></p>
                    <p><strong>Dibuat:</strong> <span id="modalDibuat"></span></p>
                </div>
                <div id="modalResumeContent" class="modal-resume-content"></div>
                
                <!-- SECTION FEEDBACK GURU -->
                <div id="feedbackSection" style="display: none; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
                    <h3 style="color: #2563eb; margin-bottom: 15px;">📝 Feedback dari Guru</h3>
                    
                    <div class="modal-meta" style="background: #f0f9ff; border-left: 4px solid #2563eb;">
                        <p><strong>Guru Penilai:</strong> <span id="modalGuruNama"></span></p>
                        <p><strong>Tanggal Penilaian:</strong> <span id="modalFeedbackDate"></span></p>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <h4 style="color: #059669; margin-bottom: 10px;">✅ Feedback:</h4>
                        <div id="modalFeedback" style="padding: 15px; background: #f0fdf4; border-radius: 10px; border-left: 4px solid #059669; line-height: 1.6;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        /* Load data siswa saat halaman dimuat */
        window.addEventListener('DOMContentLoaded', function() {
            loadSiswaData();
            setTimestampDefault();
            setupEditor();
        });

        /* Setup Editor */
        function setupEditor() {
            const editor = document.getElementById('resumeEditor');
            
            // Blok copy paste
            editor.addEventListener('paste', function(e) {
                e.preventDefault();
                alert("Copy paste tidak diperbolehkan! Silakan mengetik manual.");
            });

            // Update word count saat fokus hilang
            editor.addEventListener('blur', updateWordCount);
            
            // Update word count setiap kali ketik
            editor.addEventListener('input', updateWordCount);
        }

        /* Execute Editor Command */
        function execCmd(command, value = null) {
            document.execCommand(command, false, value);
            document.getElementById('resumeEditor').focus();
        }

        /* Update Word Counter */
        function updateWordCount() {
            const editor = document.getElementById('resumeEditor');
            const text = editor.innerText || editor.textContent || '';
            
            // Hitung kata
            const words = text.trim().split(/\s+/).filter(word => word.length > 0);
            const wordCount = words.length;
            
            // Hitung karakter
            const charCount = text.length;
            
            // Update display
            document.getElementById('wordCount').textContent = wordCount;
            document.getElementById('charCount').textContent = charCount;
            
            // Update requirement status
            const requirement = document.getElementById('wordRequirement');
            if (wordCount >= 100) {
                requirement.classList.add('completed');
                requirement.textContent = '✅ Target 100 kata tercapai!';
            } else {
                requirement.classList.remove('completed');
                const remaining = 100 - wordCount;
                requirement.textContent = '⚠️ Masih butuh ' + remaining + ' kata lagi';
            }
        }

        /* Ambil data siswa dari PHP dan tampilkan di form */
        function loadSiswaData() {
            <?php if ($siswa): ?>
                document.getElementById('nama').value = '<?php echo esc($siswa['nama'] ?? ''); ?>';
                document.getElementById('nis_nip').value = '<?php echo esc($siswa['nis_nip'] ?? ''); ?>';
                document.getElementById('email').value = '<?php echo esc($siswa['email'] ?? ''); ?>';
                document.getElementById('kelas').value = '<?php echo esc($siswa['kelas'] ?? ''); ?>';
            <?php endif; ?>
        }

        /* Set tanggal/jam otomatis sesuai timestamp saat ini */
        function setTimestampDefault() {
            const now = new Date();
            // Format untuk datetime-local: YYYY-MM-DDTHH:mm
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            const timestamp = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('tanggal').value = timestamp;
        }

        /* NAVIGASI */
        function showPage(pageId) {
            const pages = document.querySelectorAll('.content');
            pages.forEach(page => {
                page.classList.remove('active');
            });
            document.getElementById(pageId).classList.add('active');
        }

        /* Submit Resume dengan validasi */
        function submitResume(event) {
            event.preventDefault();

            // Validasi semua field harus diisi
            const nama = document.getElementById('nama').value.trim();
            const tanggal = document.getElementById('tanggal').value.trim();
            const kelas = document.getElementById('kelas').value.trim();
            const kegiatan = document.getElementById('kegiatan').value.trim();
            const judul = document.getElementById('judul').value.trim();
            const nama_pemateri_penerbit = document.getElementById('nama_pemateri_penerbit').value.trim();
            const dokumentasi_link = document.getElementById('dokumentasi_link').value.trim();
            const editor = document.getElementById('resumeEditor');
            const resumeText = (editor.innerText || editor.textContent || '').trim();

            const success = document.getElementById('successAlert');
            const error = document.getElementById('errorAlert');
            const errorMessage = document.getElementById('errorMessage');

            success.style.display = 'none';
            error.style.display = 'none';

            // Cek field kosong
            if (!nama || !tanggal || !kelas || !kegiatan || !judul || !resumeText) {
                errorMessage.textContent = 'Semua kolom harus diisi!';
                error.style.display = 'block';
                return;
            }

            // Hitung jumlah kata
            const wordCount = resumeText.split(/\s+/).filter(word => word.length > 0).length;

            if (wordCount < 100) {
                errorMessage.textContent = 'Resume minimal 100 kata! Kata saat ini: ' + wordCount;
                error.style.display = 'block';
                return;
            }

            // Ambil HTML content dari editor
            const resumeHTML = editor.innerHTML;

            // Kirim data ke backend
            const formData = new FormData();
            formData.append('action', 'save_resume');
            formData.append('nama', nama);
            formData.append('tanggal', tanggal);
            formData.append('kelas', kelas);
            formData.append('kegiatan', kegiatan);
            formData.append('judul', judul);
            formData.append('nama_pemateri_penerbit', nama_pemateri_penerbit);
            formData.append('resumeText', resumeText);
            formData.append('resumeHTML', resumeHTML);
            formData.append('dokumentasi_link', dokumentasi_link);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    success.style.display = 'block';
                    document.getElementById('resumeForm').reset();
                    document.getElementById('resumeEditor').innerHTML = '';
                    updateWordCount();
                    setTimestampDefault();
                    loadSiswaData();
                    // Reset select ke placeholder
                    document.getElementById('kegiatan').value = '';
                    document.getElementById('judul').value = '';
                } else {
                    errorMessage.textContent = data.message;
                    error.style.display = 'block';
                }
            })
            .catch(err => {
                errorMessage.textContent = 'Terjadi kesalahan: ' + err;
                error.style.display = 'block';
            });
        }

        // Data resume dari PHP
        const resumeList = <?php echo json_encode($resume_list); ?>;

        /* BUKA MODAL RESUME */
        function openResumeModal(index) {
            const resume = resumeList[index];
            
            document.getElementById('modalTitle').textContent = 'Detail Resume: ' + resume.judul;
            document.getElementById('modalTanggal').textContent = new Date(resume.tanggal).toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('modalKegiatan').textContent = resume.kegiatan;
            document.getElementById('modalJudul').textContent = resume.judul;
            document.getElementById('modalPemateriPenerbit').textContent = resume.nama_pemateri_penerbit || '-';
            if (resume.dokumentasi_link && resume.dokumentasi_link.trim() !== '') {
                document.getElementById('modalDokumentasi').href = resume.dokumentasi_link;
                document.getElementById('modalDokumentasi').textContent = resume.dokumentasi_link;
                document.getElementById('modalDokumentasiWrapper').style.display = 'block';
            } else {
                document.getElementById('modalDokumentasiWrapper').style.display = 'none';
            }
            document.getElementById('modalDibuat').textContent = new Date(resume.created_at).toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'long', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Gunakan resume_html jika ada, jika tidak gunakan resume_text
            if (resume.resume_html && resume.resume_html.trim() !== '') {
                document.getElementById('modalResumeContent').innerHTML = resume.resume_html;
            } else {
                document.getElementById('modalResumeContent').innerHTML = '<p>' + resume.resume_text.replace(/\n/g, '</p><p>') + '</p>';
            }
            
            // Tampilkan feedback jika ada
            const feedbackSection = document.getElementById('feedbackSection');

            if (resume.feedback && resume.feedback.trim() !== '') {
                document.getElementById('modalGuruNama').textContent = resume.guru_nama || 'Guru';
                document.getElementById('modalFeedbackDate').textContent = resume.feedback_date ? new Date(resume.feedback_date).toLocaleDateString('id-ID', { 
                    day: 'numeric', 
                    month: 'long', 
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '-';
                document.getElementById('modalFeedback').innerHTML = resume.feedback.replace(/\n/g, '<br>');
                feedbackSection.style.display = 'block';
            } else {
                feedbackSection.style.display = 'none';
            }
            
            document.getElementById('resumeModal').classList.add('active');
        }

        /* TUTUP MODAL RESUME */
        function closeResumeModal() {
            document.getElementById('resumeModal').classList.remove('active');
        }

        // Tutup modal ketika klik di luar modal
        document.getElementById('resumeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeResumeModal();
            }
        });

        // Tutup modal dengan tombol Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeResumeModal();
            }
        });

        /* GRAFIK */
        const ctx = document.getElementById('literasiChart');

        // Data dari PHP untuk grafik
        const chartLabelsMonthly = <?php echo json_encode($chart_labels_monthly); ?>;
        const chartDataMonthly = <?php echo json_encode($chart_data_monthly); ?>;
        
        // Data real dari PHP untuk mingguan
        const chartLabelsWeekly = <?php echo json_encode($chart_labels_weekly); ?>;
        const chartDataWeekly = <?php echo json_encode($chart_data_weekly); ?>;
        
        // Data real dari PHP untuk tahunan
        const chartLabelsYearly = <?php echo json_encode($chart_labels_yearly); ?>;
        const chartDataYearly = <?php echo json_encode($chart_data_yearly); ?>;

        let chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabelsMonthly,
                datasets: [{
                    label: 'Jumlah Literasi',
                    data: chartDataMonthly,
                    borderWidth: 3,
                    tension: 0.3,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)'
                }]
            },

            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        /* FILTER GRAFIK */
        document.getElementById('filterGrafik').addEventListener('change', function() {

            if (this.value === 'minggu') {
                chart.data.labels = chartLabelsWeekly;
                chart.data.datasets[0].data = chartDataWeekly;
            } else if (this.value === 'bulan') {
                chart.data.labels = chartLabelsMonthly;
                chart.data.datasets[0].data = chartDataMonthly;
            } else {
                chart.data.labels = chartLabelsYearly;
                chart.data.datasets[0].data = chartDataYearly;
            }

            chart.update();

        });

        <?php include __DIR__ . '/../includes/profile_js.php'; ?>
    </script>

</body>

</html>
