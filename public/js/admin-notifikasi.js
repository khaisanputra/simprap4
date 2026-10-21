/* Admin Notifikasi - HANYA untuk admin, tidak mengganggu siswa */
function initAdminNotifications() {
    if (window._adminNotifInitialized) return;
    window._adminNotifInitialized = true;
    var notifBtn = document.getElementById('notifBtn');
    if (!notifBtn) return;

    // Bell hanya untuk ADMIN — Blade sudah hanya render untuk admin, cek DOM sebagai safety
    var roleEl = document.querySelector('.profile-role');
    if (roleEl) {
        var roleText = roleEl.textContent.trim().toLowerCase();
        var isAdminDom = roleText.indexOf('administrator') !== -1 || roleText.indexOf('admin') !== -1;
        if (!isAdminDom) {
            notifBtn.style.display = 'none';
            return;
        }
    }

    // Muat CSS jika belum ada (hanya admin)
    if (!document.querySelector('link[href*="admin-notifikasi.css"]')) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/css/admin-notifikasi.css?v=' + Date.now();
        document.head.appendChild(link);
    }

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : null;
    // Fallback ambil dari input hidden _token di halaman (jika ada form logout)
    if (!csrfToken) {
        var tokenInput = document.querySelector('input[name="_token"]');
        if (tokenInput) csrfToken = tokenInput.value;
    }

    var wrap = notifBtn.closest('.notif-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'notif-wrap';
        notifBtn.parentNode.insertBefore(wrap, notifBtn);
        wrap.appendChild(notifBtn);
    }

    // Badge numeric (ganti dot) — jangan duplikat
    var badgeDot = notifBtn.querySelector('.badge-dot');
    var badge = notifBtn.querySelector('.notif-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'notif-badge';
        badge.hidden = true;
        badge.textContent = '0';
        notifBtn.appendChild(badge);
    }

    var dropdown = document.getElementById('notifDropdown');
    if (!dropdown) {
        dropdown = document.createElement('div');
        dropdown.id = 'notifDropdown';
        dropdown.className = 'notif-dropdown';
        dropdown.setAttribute('aria-hidden', 'true');
        dropdown.innerHTML = '' +
            '<div class="notif-dropdown-head">' +
                '<h4><i class="fa-solid fa-bell" style="color:#0F4C81"></i> Notifikasi</h4>' +
                '<button type="button" id="notifMarkAll">Tandai semua dibaca</button>' +
            '</div>' +
            '<div class="notif-list" id="notifList"><div class="notif-empty"><i class="fa-solid fa-spinner fa-spin"></i> Memuat...</div></div>' +
            '<div class="notif-foot" style="justify-content:flex-end;">' +
                '<button type="button" id="notifCloseBtn">Tutup</button>' +
            '</div>';
        // Sisipkan di dalam wrapper bell, bukan di navbar-right
        wrap.appendChild(dropdown);
    }

    var listEl = dropdown.querySelector('#notifList');
    var markAllBtn = dropdown.querySelector('#notifMarkAll');
    var closeBtn = dropdown.querySelector('#notifCloseBtn');

    function isAdminError(status) { return status === 403 || status === 401; }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = false;
            if (badgeDot) badgeDot.style.display = 'none';
        } else {
            badge.hidden = true;
            if (badgeDot) badgeDot.style.display = 'none';
        }
    }

    function fetchWithCsrf(url, opts) {
        opts = opts || {};
        opts.headers = opts.headers || {};
        if (csrfToken) opts.headers['X-CSRF-TOKEN'] = csrfToken;
        opts.headers['X-Requested-With'] = 'XMLHttpRequest';
        return fetch(url, opts);
    }

    function loadUnreadCount() {
        fetchWithCsrf('/admin/notifikasi/unread-count', { method: 'GET' })
            .then(function (r) {
                if (isAdminError(r.status)) {
                    // Bukan admin -> sembunyikan bell
                    notifBtn.style.display = 'none';
                    if (dropdown) dropdown.style.display = 'none';
                    throw new Error('not admin');
                }
                if (!r.ok) throw new Error('fetch unread fail');
                return r.json();
            })
            .then(function (j) {
                updateBadge(j.count || 0);
                // Jika admin, pastikan tombol terlihat
                notifBtn.style.display = '';
            })
            .catch(function (e) {
                if (e && e.message === 'not admin') return;
                // diamkan, biarkan badge hidden
            });
    }

    function typeIcon(type) {
        if (type === 'izin') return 'fa-envelope-open-text';
        if (type === 'dispensasi') return 'fa-file-circle-check';
        if (type === 'sakit') return 'fa-house-medical';
        if (type === 'alpha') return 'fa-user-xmark';
        return 'fa-bell';
    }
    function typeColor(type) {
        if (type === 'izin') return '#EAB308';
        if (type === 'dispensasi') return '#F97316';
        if (type === 'sakit') return '#3B82F6';
        if (type === 'alpha') return '#EF4444';
        return '#64748B';
    }
    function typeBg(type) {
        if (type === 'izin') return '#FEFCE8';
        if (type === 'dispensasi') return '#FFF7ED';
        if (type === 'sakit') return '#EFF6FF';
        if (type === 'alpha') return '#FEF2F2';
        return '#F8FAFC';
    }

    function renderList(items) {
        if (!items || !items.length) {
            listEl.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-inbox"></i>Tidak ada notifikasi</div>';
            return;
        }
        var html = '';
        items.forEach(function (it) {
            var unreadCls = it.is_read ? '' : ' unread';
            var dot = it.is_read ? '' : '<span class="dot-unread"></span>';
            var time = it.created_time ? it.created_time + ' WIB' : it.created_human;
            html += '<a href="' + it.link + '" class="notif-item' + unreadCls + '" data-id="' + it.id + '">' +
                '<div class="notif-icon" style="background:' + typeBg(it.type) + '; color:' + typeColor(it.type) + '; border-color:' + typeColor(it.type) + '20"><i class="fa-solid ' + typeIcon(it.type) + '"></i></div>' +
                '<div class="notif-content">' +
                    '<div class="notif-title">' + dot + '<span>' + it.title + '</span></div>' +
                    '<div class="notif-msg">' + it.message + '</div>' +
                    '<div class="notif-meta"><span><i class="fa-regular fa-clock"></i> ' + time + '</span><span>•</span><span>' + it.tanggal_fmt + '</span></div>' +
                '</div>' +
            '</a>';
        });
        listEl.innerHTML = html;

        // Bind klik item -> mark as read lalu navigasi (jamin request selesai sebelum pindah)
        listEl.querySelectorAll('.notif-item').forEach(function (el) {
            el.addEventListener('click', function (e) {
                // Hanya intercept klik kiri tanpa modifier; biarkan ctrl/middle click default
                if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
                var id = el.getAttribute('data-id');
                var href = el.getAttribute('href');
                if (!id || !href) return;
                e.preventDefault();
                var navigated = false;
                var doNav = function() {
                    if (!navigated) {
                        navigated = true;
                        window.location.href = href;
                    }
                };
                var url = '/admin/notifikasi/' + id + '/read';
                // 1. Coba sendBeacon jika cocok (POST dengan _token)
                var beaconOk = false;
                if (navigator.sendBeacon && csrfToken) {
                    try {
                        var fd = new FormData();
                        fd.append('_token', csrfToken);
                        beaconOk = navigator.sendBeacon(url, fd);
                    } catch (err) { beaconOk = false; }
                }
                if (beaconOk) {
                    // Beacon queued, beri jeda singkat lalu navigasi
                    setTimeout(doNav, 80);
                } else {
                    // 2. Fallback fetch keepalive yang menunggu sebelum navigasi
                    fetchWithCsrf(url, { method: 'POST', keepalive: true })
                        .then(doNav, doNav)
                        .catch(doNav);
                    setTimeout(doNav, 1200);
                }
            });
        });
    }

    function loadDropdown() {
        listEl.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-spinner fa-spin"></i> Memuat...</div>';
        fetchWithCsrf('/admin/notifikasi/dropdown', { method: 'GET' })
            .then(function (r) {
                if (isAdminError(r.status)) { notifBtn.style.display = 'none'; throw new Error('not admin'); }
                if (!r.ok) throw new Error('dropdown fail');
                return r.json();
            })
            .then(function (j) {
                updateBadge(j.unread || 0);
                renderList(j.items || []);
            })
            .catch(function () {
                listEl.innerHTML = '<div class="notif-empty"><i class="fa-solid fa-triangle-exclamation"></i>Gagal memuat notifikasi</div>';
            });
    }

    function openDropdown() {
        dropdown.classList.add('open');
        dropdown.setAttribute('aria-hidden', 'false');
        loadDropdown();
    }
    function closeDropdown() {
        dropdown.classList.remove('open');
        dropdown.setAttribute('aria-hidden', 'true');
    }

    notifBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (dropdown.classList.contains('open')) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            fetchWithCsrf('/admin/notifikasi/read-all', { method: 'POST' })
                .then(function (r) {
                    if (r.ok) {
                        updateBadge(0);
                        loadDropdown();
                        // Update list visual
                        listEl.querySelectorAll('.notif-item.unread').forEach(function (el) {
                            el.classList.remove('unread');
                            var dot = el.querySelector('.dot-unread');
                            if (dot) dot.remove();
                        });
                    }
                });
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            closeDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            closeDropdown();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDropdown();
    });

    // Polling ringan tiap 60 detik untuk badge
    loadUnreadCount();
    setInterval(loadUnreadCount, 60000);
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminNotifications);
} else {
    initAdminNotifications();
}
