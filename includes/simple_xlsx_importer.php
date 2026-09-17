<?php
/**
 * SimpleXlsxImporter
 * Standalone parser & importer untuk file Excel (.xlsx) dan CSV
 * Mendukung format absensi/buku nilai SMKN 3 Malang dan format tabel standar.
 * Tanpa library pihak ketiga — murni menggunakan ZipArchive & SimpleXML bawaan PHP.
 */

class SimpleXlsxImporter
{
    /**
     * Membaca seluruh file dalam archive ZIP/XLSX
     * Mendukung fallback otomatis jika ekstensi ZipArchive tidak terinstall di server PHP
     */
    public static function readZipEntries(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan: " . htmlspecialchars($filePath));
        }

        // 1. Coba ZipArchive jika ekstensi tersedia
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $entries = [];
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = ltrim(str_replace('\\', '/', $zip->getNameIndex($i)), '/');
                    $entries[$name] = $zip->getFromIndex($i);
                }
                $zip->close();
                return $entries;
            }
        }

        // 2. Fallback: Pure-PHP PKZIP Central Directory Reader (menggunakan gzinflate bawaan PHP core)
        $fp = @fopen($filePath, 'rb');
        if (!$fp) {
            throw new Exception("Gagal membuka file Excel: " . htmlspecialchars($filePath));
        }

        // Cari End of Central Directory Record (EOCD signature: PK\x05\x06)
        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);
        $searchLen = min($fileSize, 65557);
        fseek($fp, $fileSize - $searchLen);
        $data = fread($fp, $searchLen);

        $eocdPos = strrpos($data, "PK\x05\x06");
        if ($eocdPos !== false) {
            $eocd = unpack('vdisk/vcd_disk/vdisk_entries/vtotal_entries/Vcd_size/Vcd_offset/vcomment_len', substr($data, $eocdPos + 4, 18));
            fseek($fp, $eocd['cd_offset']);
            $entries = [];

            for ($i = 0; $i < $eocd['total_entries']; $i++) {
                $sig = fread($fp, 4);
                if ($sig !== "PK\x01\x02") {
                    break;
                }
                $cd = unpack('vversion/vversion_needed/vflag/vmethod/vmodtime/vmoddate/Vcrc/Vcsize/Vusize/vnamelen/vextralen/vcommentlen/vdisk_start/vint_attr/Vext_attr/Vlocal_offset', fread($fp, 42));
                $name = fread($fp, $cd['namelen']);
                if ($cd['extralen'] > 0) fseek($fp, $cd['extralen'], SEEK_CUR);
                if ($cd['commentlen'] > 0) fseek($fp, $cd['commentlen'], SEEK_CUR);

                $nameNorm = ltrim(str_replace('\\', '/', $name), '/');

                // Baca data dari local header
                $curPos = ftell($fp);
                fseek($fp, $cd['local_offset']);
                $locSig = fread($fp, 4);
                if ($locSig === "PK\x03\x04") {
                    $loc = unpack('vversion/vflag/vmethod/vmodtime/vmoddate/Vcrc/Vcsize/Vusize/vnamelen/vextralen', fread($fp, 26));
                    fseek($fp, $loc['namelen'] + $loc['extralen'], SEEK_CUR);
                    $cdata = fread($fp, $cd['csize']);

                    if ($cd['method'] == 8) {
                        $decompressed = @gzinflate($cdata);
                        if ($decompressed !== false) {
                            $entries[$nameNorm] = $decompressed;
                        }
                    } elseif ($cd['method'] == 0) {
                        $entries[$nameNorm] = $cdata;
                    }
                }
                fseek($fp, $curPos);
            }
            fclose($fp);
            if (!empty($entries)) {
                return $entries;
            }
        } else {
            fclose($fp);
        }

        // 3. Fallback: coba unzip command line jika tersedia di OS server
        if (function_exists('shell_exec') || function_exists('exec')) {
            $tmpDir = sys_get_temp_dir() . '/xlsx_' . uniqid();
            @mkdir($tmpDir, 0777, true);
            $cmd = 'unzip -q ' . escapeshellarg($filePath) . ' -d ' . escapeshellarg($tmpDir) . ' 2>&1';
            @shell_exec($cmd);
            if (file_exists($tmpDir . '/xl/workbook.xml')) {
                $entries = [];
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $item) {
                    if ($item->isFile()) {
                        $rel = ltrim(str_replace(['\\', $tmpDir], ['/', ''], $item->getPathname()), '/');
                        $entries[$rel] = file_get_contents($item->getPathname());
                    }
                }
                // Hapus folder sementara
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $f) {
                    $f->isDir() ? @rmdir($f->getRealPath()) : @unlink($f->getRealPath());
                }
                @rmdir($tmpDir);
                if (!empty($entries)) {
                    return $entries;
                }
            }
        }

        throw new Exception("Tidak dapat mengekstrak file Excel (.xlsx). Pastikan file tidak rusak atau gunakan format CSV (.csv).");
    }

    /**
     * Membaca file .xlsx dan mengembalikan array: sheetName => rows => columns
     */
    public static function parseXlsx(string $filePath): array
    {
        $zipEntries = self::readZipEntries($filePath);

        // Helper untuk mencari file entry dengan variasi path
        $getEntry = function(string $path) use (&$zipEntries): ?string {
            $pathNorm = ltrim(str_replace('\\', '/', $path), '/');
            if (isset($zipEntries[$pathNorm])) {
                return $zipEntries[$pathNorm];
            }
            if (strpos($pathNorm, 'xl/') !== 0 && isset($zipEntries['xl/' . $pathNorm])) {
                return $zipEntries['xl/' . $pathNorm];
            }
            return null;
        };

        // 1. Baca shared strings
        $sharedStrings = [];
        $ssXml = $getEntry('xl/sharedStrings.xml');
        if ($ssXml) {
            $xml = @simplexml_load_string($ssXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $str = '';
                        foreach ($si->r as $r) {
                            $str .= (string) $r->t;
                        }
                        $sharedStrings[] = $str;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca relationships untuk mencocokkan rId sheet ke target file xml
        $rels = [];
        $relsXml = $getEntry('xl/_rels/workbook.xml.rels');
        if ($relsXml) {
            $xml = @simplexml_load_string($relsXml);
            if ($xml) {
                foreach ($xml->Relationship as $rel) {
                    $rels[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        // 3. Baca nama sheet dari workbook.xml
        $sheets = [];
        $wbXml = $getEntry('xl/workbook.xml');
        if ($wbXml) {
            $xml = @simplexml_load_string($wbXml);
            if ($xml && isset($xml->sheets->sheet)) {
                foreach ($xml->sheets->sheet as $s) {
                    $sheetName = (string) $s['name'];
                    $rIdAttr = $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $rId = (string) ($rIdAttr['id'] ?? '');
                    $target = $rels[$rId] ?? '';
                    if ($target) {
                        if (strpos($target, '/') !== 0 && strpos($target, 'xl/') !== 0) {
                            $target = 'xl/' . $target;
                        }
                        $sheets[$sheetName] = $target;
                    }
                }
            }
        }

        // 4. Baca data setiap sheet
        $result = [];
        foreach ($sheets as $name => $xmlPath) {
            $sheetContent = $getEntry($xmlPath);
            if (!$sheetContent) {
                continue;
            }
            $xml = @simplexml_load_string($sheetContent);
            if (!$xml || !isset($xml->sheetData->row)) {
                continue;
            }

            $rows = [];
            foreach ($xml->sheetData->row as $row) {
                $rIndex = (int) $row['r'];
                $cells = [];
                foreach ($row->c as $c) {
                    $ref = (string) $c['r'];
                    $col = preg_replace('/[0-9]/', '', $ref);
                    $t = (string) $c['t'];
                    $val = '';

                    if ($t === 's' && isset($c->v)) {
                        $idx = (int) $c->v;
                        $val = $sharedStrings[$idx] ?? '';
                    } elseif ($t === 'inlineStr' && isset($c->is->t)) {
                        $val = (string) $c->is->t;
                    } elseif (isset($c->v)) {
                        $val = (string) $c->v;
                    }

                    $cells[$col] = trim($val);
                }
                $rows[$rIndex] = $cells;
            }
            $result[$name] = $rows;
        }

        return $result;
    }

    /**
     * Membaca file .csv
     */
    public static function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File CSV tidak ditemukan.");
        }

        $rows = [];
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new Exception("Gagal membaca file CSV.");
        }

        $rowIndex = 1;
        while (($data = fgetcsv($handle, 2000, ',')) !== false) {
            if ($data === [null] || empty($data)) {
                continue;
            }
            $cellRow = [];
            foreach ($data as $colIdx => $val) {
                $colLetter = self::colIndexToLetter($colIdx);
                $cellRow[$colLetter] = trim($val);
            }
            $rows[$rowIndex] = $cellRow;
            $rowIndex++;
        }
        fclose($handle);

        return ['Sheet1' => $rows];
    }

    private static function colIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intval($index / 26) - 1;
        }
        return $letter;
    }

    /**
     * Otomatis deteksi Jurusan dari Nama Kelas
     */
    public static function detectJurusan(string $namaKelas): string
    {
        $upper = strtoupper($namaKelas);
        if (strpos($upper, 'PH') !== false || strpos($upper, 'HOTEL') !== false || strpos($upper, 'PERHOTELAN') !== false) {
            return 'Perhotelan';
        }
        if (strpos($upper, 'KUL') !== false || strpos($upper, 'BOGA') !== false || strpos($upper, 'KULINER') !== false) {
            return 'Kuliner';
        }
        if (strpos($upper, 'TKC') !== false || strpos($upper, 'KECANTIKAN') !== false || strpos($upper, 'RAMBUT') !== false) {
            return 'Tata Kecantikan';
        }
        if (strpos($upper, 'DPB') !== false || strpos($upper, 'BUSANA') !== false || strpos($upper, 'BSN') !== false) {
            return 'Desain dan Produksi Busana';
        }
        if (strpos($upper, 'TKJ') !== false || strpos($upper, 'KOMPUTER') !== false || strpos($upper, 'JARINGAN') !== false) {
            return 'Teknik Komputer Jaringan';
        }
        return 'Perhotelan';
    }

    /**
     * Otomatis deteksi Tingkat dari Nama Kelas (X, XI, XII)
     */
    public static function detectTingkat(string $namaKelas): string
    {
        $upper = strtoupper(trim($namaKelas));
        if (preg_match('/\b(XII)\b/', $upper)) return 'XII';
        if (preg_match('/\b(XI)\b/', $upper)) return 'XI';
        if (preg_match('/\b(X)\b/', $upper)) return 'X';
        if (str_starts_with($upper, 'XII')) return 'XII';
        if (str_starts_with($upper, 'XI')) return 'XI';
        if (str_starts_with($upper, 'X')) return 'X';
        return 'X';
    }

    /**
     * Scan sheet untuk menemukan sheet yang merupakan daftar kelas & siswa valid
     */
    public static function inspectRosterSheets(array $sheets): array
    {
        $rosters = [];

        foreach ($sheets as $sheetName => $rows) {
            $isRoster = false;
            $className = '';
            $waliKelas = '';
            $headerRow = 0;
            $colNipd = 'B';
            $colNama = 'C';
            $studentCount = 0;

            foreach ($rows as $rNum => $cells) {
                if ($rNum <= 6) {
                    $rowText = strtoupper(implode(' ', $cells));

                    if ($rNum == 2 && !empty($cells['A'])) {
                        $className = trim($cells['A']);
                    }

                    if (strpos($rowText, 'WALI KELAS') !== false) {
                        foreach ($cells as $val) {
                            if (stripos($val, 'WALI KELAS') !== false) {
                                $p = explode(':', $val);
                                if (isset($p[1])) {
                                    $waliKelas = trim($p[1]);
                                }
                            }
                        }
                    }

                    if (strpos($rowText, 'NIPD') !== false && strpos($rowText, 'NAMA') !== false) {
                        $isRoster = true;
                        $headerRow = $rNum;
                        foreach ($cells as $col => $v) {
                            $vu = strtoupper(trim($v));
                            if ($vu === 'NIPD' || strpos($vu, 'NISN') !== false) $colNipd = $col;
                            if ($vu === 'NAMA' || strpos($vu, 'NAMA PESERTA') !== false) $colNama = $col;
                        }
                    }
                } elseif ($isRoster && $rNum > $headerRow) {
                    $nama = trim($cells[$colNama] ?? '');
                    if (stripos($nama, 'TOTAL') !== false || stripos($nama, 'LAKI-LAKI') !== false || stripos($nama, 'PEREMPUAN') !== false) {
                        break;
                    }
                    if (strlen($nama) > 2) {
                        $studentCount++;
                    }
                }
            }

            if ($isRoster && $studentCount > 0) {
                if (!$className) {
                    $className = $sheetName;
                }
                $rosters[$sheetName] = [
                    'sheet_name'   => $sheetName,
                    'class_name'   => $className,
                    'wali_kelas'   => $waliKelas,
                    'student_count'=> $studentCount,
                    'header_row'   => $headerRow,
                    'col_nipd'     => $colNipd,
                    'col_nama'     => $colNama
                ];
            }
        }

        return $rosters;
    }

    /**
     * Import format sekolah SMKN 3 (seperti A. KELAS X 2026-2027.xlsx)
     */
    public static function importSchoolSheets(array $sheets, mysqli $koneksi, array $options = []): array
    {
        $targetSheet = $options['target_sheet'] ?? 'all';
        $defaultPasswordSiswa = '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6'; // siswa123
        $defaultPasswordGuru  = '$2y$10$7ehf9UIclph7QsywMBph/eswrNme4uYsvsDXbEjpY41kaF7qRGYFi'; // guru123

        $summary = [
            'kelas_count' => 0,
            'guru_count'  => 0,
            'siswa_count' => 0,
            'skipped'     => 0,
            'details'     => []
        ];

        $rosters = self::inspectRosterSheets($sheets);
        if (empty($rosters)) {
            // Jika format bukan format roster sekolah SMKN 3, coba format tabel standar
            $firstSheetRows = reset($sheets);
            return self::importStandardTable($firstSheetRows, $koneksi);
        }

        $stmtKelas = $koneksi->prepare("
            INSERT INTO `kelas` (`nama_kelas`, `tingkat`, `jurusan`, `wali_kelas`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                `jurusan` = VALUES(`jurusan`),
                `wali_kelas` = VALUES(`wali_kelas`)
        ");

        $stmtUser = $koneksi->prepare("
            INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                `nama` = VALUES(`nama`),
                `kelas` = VALUES(`kelas`),
                `jurusan` = VALUES(`jurusan`),
                `is_active` = VALUES(`is_active`)
        ");

        foreach ($rosters as $sheetName => $meta) {
            if ($targetSheet !== 'all' && $targetSheet !== $sheetName) {
                continue;
            }

            $rows = $sheets[$sheetName];
            $namaKelas = preg_replace('/\s+/', ' ', trim($meta['class_name']));
            $waliKelas = trim($meta['wali_kelas']);
            $tingkat = self::detectTingkat($namaKelas);
            $jurusan = self::detectJurusan($namaKelas);

            // 1. Simpan Kelas
            if ($namaKelas) {
                $stmtKelas->bind_param('ssss', $namaKelas, $tingkat, $jurusan, $waliKelas);
                if ($stmtKelas->execute()) {
                    $summary['kelas_count']++;
                }
            }

            // 2. Simpan Wali Kelas (Guru)
            if ($waliKelas && strtoupper($waliKelas) !== '-' && strlen($waliKelas) > 3) {
                $emailWali = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', explode(',', $waliKelas)[0]));
                $emailWali = trim($emailWali, '.') . '@example.com';
                $jabatanGuru = 'Guru';
                $nipPlaceholder = 'GURU_' . strtoupper(substr(md5($waliKelas), 0, 6));

                $chk = $koneksi->query("SELECT nis_nip FROM users WHERE nama = '" . $koneksi->real_escape_string($waliKelas) . "' AND jabatan = 'Guru' LIMIT 1");
                if ($chk && $rowG = $chk->fetch_assoc()) {
                    $nipPlaceholder = $rowG['nis_nip'];
                }

                $stmtUser->bind_param('sssssss', $waliKelas, $emailWali, $defaultPasswordGuru, $jabatanGuru, $nipPlaceholder, $namaKelas, $jurusan);
                if ($stmtUser->execute()) {
                    $summary['guru_count']++;
                }
            }

            // 3. Simpan Siswa
            $sheetSiswaCount = 0;
            $headerRowIndex = $meta['header_row'];
            $colNipd = $meta['col_nipd'];
            $colNama = $meta['col_nama'];

            foreach ($rows as $rNum => $cells) {
                if ($rNum <= $headerRowIndex) continue;

                $nipd = trim($cells[$colNipd] ?? '');
                $nama = trim($cells[$colNama] ?? '');

                if (stripos($nama, 'TOTAL') !== false || stripos($nama, 'LAKI-LAKI') !== false || stripos($nama, 'PEREMPUAN') !== false) {
                    break;
                }

                if ($nipd === '' && $nama === '') continue;
                if (strlen($nama) < 2) continue;

                $emailUser = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $nama));
                $emailUser = trim($emailUser, '.');
                $nisSiswa = $nipd !== '' ? $nipd : ('SISWA_' . substr(md5($nama . $namaKelas), 0, 8));
                $emailSiswa = $emailUser . '@example.com';

                // Cegah duplikasi email jika nama sama dengan siswa lain
                $chkEmail = $koneksi->query("SELECT id FROM users WHERE email = '" . $koneksi->real_escape_string($emailSiswa) . "' AND nis_nip != '" . $koneksi->real_escape_string($nisSiswa) . "' LIMIT 1");
                if ($chkEmail && $chkEmail->num_rows > 0) {
                    $suffix = preg_replace('/[^0-9a-z]/', '', $nisSiswa);
                    $emailSiswa = $emailUser . '.' . substr($suffix, -4) . '@example.com';
                }

                $jabatanSiswa = 'Siswa';
                $stmtUser->bind_param('sssssss', $nama, $emailSiswa, $defaultPasswordSiswa, $jabatanSiswa, $nisSiswa, $namaKelas, $jurusan);
                if ($stmtUser->execute()) {
                    $summary['siswa_count']++;
                    $sheetSiswaCount++;
                } else {
                    $summary['skipped']++;
                }
            }

            $summary['details'][] = "Sheet <strong>" . htmlspecialchars($sheetName) . "</strong> (" . htmlspecialchars($namaKelas) . "): Berhasil mengimpor {$sheetSiswaCount} siswa & Wali Kelas " . ($waliKelas ? htmlspecialchars($waliKelas) : '-');
        }

        $stmtKelas->close();
        $stmtUser->close();

        return $summary;
    }

    /**
     * Import format tabel kolom standar
     */
    public static function importStandardTable(array $rows, mysqli $koneksi): array
    {
        $defaultPasswordSiswa = '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6'; // siswa123
        $defaultPasswordGuru  = '$2y$10$7ehf9UIclph7QsywMBph/eswrNme4uYsvsDXbEjpY41kaF7qRGYFi'; // guru123

        $summary = [
            'kelas_count' => 0,
            'guru_count'  => 0,
            'siswa_count' => 0,
            'skipped'     => 0,
            'details'     => []
        ];

        if (empty($rows)) return $summary;

        // Cari baris header
        $colMap = [];
        $headerKey = 1;
        foreach ($rows as $rKey => $cells) {
            foreach ($cells as $col => $val) {
                $v = strtolower(trim($val));
                if (strpos($v, 'nama') !== false && strpos($v, 'wali') === false) $colMap['nama'] = $col;
                elseif (strpos($v, 'nis') !== false || strpos($v, 'nip') !== false) $colMap['nis_nip'] = $col;
                elseif (strpos($v, 'kelas') !== false && strpos($v, 'wali') === false) $colMap['kelas'] = $col;
                elseif (strpos($v, 'jurusan') !== false || strpos($v, 'program') !== false) $colMap['jurusan'] = $col;
                elseif (strpos($v, 'jabatan') !== false || strpos($v, 'role') !== false) $colMap['jabatan'] = $col;
                elseif (strpos($v, 'email') !== false) $colMap['email'] = $col;
                elseif (strpos($v, 'password') !== false) $colMap['password'] = $col;
                elseif (strpos($v, 'wali') !== false) $colMap['wali_kelas'] = $col;
            }
            if (isset($colMap['nama'])) {
                $headerKey = $rKey;
                break;
            }
        }

        if (!isset($colMap['nama'])) {
            throw new Exception("Header kolom 'Nama' tidak ditemukan di baris judul.");
        }

        $stmtKelas = $koneksi->prepare("
            INSERT INTO `kelas` (`nama_kelas`, `tingkat`, `jurusan`, `wali_kelas`)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                `jurusan` = VALUES(`jurusan`),
                `wali_kelas` = VALUES(`wali_kelas`)
        ");

        $stmtUser = $koneksi->prepare("
            INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                `nama` = VALUES(`nama`),
                `kelas` = VALUES(`kelas`),
                `jurusan` = VALUES(`jurusan`),
                `is_active` = VALUES(`is_active`)
        ");

        $countSiswa = 0;
        $countGuru  = 0;

        foreach ($rows as $rNum => $cells) {
            if ($rNum <= $headerKey) continue;

            $nama = trim($cells[$colMap['nama'] ?? ''] ?? '');
            if ($nama === '') continue;

            $nisNip = trim($cells[$colMap['nis_nip'] ?? ''] ?? '');
            $kelas = trim($cells[$colMap['kelas'] ?? ''] ?? '-');
            $jurusan = trim($cells[$colMap['jurusan'] ?? ''] ?? '');
            if ($jurusan === '' && $kelas !== '-') {
                $jurusan = self::detectJurusan($kelas);
            }
            $jabatan = ucfirst(strtolower(trim($cells[$colMap['jabatan'] ?? ''] ?? 'Siswa')));
            if (!in_array($jabatan, ['Siswa', 'Guru', 'Super Admin'])) {
                $jabatan = 'Siswa';
            }

            $email = trim($cells[$colMap['email'] ?? ''] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $clean = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '.', $nama));
                $email = trim($clean, '.') . '@example.com';
            }

            $rawPass = trim($cells[$colMap['password'] ?? ''] ?? '');
            if ($rawPass !== '') {
                $passHash = password_hash($rawPass, PASSWORD_DEFAULT);
            } else {
                $passHash = ($jabatan === 'Guru') ? $defaultPasswordGuru : $defaultPasswordSiswa;
            }

            $waliKelas = trim($cells[$colMap['wali_kelas'] ?? ''] ?? '');

            // Simpan Kelas
            if ($kelas !== '-' && $kelas !== '') {
                $tingkat = self::detectTingkat($kelas);
                $stmtKelas->bind_param('ssss', $kelas, $tingkat, $jurusan, $waliKelas);
                if ($stmtKelas->execute()) {
                    $summary['kelas_count']++;
                }
            }

            // Simpan User
            if ($nisNip === '') {
                $nisNip = strtoupper(substr($jabatan, 0, 1)) . '_' . substr(md5($nama . $email), 0, 8);
            }

            $stmtUser->bind_param('sssssss', $nama, $email, $passHash, $jabatan, $nisNip, $kelas, $jurusan);
            if ($stmtUser->execute()) {
                if ($jabatan === 'Guru') {
                    $countGuru++;
                    $summary['guru_count']++;
                } else {
                    $countSiswa++;
                    $summary['siswa_count']++;
                }
            } else {
                $summary['skipped']++;
            }
        }

        $stmtKelas->close();
        $stmtUser->close();

        $summary['details'][] = "Tabel Standar: Berhasil mengimpor {$countSiswa} siswa dan {$countGuru} guru.";
        return $summary;
    }

    /**
     * Kirim output file CSV template untuk di-download
     */
    public static function downloadTemplateCsv(): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=template_import_siswa_dan_guru.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Nama', 'NISN_NIP', 'Kelas', 'Jurusan', 'Jabatan', 'Email', 'Password', 'Wali_Kelas']);
        fputcsv($output, ['ABYM REYNO TIRTA RAZKY', '145312351092', 'X Perhotelan 1', 'Perhotelan', 'Siswa', 'abym.reyno@example.com', 'siswa123', 'SOEKARDI ARIF WIDIJAYANTO, S.Pd.']);
        fputcsv($output, ['SOEKARDI ARIF WIDIJAYANTO, S.Pd.', 'GURU003', 'X Perhotelan 1', 'Perhotelan', 'Guru', 'soekardi.arif@example.com', 'guru123', '']);
        fclose($output);
        exit;
    }
}
