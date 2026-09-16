/* ===== PROFIL MODAL ===== */
function openProfileModal() {
    document.getElementById('profileModalOverlay').classList.add('open');
}

function closeProfileModal(e) {
    if (!e || e.target === document.getElementById('profileModalOverlay')) {
        document.getElementById('profileModalOverlay').classList.remove('open');
        document.getElementById('pmPassword').value = '';
        document.getElementById('pmConfirm').value = '';
        const al = document.getElementById('pmAlert');
        al.style.display = 'none';
        al.className = 'pm-alert';
    }
}

function submitProfile() {
    const nama     = document.getElementById('pmNama').value.trim();
    const password = document.getElementById('pmPassword').value;
    const confirm  = document.getElementById('pmConfirm').value;

    if (!nama)                            { showPmAlert('error', 'Nama tidak boleh kosong.'); return; }
    if (password && password !== confirm)  { showPmAlert('error', 'Konfirmasi password tidak cocok.'); return; }
    if (password && password.length < 6)   { showPmAlert('error', 'Password minimal 6 karakter.'); return; }

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('new_nama', nama);
    fd.append('new_password', password);
    fd.append('confirm_password', confirm);

    fetch('', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showPmAlert('success', data.message);
                const namaInput = document.getElementById('nama');
                if (namaInput) namaInput.value = data.new_nama;
                document.getElementById('pmPassword').value = '';
                document.getElementById('pmConfirm').value = '';
            } else {
                showPmAlert('error', data.message || 'Terjadi kesalahan.');
            }
        })
        .catch(() => showPmAlert('error', 'Gagal menghubungi server.'));
}

function showPmAlert(type, msg) {
    const el = document.getElementById('pmAlert');
    el.textContent = msg;
    el.className = 'pm-alert ' + type;
    el.style.display = 'block';
}
