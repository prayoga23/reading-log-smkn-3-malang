-- ==========================================================
-- SQL Data: Kelas X Perhotelan 1, Wali Kelas, dan 35 Siswa
-- Berdasarkan: A. KELAS X 2026-2027.xlsx (Sheet: X PH 1)
-- Tahun Pelajaran: 2026 - 2027
-- SMKN 3 Malang - Sistem Reading Log Literasi
-- ==========================================================

-- 1. Insert / Update Data Kelas
INSERT INTO `kelas` (`nama_kelas`, `tingkat`, `jurusan`, `wali_kelas`)
VALUES ('X Perhotelan 1', 'X', 'Perhotelan', 'SOEKARDI ARIF WIDIJAYANTO, S.Pd.')
ON DUPLICATE KEY UPDATE
    `jurusan` = VALUES(`jurusan`),
    `wali_kelas` = VALUES(`wali_kelas`);

-- 2. Insert / Update Data Wali Kelas (Guru)
-- Password default: guru123
INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
VALUES (
    'SOEKARDI ARIF WIDIJAYANTO, S.Pd.',
    'soekardi.arif@example.com',
    '$2y$10$7ehf9UIclph7QsywMBph/eswrNme4uYsvsDXbEjpY41kaF7qRGYFi',
    'Guru',
    'GURU003',
    'X Perhotelan 1',
    'Perhotelan',
    1
)
ON DUPLICATE KEY UPDATE
    `nama` = VALUES(`nama`),
    `jabatan` = VALUES(`jabatan`),
    `kelas` = VALUES(`kelas`),
    `jurusan` = VALUES(`jurusan`),
    `is_active` = VALUES(`is_active`);

-- 3. Insert / Update Data Siswa (35 Siswa)
-- Password default semua siswa: siswa123
INSERT INTO `users` (`nama`, `email`, `password`, `jabatan`, `nis_nip`, `kelas`, `jurusan`, `is_active`)
VALUES
    ('ABYM REYNO TIRTA RAZKY', 'abym.reyno.tirta.razky@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '145312351092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('ACHMAD EKA ARDHIANSAH', 'achmad.eka.ardhiansah@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '150972494092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('ADE INDIRA ALFIA PUTRI', 'ade.indira.alfia.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '150982495092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('AHMAD FAISHAL BARI', 'ahmad.faishal.bari@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151012498092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('ALEXANDRA KEVINA AURORA PUTRI', 'alexandra.kevina.aurora.putri@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151062503092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('AQILLAH MARSYA MAYGISABILA', 'aqillah.marsya.maygisabila@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151122509092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('ARKA REYZA ALAMSHAH', 'arka.reyza.alamshah@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151142511092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('BIMA PUTRA RAMADHAN', 'bima.putra.ramadhan@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151232520092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('CLARISTA ANGGA ENI', 'clarista.angga.eni@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151312528092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('DHESTARA ALFAREZI SYAHPUTRA', 'dhestara.alfarezi.syahputra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151362533092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('DIAJENG TRI HERNANDA PRAMESWARI', 'diajeng.tri.hernanda.prameswari@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151372534092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('FATHIMAH AZZAHRA PUTRI WAHYUDA', 'fathimah.azzahra.putri.wahyuda@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151422539092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('HAIKAL ARKA NOVANSYAH', 'haikal.arka.novansyah@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151482545092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('HAWA QUINEFIRA SHER FATHAYA', 'hawa.quinefira.sher.fathaya@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151492546092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('JOSHUA JULIAN NICO', 'joshua.julian.nico@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151562553092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('JUVENA GLORY', 'juvena.glory@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151572554092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('LEONA AZARIA CELESTYN', 'leona.azaria.celestyn@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151632560092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MARSYELL', 'marsyell@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151682565092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MARVEL RADITYA SAPUTRA', 'marvel.raditya.saputra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151692566092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MUCH RIZKY RAHMAD DHANI', 'much.rizky.rahmad.dhani@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151762573092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MUHAMMAD ALIEF FIRDAUSYAH PUTRA', 'muhammad.alief.firdausyah.putra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151802577092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MUHAMMAD FARIS ABDILLAH', 'muhammad.faris.abdillah@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151842581092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('MUHAMMAD KENZIE CHAMELO PUTRA SUBEKTI', 'muhammad.kenzie.chamelo.putra.subekti@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151882585092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('NADIN MAULUDIYAH', 'nadin.mauludiyah@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151932590092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('NADYA EKA PUSPITASARI', 'nadya.eka.puspitasari@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151942591092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('NAJWA ANGGUN AZ ZAHRO', 'najwa.anggun.az.zahro@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151962593092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('NARENDRA SYAHREZA PRABASWARA', 'narendra.syahreza.prabaswara@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151972594092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('NICHAZA NAFALIA ANANTA', 'nichaza.nafalia.ananta@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '151992596092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('OKTAVIA PUTRI ANGGRAENI', 'oktavia.putri.anggraeni@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152012598092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('RAIHAN AKBAR SAPUTRA', 'raihan.akbar.saputra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152072604092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('RAVENSKA ALKHANSA SALWA', 'ravenska.alkhansa.salwa@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152102607092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('REVAN ARYA MAULANA', 'revan.arya.maulana@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152122609092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('RIZKY BAGUS KRISNA SAPUTRA', 'rizky.bagus.krisna.saputra@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152152612092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('SEILLA APRILLIA SARI', 'seilla.aprillia.sari@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152202617092', 'X Perhotelan 1', 'Perhotelan', 1),
    ('YULIA WULANDARI', 'yulia.wulandari@example.com', '$2y$12$fO1N7F0oU8jbze6gvGlfr.4aqnVq5misk5EataWn7mltq3NXA/IE6', 'Siswa', '152302627092', 'X Perhotelan 1', 'Perhotelan', 1)
ON DUPLICATE KEY UPDATE
    `nama` = VALUES(`nama`),
    `kelas` = VALUES(`kelas`),
    `jurusan` = VALUES(`jurusan`),
    `is_active` = VALUES(`is_active`);
