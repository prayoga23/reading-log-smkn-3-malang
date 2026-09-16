/* ===== SHARED SIDEBAR & PROFILE CSS ===== */

/* SIDEBAR */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #0f4c81, #2563eb);
    padding: 20px 25px;
    color: white;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.sidebar-menu-area {
    flex: 1;
}

.sidebar-logo-box {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 16px;
    padding: 10px 16px;
    margin-bottom: 14px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.15);
}

.sidebar-logo-box img {
    height: 48px;
    width: auto;
    object-fit: contain;
    display: block;
}

.sidebar-logo-divider {
    width: 1px;
    height: 38px;
    background: rgba(0,0,0,0.15);
    border-radius: 2px;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 10px;
    font-size: 20px;
    line-height: 1.4;
}

.sidebar > p {
    text-align: center;
    font-size: 13px;
    opacity: 0.85;
    margin-bottom: 20px;
}

.menu button,
.nav-button {
    width: 100%;
    padding: 14px 15px;
    margin-bottom: 12px;
    border: none;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: 0.3s;
    text-align: left;
    display: block;
    text-decoration: none;
}

.menu button:hover,
.nav-button:hover,
.nav-button.active {
    background: white;
    color: #2563eb;
    transform: translateX(4px);
}

/* TOMBOL PROFIL */
.sidebar-profile-btn {
    width: 100%;
    padding: 13px 15px;
    border: 2px solid rgba(255,255,255,0.5);
    border-radius: 12px;
    background: rgba(255,255,255,0.12);
    color: white;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 8px;
}

.sidebar-profile-btn:hover {
    background: white;
    color: #2563eb;
    border-color: white;
}

/* MODAL PROFIL */
.profile-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 2000;
    align-items: center;
    justify-content: center;
}

.profile-modal-overlay.open {
    display: flex;
}

.profile-modal {
    background: white;
    border-radius: 20px;
    padding: 32px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    animation: pmSlideUp 0.3s ease;
    position: relative;
}

@keyframes pmSlideUp {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

.profile-modal h2 {
    font-size: 22px;
    color: #0f4c81;
    margin-bottom: 6px;
}

.profile-modal .pm-subtitle {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 24px;
}

.pm-close {
    position: absolute;
    top: 16px;
    right: 20px;
    background: none;
    border: none;
    font-size: 26px;
    cursor: pointer;
    color: #94a3b8;
    line-height: 1;
}

.pm-close:hover { color: #dc2626; }

.pm-group {
    margin-bottom: 16px;
}

.pm-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: #334155;
    margin-bottom: 6px;
}

.pm-group input {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.pm-group input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}

.pm-divider {
    border: none;
    border-top: 1px solid #e2e8f0;
    margin: 18px 0;
}

.pm-note {
    font-size: 12px;
    color: #94a3b8;
    margin-bottom: 12px;
}

.pm-submit {
    width: 100%;
    padding: 13px;
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 4px;
}

.pm-submit:hover { opacity: 0.9; transform: translateY(-1px); }

.pm-alert {
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 14px;
    display: none;
}

.pm-alert.success { background: #dcfce7; color: #166534; }
.pm-alert.error   { background: #fee2e2; color: #991b1b; }

/* RESPONSIVE SIDEBAR */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
