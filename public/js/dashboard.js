/* =========================================================
   Dashboard Absensi PKL - PT Sandya Network
   JavaScript: sidebar, dropdown, ripple, toast
   Filter/export/refresh kini ditangani server-side (Laravel).
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleSidebar');
    const closeBtn = document.getElementById('sidebarClose');
    const profileDropdownBtn = document.getElementById('profileDropdownBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    const notifBtn = document.getElementById('notifBtn');
    const mainWrapper = document.querySelector('.main-wrapper');

    /* Jika elemen inti tidak ada (misal dashboard siswa tanpa tabel), jangan error. */
    if (!sidebar) {
        return;
    }

    /* ---------- Sidebar Toggle (desktop: collapse / mobile: slide) ---------- */
    function isMobile() {
        return window.innerWidth <= 992;
    }

    function lockBodyScroll() {
        if (document.body) document.body.style.overflow = 'hidden';
    }

    function unlockBodyScroll() {
        if (document.body) document.body.style.overflow = '';
    }

    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('show');
        if (isMobile()) lockBodyScroll();
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        unlockBodyScroll();
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            if (isMobile()) {
                openSidebar();
            } else {
                if (sidebar) sidebar.classList.toggle('collapsed');
                if (mainWrapper) mainWrapper.classList.toggle('sidebar-collapsed');
            }
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    /* Tutup sidebar otomatis saat menu dipilih di layar mobile */
    if (sidebar) {
        var navLinks = sidebar.querySelectorAll('.sidebar-nav .nav-link');
        if (navLinks && navLinks.length) {
            navLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    if (isMobile() && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                });
            });
        }
    }

    /* Bersihkan state sidebar saat resize antar breakpoint */
    window.addEventListener('resize', function () {
        if (window.innerWidth > 992) {
            if (sidebar) sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('show');
            unlockBodyScroll();
        } else {
            if (sidebar) sidebar.classList.remove('collapsed');
            if (mainWrapper) mainWrapper.classList.remove('sidebar-collapsed');
        }
    });

    /* ---------- Profile Dropdown ---------- */
    if (profileDropdownBtn && profileDropdown) {
        profileDropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!profileDropdown.contains(e.target) && !profileDropdownBtn.contains(e.target)) {
                profileDropdown.classList.remove('open');
            }
        });
    }

    /* ---------- Ripple Effect ---------- */
    function createRipple(e) {
        const btn = e.currentTarget;
        const rect = btn.getBoundingClientRect();
        const ripple = document.createElement('span');
        const size = Math.max(rect.width, rect.height);

        ripple.className = 'ripple-effect';
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

        btn.appendChild(ripple);
        setTimeout(function () { ripple.remove(); }, 600);
    }

    document.querySelectorAll('.btn-tool, .chip, .icon-btn, .btn-logout, .stat-card').forEach(function (el) {
        if (el) el.addEventListener('click', createRipple);
    });

    /* ---------- Admin-only table elements — aman untuk siswa (tanpa tabel) ---------- */
    // Tabel aktivitas hanya dirender untuk admin. Untuk siswa, elemen-elemen ini tidak ada,
    // jadi semua akses harus dicek null agar tidak throw error.
    var tableBody = document.getElementById('tableBody');
    var tableLoader = document.getElementById('tableLoader');
    var skeletonEl = document.getElementById('skeleton');
    var tableWrapEl = document.getElementById('tableWrap');
    var searchInputEl = document.querySelector('.search-input');
    var paginationEl = document.querySelector('.pagination-links');
    // Jika tidak ada (siswa), tidak perlu inisialisasi — biarkan JS tetap berjalan tanpa error.
    if (tableBody || tableLoader || skeletonEl || tableWrapEl || searchInputEl || paginationEl) {
        // Placeholder untuk logic tabel admin di masa depan — saat ini aman walau elemen tidak ada.
    }

    /* ---------- Toast sederhana untuk aksi ---------- */
    function showToast(msg) {
        const existing = document.querySelector('.dashboard-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'dashboard-toast';
        toast.textContent = msg;
        document.body.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('show');
        }, 10);

        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 2200);
    }



    /* ---------- Modal Ganti Password (Menu User) ---------- */
    const passwordModal = document.getElementById('passwordModal');
    const passwordForm = document.getElementById('passwordForm');

    window.openPasswordModal = function (btn) {
        if (!btn) return;
        var userId = btn.getAttribute('data-user-id');
        var userName = btn.getAttribute('data-user-name');
        if (passwordModal && passwordForm) {
            var nameEl = document.getElementById('passwordUserName');
            if (nameEl) nameEl.textContent = userName;
            passwordForm.setAttribute('action', '/menu-user/' + userId + '/password');
            passwordModal.classList.add('show');
            try { passwordForm.reset(); } catch (e) {}
        }
    };

    window.closePasswordModal = function () {
        if (passwordModal) passwordModal.classList.remove('show');
    };

    if (passwordModal) {
        passwordModal.addEventListener('click', function (e) {
            if (e.target === passwordModal) closePasswordModal();
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && passwordModal && passwordModal.classList.contains('show')) {
            closePasswordModal();
        }
    });

    /* ---------- Konfirmasi Hapus (Menu User) ---------- */
    // Nama diambil dari data attribute agar aman terhadap karakter kutip.
    window.confirmDelete = function (event, form) {
        event.preventDefault();
        const name = form.getAttribute('data-user-name') || 'user ini';
        if (confirm('Yakin ingin menghapus user "' + name + '"?')) {
            form.submit();
        }
        return false;
    };
});
