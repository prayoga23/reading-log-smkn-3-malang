<?php
// $profile_nama: nama yang ditampilkan di field input, harus di-escape sebelum dikirim
// Contoh: $profile_nama = esc($siswa['nama'] ?? '');
if (!isset($profile_nama)) {
    $profile_nama = '';
}
?>
<!-- MODAL PROFIL -->
<div class="profile-modal-overlay" id="profileModalOverlay" onclick="closeProfileModal(event)">
    <div class="profile-modal">
        <button class="pm-close" onclick="closeProfileModal()">&times;</button>
        <h2>&#128100; Profil Saya</h2>
        <p class="pm-subtitle">Perbarui nama dan password akun Anda</p>

        <div class="pm-alert" id="pmAlert"></div>

        <div class="pm-group">
            <label>Nama Lengkap</label>
            <input type="text" id="pmNama" placeholder="Nama lengkap" value="<?php echo $profile_nama; ?>">
        </div>

        <hr class="pm-divider">
        <p class="pm-note">Kosongkan password jika tidak ingin menggantinya.</p>

        <div class="pm-group">
            <label>Password Baru</label>
            <input type="password" id="pmPassword" placeholder="Password baru (min. 6 karakter)">
        </div>

        <div class="pm-group">
            <label>Konfirmasi Password</label>
            <input type="password" id="pmConfirm" placeholder="Ulangi password baru">
        </div>

        <button class="pm-submit" onclick="submitProfile()">Simpan Perubahan</button>
    </div>
</div>
