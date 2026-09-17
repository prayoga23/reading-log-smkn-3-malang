<?php
session_start();
include __DIR__ . '/../inc_koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['jabatan'] ?? '') !== 'Guru') {
    header('Location: ../index.php');
    exit;
}

// Fungsi helper
function esc($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ensure_resume_table($koneksi)
{
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

function ensure_penilaian_table($koneksi)
{
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

function get_single_count($koneksi, $sql, $types = '', $params = [])
{
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

function format_chart_series($counts, $label_formatter, $limit)
{
    if (empty($counts)) {
        return [
            'labels' => ['Belum ada data'],
            'data' => [0],
        ];
    }

    ksort($counts);

    if (count($counts) > $limit) {
        $counts = array_slice($counts, -$limit, null, true);
    }

    $labels = [];
    $data = [];

    foreach ($counts as $key => $value) {
        $labels[] = $label_formatter($key);
        $data[] = (int) $value;
    }

    return [
        'labels' => $labels,
        'data' => $data,
    ];
}

// Ambil data guru
$user_id = $_SESSION['user_id'];
$stmt = $koneksi->prepare('SELECT id, nama, kelas FROM users WHERE id = ? AND jabatan = ?');
$jabatan = 'Guru';
$guru = null;

if ($stmt) {
    $stmt->bind_param('is', $user_id, $jabatan);
    $stmt->execute();
    $result = $stmt->get_result();
    $guru = $result->fetch_assoc();
    $stmt->close();
}

// Ambil data resume siswa berdasarkan kelas guru
$resume_data = [];
$kelas_guru = $guru['kelas'] ?? '';
$stat_total_resume = 0;
$stat_total_siswa = 0;
$stat_siswa_sudah_upload = 0;
$stat_siswa_belum_upload = 0;
$top_readers = [];

if ($guru) {
    $kelas = $kelas_guru;
    $resume_table_ready = false;
    $penilaian_table_ready = false;

    try {
        $resume_table_ready = ensure_resume_table($koneksi);
        $penilaian_table_ready = ensure_penilaian_table($koneksi);
    } catch (Throwable $e) {
        $resume_table_ready = false;
        $penilaian_table_ready = false;
    }

    if ($resume_table_ready && $penilaian_table_ready) {
        $sql = "SELECT
        r.*,
        u.nama AS siswa_nama,
        u.email,
        u.kelas,
        u.nis_nip,
        p.feedback,
        p.score
    FROM resume_literasi r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN penilaian_guru p ON r.id = p.resume_id AND p.guru_id = ?
                WHERE u.kelas = ? AND u.jabatan = 'Siswa'
                ORDER BY r.tanggal DESC";
        
        try {
            $stmt = $koneksi->prepare($sql);
        } catch (Throwable $e) {
            $stmt = false;
        }

        if ($stmt) {
            $stmt->bind_param('is', $user_id, $kelas);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $resume_data[] = $row;
            }
            $stmt->close();
        }

        $stat_total_resume = count($resume_data);
        $stat_total_siswa = get_single_count(
            $koneksi,
            "SELECT COUNT(*) AS total FROM users WHERE kelas = ? AND jabatan = 'Siswa'",
            's',
            [$kelas]
        );
        $stat_siswa_sudah_upload = get_single_count(
            $koneksi,
            "SELECT COUNT(DISTINCT r.user_id) AS total
             FROM resume_literasi r
             JOIN users u ON r.user_id = u.id
             WHERE u.kelas = ? AND u.jabatan = 'Siswa'",
            's',
            [$kelas]
        );
        $stat_siswa_belum_upload = max(0, $stat_total_siswa - $stat_siswa_sudah_upload);

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
            $stmt->bind_param('s', $kelas);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $top_readers[] = $row;
            }
            $stmt->close();
        }
    }
}

// Group data per tanggal
$grouped_data = [];
$daily_counts = [];
$weekly_counts = [];
$monthly_counts = [];
$yearly_counts = [];

foreach ($resume_data as $item) {
    $timestamp = strtotime($item['tanggal']);
    $date = date('Y-m-d', $timestamp);
    $date_formatted = date('d F Y', $timestamp);
    
    if (!isset($grouped_data[$date])) {
        $grouped_data[$date] = [
            'date_formatted' => $date_formatted,
            'items' => []
        ];
    }
    $grouped_data[$date]['items'][] = $item;

    $week = date('o-\WW', $timestamp);
    $month = date('Y-m', $timestamp);
    $year = date('Y', $timestamp);

    $daily_counts[$date] = ($daily_counts[$date] ?? 0) + 1;
    $weekly_counts[$week] = ($weekly_counts[$week] ?? 0) + 1;
    $monthly_counts[$month] = ($monthly_counts[$month] ?? 0) + 1;
    $yearly_counts[$year] = ($yearly_counts[$year] ?? 0) + 1;
}

$chart_data = [
    'harian' => format_chart_series($daily_counts, function ($key) {
        return date('d M', strtotime($key));
    }, 7),
    'mingguan' => format_chart_series($weekly_counts, function ($key) {
        return str_replace('-W', ' Minggu ', $key);
    }, 8),
    'bulanan' => format_chart_series($monthly_counts, function ($key) {
        return date('M Y', strtotime($key . '-01'));
    }, 12),
    'tahunan' => format_chart_series($yearly_counts, function ($key) {
        return $key;
    }, 5),
];

$pdf_summary = [
    'kelas' => $kelas_guru,
    'total_resume' => $stat_total_resume,
    'total_siswa' => $stat_total_siswa,
    'sudah_upload' => $stat_siswa_sudah_upload,
    'belum_upload' => $stat_siswa_belum_upload,
    'top_readers'  => $top_readers,
];

// Handler update profil guru
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
    <title>Dashboard Guru Reading Log | SMKN 3 Malang</title>

    <!-- Chart JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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

        <?php include __DIR__ . '/../includes/sidebar_css.php'; ?>

        /* MAIN */
        .main {
            margin-left: 280px;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .page {
            display: none;
        }

        .active {
            display: block;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #2563eb;
        }

        /* TABLE */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #2563eb;
            color: white;
            padding: 14px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        tr:hover {
            background: #f5f9ff;
        }

        /* BUTTON */
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }

        .btn-detail {
            background: #2563eb;
        }

        .btn-pdf {
            background: #059669;
        }

        .btn-simpan {
            background: #1d4ed8;
            width: 100%;
            margin-top: 10px;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            margin-top: 10px;
            resize: none;
        }

        input {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            margin-top: 10px;
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            width: 70%;
            margin: 40px auto;
            padding: 30px;
            border-radius: 20px;
            max-height: 85vh;
            overflow: auto;
        }

        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
        }

        /* GRID */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-box {
            background: #f8fbff;
            padding: 20px;
            border-radius: 15px;
            border-left: 6px solid #2563eb;
        }

        .stat-box h3 {
            color: #2563eb;
            margin-bottom: 8px;
        }

        .top-reader {
            padding: 12px;
            margin-bottom: 10px;
            background: #f4f8ff;
            border-radius: 12px;
        }

        @media(max-width:768px) {

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main {
                margin-left: 0;
            }

            .modal-content {
                width: 95%;
            }

            .header-top {
                flex-direction: column;
                align-items: flex-start;
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

                <button onclick="showPage('resumePage')">
                    Daftar Resume Siswa
                </button>

                <button onclick="showPage('statistikPage')">
                    Statistik Literasi
                </button>

            </div>
        </div>

        <button class="sidebar-profile-btn" onclick="openProfileModal()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
        </button>

    </div>

    <?php
    $profile_nama = esc($guru['nama'] ?? '');
    include __DIR__ . '/../includes/profile_modal.php';
    ?>

    <!-- MAIN -->
    <div class="main">

        <div class="header">
            <div class="header-top">
                <h1>Dashboard Guru</h1>
                <a class="logout-link" href="../logout.php">Log Out</a>
            </div>
            <p>
                Selamat datang, <strong><?php echo esc($guru['nama'] ?? '-'); ?></strong> &mdash;
                Kelas <?php echo esc($guru['kelas'] ?? '-'); ?> &bull;
                <?php echo esc($guru['jurusan'] ?? '-'); ?>
            </p>
        </div>

        <!-- PAGE RESUME -->
        <div id="resumePage" class="page active">

            <div class="card">
                <h2>Daftar Resume Literasi Siswa (Per Tanggal Upload)</h2>

                <?php if (!$guru): ?>
                    <p style="text-align: center; color: #dc2626; padding: 30px;">
                        Data guru tidak ditemukan. Pastikan akun guru sudah tersimpan dengan kelas yang valid di database.
                    </p>
                <?php elseif (empty($resume_data)): ?>
                    <p style="text-align: center; color: #999; padding: 30px;">
                        Belum ada resume siswa yang diupload.
                    </p>
                <?php else: ?>
                    <?php foreach ($grouped_data as $date => $group): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="background: #f3f4f6; padding: 12px 16px; border-radius: 8px; margin-bottom: 12px; color: #2563eb; font-size: 16px;">
                                📅 <?php echo $group['date_formatted']; ?> (<?php echo count($group['items']); ?> Resume)
                            </h3>
                            
                            <div style="overflow-x: auto;">
                                <table style="margin-bottom: 20px;">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Siswa</th>
                                            <th>NIPD</th>
                                            <th>Kelas</th>
                                            <th>Tema / Judul</th>
                                            <th>Pemateri / Penerbit</th>
                                            <th>Jenis Kegiatan</th>
                                            <th>Waktu Upload</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($group['items'] as $index => $resume): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo esc($resume['siswa_nama']); ?></td>
                                                <td><?php echo esc($resume['nis_nip']); ?></td>
                                                <td><?php echo esc($resume['kelas']); ?></td>
                                                <td><?php echo esc($resume['judul']); ?></td>
                                                <td><?php echo esc($resume['nama_pemateri_penerbit'] ?? '-'); ?></td>
                                                <td><?php echo esc($resume['kegiatan']); ?></td>
                                                <td><?php echo date('H:i', strtotime($resume['tanggal'])); ?></td>
                                                <td style="color: <?php echo empty($resume['feedback']) ? '#dc2626' : '#059669'; ?>; font-weight: bold;">
                                                    <?php echo empty($resume['feedback']) ? 'Belum Dinilai' : 'Sudah Dinilai'; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-detail" onclick="openModal('<?php echo esc($resume['id']); ?>')">Lihat</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>

        </div>

        <!-- PAGE STATISTIK -->
        <div id="statistikPage" class="page">

            <div class="grid">

                <div class="stat-box">
                    <h3>Total Upload Kelas</h3>
                    <p style="font-size:30px;font-weight:bold;"><?php echo number_format($stat_total_resume, 0, ',', '.'); ?></p>
                </div>

                <div class="stat-box">
                    <h3>Total Siswa Kelas</h3>
                    <p style="font-size:30px;font-weight:bold;"><?php echo number_format($stat_total_siswa, 0, ',', '.'); ?></p>
                </div>

                <div class="stat-box">
                    <h3>Siswa Sudah Upload</h3>
                    <p style="font-size:30px;font-weight:bold;"><?php echo number_format($stat_siswa_sudah_upload, 0, ',', '.'); ?></p>
                </div>

                <div class="stat-box">
                    <h3>Siswa Belum Upload</h3>
                    <p style="font-size:30px;font-weight:bold;"><?php echo number_format($stat_siswa_belum_upload, 0, ',', '.'); ?></p>
                </div>

            </div>

            <div class="card">
                <h2>Grafik Upload Resume Kelas</h2>

                <select id="filterChart" onchange="updateChart()" style="padding:10px;border-radius:10px;margin-bottom:20px;">
                    <option value="harian">Harian</option>
                    <option value="mingguan">Mingguan</option>
                    <option value="bulanan">Bulanan</option>
                    <option value="tahunan">Tahunan</option>
                </select>

                <canvas id="myChart"></canvas>

            </div>

            <div class="card">
                <h2>RINGKASAN STATISTIK LITERASI</h2>

                <?php if (empty($top_readers)): ?>
                    <div class="top-reader">
                        Belum ada siswa yang mengunggah resume.
                    </div>
                <?php else: ?>
                    <?php foreach ($top_readers as $index => $reader): ?>
                        <div class="top-reader">
                            <?php echo ($index + 1) . '. ' . esc($reader['nama']) . ' (' . esc($reader['nis_nip']) . ') - ' . (int) $reader['total_resume'] . ' resume'; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <a href="../generate_pdf_laporan.php" target="_blank" class="btn btn-pdf" style="text-decoration: none; display: inline-block;">
                    Cetak PDF Laporan
                </a>

            </div>

        </div>

    </div>

    <!-- MODAL -->
    <div class="modal" id="resumeModal">

        <div class="modal-content">

            <span class="close" onclick="closeModal()">&times;</span>

            <h2 style="margin-bottom:20px;color:#2563eb;">
                Detail Resume Literasi
            </h2>

            <div id="modalBody" style="margin-bottom: 20px;">
                <p style="text-align: center; color: #999;">Loading...</p>
            </div>

            <h3>Feedback Guru</h3>

            <textarea rows="5" id="feedback" placeholder="Masukkan feedback minimal 4 kata..."></textarea>

            <button class="btn btn-simpan" onclick="simpanPenilaian()">
                Simpan Feedback
            </button>

        </div>

    </div>

    <script>
        const chartDataSets = <?php echo json_encode($chart_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const pdfSummary = <?php echo json_encode($pdf_summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

        /* PINDAH HALAMAN */
        function showPage(pageId) {

            const pages = document.querySelectorAll('.page');

            pages.forEach(page => {
                page.classList.remove('active');
            });

            document.getElementById(pageId).classList.add('active');

        }

        /* MODAL */
        let currentResumeId = null;

        function openModal(resumeId) {
            currentResumeId = resumeId;
            document.getElementById('resumeModal').style.display = 'block';
            document.getElementById('modalBody').innerHTML = '<p style="text-align: center; color: #999;">Loading...</p>';
            document.getElementById('feedback').value = '';
            
            // Fetch data resume
            fetch('../get_resume.php?id=' + encodeURIComponent(resumeId), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (error) {
                        throw new Error('Response detail resume bukan JSON valid.');
                    }
                })
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Gagal memuat data resume.');
                    }

                    const resume = data.data;
                    const resumeContent = resume.resume_html && resume.resume_html.trim() !== ''
                        ? resume.resume_html
                        : escapeHtml(resume.resume_text || '').replace(/\n/g, '<br>');

                    const html = `
                        <p><b>Nama Siswa:</b> ${escapeHtml(resume.siswa_nama)}</p>
                        <p><b>NIPD:</b> ${escapeHtml(resume.nis_nip)}</p>
                        <p><b>Kelas:</b> ${escapeHtml(resume.kelas)}</p>
                        <p><b>Email:</b> ${escapeHtml(resume.email)}</p>
                        <p><b>Tema / Judul:</b> ${escapeHtml(resume.judul)}</p>
                        <p><b>Pemateri / Penerbit:</b> ${escapeHtml(resume.nama_pemateri_penerbit || '-')}</p>
                        <p><b>Jenis Kegiatan:</b> ${escapeHtml(resume.kegiatan)}</p>
                        ${resume.dokumentasi_link ? `<p><b>Dokumentasi:</b> <a href="${escapeHtml(resume.dokumentasi_link)}" target="_blank">${escapeHtml(resume.dokumentasi_link)}</a></p>` : ''}
                        <p><b>Tanggal Upload:</b> ${escapeHtml(resume.tanggal_formatted)}</p>
                        <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
                        <h4 style="margin-top: 20px;">Isi Resume:</h4>
                        <div style="padding: 12px; background: #f9fafb; border-radius: 8px; line-height: 1.8; text-align: justify;">
                            ${resumeContent}
                        </div>
                    `;

                    document.getElementById('modalBody').innerHTML = html;
                    document.getElementById('feedback').value = resume.feedback || '';
                })
                .catch(err => {
                    document.getElementById('modalBody').innerHTML = '<p style="color: red;">' + escapeHtml(err.message || 'Gagal memuat data resume.') + '</p>';
                });
        }

        function closeModal() {
            document.getElementById('resumeModal').style.display = 'none';
            currentResumeId = null;
            document.getElementById('feedback').value = '';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /* VALIDASI FEEDBACK */
        function simpanPenilaian() {

            const feedback = document.getElementById('feedback').value.trim();
            const jumlahKata = feedback.split(/\s+/).filter(word => word.length > 0).length;

            if (jumlahKata < 4) {
                alert('Feedback minimal harus 4 kata!');
                return;
            }

            if (!currentResumeId) {
                alert('Resume ID tidak ditemukan');
                return;
            }

            const params = new URLSearchParams({
                resume_id: currentResumeId,
                feedback: feedback
            });

            // Kirim data ke backend
            fetch('../save_penilaian.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
            .then(async response => {
                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (error) {
                    console.error('Response error:', text);
                    throw new Error('Response simpan penilaian bukan JSON valid.');
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Penilaian berhasil disimpan!');
                    closeModal();
                    location.reload();
                } else {
                    alert('Gagal menyimpan penilaian: ' + data.message);
                }
            })
            .catch(err => {
                alert('Terjadi kesalahan: ' + err);
            });
        }

        /* CHART */
        const ctx = document.getElementById('myChart');
        const initialChartData = chartDataSets.harian || {
            labels: ['Belum ada data'],
            data: [0]
        };

        let chart = new Chart(ctx, {

            type: 'line',

            data: {
                labels: initialChartData.labels,

                datasets: [{
                    label: 'Jumlah Upload Resume',
                    data: initialChartData.data,
                    borderWidth: 3,
                    tension: 0.3,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    fill: true
                }]
            },

            options: {
                responsive: true
            }

        });

        /* UPDATE CHART */
        function updateChart() {

            const filter = document.getElementById('filterChart').value;
            const dataset = chartDataSets[filter] || {
                labels: ['Belum ada data'],
                data: [0]
            };

            chart.data.labels = dataset.labels;
            chart.data.datasets[0].data = dataset.data;

            chart.update();

        }

        /* PDF */
        async function downloadPDF() {

            const {
                jsPDF
            } = window.jspdf;
            const doc = new jsPDF();
            const topReaders = Array.isArray(pdfSummary.top_readers) ? pdfSummary.top_readers : [];

            doc.setFontSize(18);
            doc.text('Laporan Literasi Kelas', 20, 20);

            doc.setFontSize(12);
            doc.text('Kelas: ' + (pdfSummary.kelas || '-'), 20, 40);
            doc.text('Total Upload Resume: ' + (pdfSummary.total_resume || 0), 20, 50);
            doc.text('Total Siswa Kelas: ' + (pdfSummary.total_siswa || 0), 20, 60);
            doc.text('Siswa Sudah Upload: ' + (pdfSummary.sudah_upload || 0), 20, 70);
            doc.text('Siswa Belum Upload: ' + (pdfSummary.belum_upload || 0), 20, 80);

            doc.text('5 Pembaca Paling Aktif:', 20, 100);
            if (topReaders.length === 0) {
                doc.text('Belum ada siswa yang mengunggah resume.', 25, 110);
            } else {
                topReaders.forEach((reader, index) => {
                    doc.text(
                        (index + 1) + '. ' + (reader.nama || '-') + ' - ' + (reader.total_resume || 0) + ' resume',
                        25,
                        110 + (index * 10)
                    );
                });
            }

            doc.save('laporan-literasi.pdf');

        }

        <?php include __DIR__ . '/../includes/profile_js.php'; ?>
    </script>

</body>

</html>
