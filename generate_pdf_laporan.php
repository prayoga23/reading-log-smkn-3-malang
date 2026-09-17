<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/inc_koneksi.php';

// Check if user is logged in as guru
if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Guru') {
    header('Location: index.php');
    exit;
}

// Include FPDF (let's make sure we have it
$fpdfPath = __DIR__ . '/libraries/fpdf.php';
if (!file_exists($fpdfPath)) {
    // If FPDF not found, let's download it on the fly
    $fpdfContent = file_get_contents('https://github.com/FPDF/fpdf/raw/1.86/fpdf.php');
    file_put_contents($fpdfPath, $fpdfContent);
}
require $fpdfPath;

// Get guru data
$user_id = $_SESSION['user_id'];
$stmt = $koneksi->prepare('SELECT nama, kelas FROM users WHERE id = ? AND jabatan = ?');
$jabatan = 'Guru';
$guru = null;
if ($stmt) {
    $stmt->bind_param('is', $user_id, $jabatan);
    $stmt->execute();
    $result = $stmt->get_result();
    $guru = $result->fetch_assoc();
    $stmt->close();
}

if (!$guru) {
    die('Data guru tidak ditemukan.');
}

$kelas_guru = $guru['kelas'] ?? '';

// Helper functions (copied from dashboard_guru.php
function ensure_resume_table($koneksi) {
    return $koneksi->query(
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
}

function ensure_penilaian_table($koneksi) {
    return $koneksi->query(
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
}

function get_single_count($koneksi, $sql, $types = '', $params = []) {
    try {
        $stmt = $koneksi->prepare($sql);
    } catch (Throwable $e) {
        return 0;
    }
    if (!$stmt) {
        return 0;
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return (int) ($row['total'] ?? 0);
}

// Get the stats
$stat_total_resume = 0;
$stat_total_siswa = 0;
$stat_siswa_sudah_upload = 0;
$stat_siswa_belum_upload = 0;
$top_readers = [];

$resume_table_ready = ensure_resume_table($koneksi);
$penilaian_table_ready = ensure_penilaian_table($koneksi);

if ($resume_table_ready && $penilaian_table_ready) {
    $stat_total_siswa = get_single_count(
        $koneksi,
        "SELECT COUNT(*) AS total FROM users WHERE kelas = ? AND jabatan = 'Siswa'",
        's',
        [$kelas_guru]
    );
    $stat_siswa_sudah_upload = get_single_count(
        $koneksi,
        "SELECT COUNT(DISTINCT r.user_id) AS total
         FROM resume_literasi r
         JOIN users u ON r.user_id = u.id
         WHERE u.kelas = ? AND u.jabatan = 'Siswa'",
        's',
        [$kelas_guru]
    );
    $stat_siswa_belum_upload = max(0, $stat_total_siswa - $stat_siswa_sudah_upload);
    
    $stmt = $koneksi->prepare(
        "SELECT COUNT(*) AS total FROM resume_literasi r
         JOIN users u ON r.user_id = u.id
         WHERE u.kelas = ? AND u.jabatan = 'Siswa'"
    );
    if ($stmt) {
        $stmt->bind_param('s', $kelas_guru);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stat_total_resume = (int) ($row['total'] ?? 0);
        $stmt->close();
    }

    try {
        $stmt = $koneksi->prepare(
            "SELECT u.nama, u.nis_nip, COUNT(r.id) AS total_resume
             FROM users u
             LEFT JOIN resume_literasi r ON r.user_id = u.id
             WHERE u.kelas = ? AND u.jabatan = 'Siswa'
             GROUP BY u.id, u.nama, u.nis_nip
             HAVING total_resume > 0
             ORDER BY total_resume DESC, u.nama ASC
             LIMIT 5"
        );
    } catch (Throwable $e) {
        $stmt = false;
    }

    if ($stmt) {
        $stmt->bind_param('s', $kelas_guru);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $top_readers[] = $row;
        }
        $stmt->close();
    }
}

// Now create the PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $logoPath = __DIR__ . '/logo/1. logo smk negeri 3 malang.png';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 10, 8, 30);
        }
        // Title
        $this->SetFont('Arial', 'B', 18);
        $this->Cell(0, 15, 'Laporan Literasi Kelas', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 8, 'SMK Negeri 3 Malang', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

// Add content
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Kelas: ' . ($kelas_guru ?: '-'), 0, 1);
$pdf->Ln(5);

// RINGKASAN STATISTIK LITERASI
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'RINGKASAN STATISTIK LITERASI', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

// Table for statistics
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(80, 10, 'Keterangan', 1, 0, 'L', true);
$pdf->Cell(40, 10, 'Jumlah', 1, 1, 'C', true);

$pdf->Cell(80, 8, 'Total Upload Resume', 1);
$pdf->Cell(40, 8, number_format($stat_total_resume, 0, ',', '.'), 1, 1, 'C');

$pdf->Cell(80, 8, 'Total Siswa Kelas', 1);
$pdf->Cell(40, 8, number_format($stat_total_siswa, 0, ',', '.'), 1, 1, 'C');

$pdf->Cell(80, 8, 'Siswa Sudah Upload', 1);
$pdf->Cell(40, 8, number_format($stat_siswa_sudah_upload, 0, ',', '.'), 1, 1, 'C');

$pdf->Cell(80, 8, 'Siswa Belum Upload', 1);
$pdf->Cell(40, 8, number_format($stat_siswa_belum_upload, 0, ',', '.'), 1, 1, 'C');

$pdf->Ln(5);

// 5 Pembaca Paling Aktif di Kelas
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, '5 Pembaca Paling Aktif di Kelas', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);

// Table for top readers
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(80, 8, 'Nama', 1, 0, 'L', true);
$pdf->Cell(40, 8, 'NIS/NIP', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Jumlah Resume', 1, 1, 'C', true);

if (empty($top_readers)) {
    $pdf->Cell(160, 8, 'Belum ada siswa yang mengunggah resume.', 1, 1, 'C');
} else {
    foreach ($top_readers as $index => $reader) {
        $pdf->Cell(10, 8, ($index + 1), 1, 0, 'C');
        $pdf->Cell(80, 8, ($reader['nama'] ?? '-'), 1);
        $pdf->Cell(40, 8, ($reader['nis_nip'] ?? '-'), 1, 0, 'C');
        $pdf->Cell(30, 8, ((int)$reader['total_resume']), 1, 1, 'C');
    }
}

if (ob_get_length()) {
    ob_end_clean();
}
$pdf->Output('I', 'Laporan_Literasi_Kelas_' . ($kelas_guru ?: 'TanpaKelas') . '.pdf');
