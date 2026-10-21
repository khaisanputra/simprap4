/* =========================================================
   Halaman Absen Pulang - Sistem Absensi PKL Sandya Network
   Jam realtime (WIB), kamera/selfie, Geolocation (GPS),
   kompas, dan submit absen.
   Waktu resmi & validasi akhir ditentukan oleh server Laravel.
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    var cfg   = window.ABSEN_CONFIG || {};
    var form  = document.getElementById('formAbsenPulang');
    var btn   = document.getElementById('btnAbsenPulang');
    var latF  = document.getElementById('latField');
    var lngF  = document.getElementById('lngField');
    var accF  = document.getElementById('accuracyField');
    var fotoF = document.getElementById('fotoField');
    var hint  = document.getElementById('absenHint');

    var locStatusEl = document.getElementById('locStatus');
    var latValEl    = document.getElementById('latValue');
    var lngValEl    = document.getElementById('lngValue');
    var accValEl    = document.getElementById('accuracyValue');
    var distEl      = document.getElementById('jarakValue');
    var radiusEl    = document.getElementById('radiusStatus');

    var submitting  = false;
    var bolehAbsen  = btn ? btn.getAttribute('data-boleh-absen') === '1' : false;
    // Deteksi status exempt (Izin/Dispensasi/Sakit) — tidak perlu pulang
    var isExempt = !!cfg.isSelesaiTanpaPulang || !!cfg.isExempt;
    var statusKehadiran = cfg.statusKehadiran || (btn ? btn.getAttribute('data-status-kehadiran') : '') || '';

    // Jika exempt, jangan inisialisasi GPS/kamera dan biarkan banner selesai
    if (isExempt && hint) {
        // Tampilkan pesan exempt jika belum ada
        // hint sudah diisi server, tapi pastikan tidak tertimpa
    }

    /* ================= JAM REALTIME (WIB, sinkron server) ================= */
    var clockEl  = document.getElementById('clockTime');
    var clockSec = document.getElementById('clockSec');
    var offset = 0;
    if (cfg.serverTime) {
        try {
            var serverMsTmp = new Date(cfg.serverTime).getTime();
            offset = serverMsTmp - Date.now();
        } catch (e) { offset = 0; }
    }
    if (cfg.serverTime && clockEl) {

        function renderClock() {
            var d = new Date(Date.now() + offset);
            var hh = String(d.getHours()).padStart(2, '0');
            var mm = String(d.getMinutes()).padStart(2, '0');
            var ss = String(d.getSeconds()).padStart(2, '0');
            clockEl.textContent = hh + ':' + mm;
            if (clockSec) clockSec.textContent = ':' + ss;
        }
        renderClock();
        setInterval(renderClock, 1000);
    }

    /* ============ STATUS JAM ABSEN PULANG (WIB) ============ */
    var waktuBannerTitle = document.getElementById('waktuBannerTitle');
    var waktuBannerSub   = document.getElementById('waktuBannerSub');
    var waktuCountdown   = document.getElementById('waktuCountdown');

    // Jendela FIXED: 17:00 - 23:00 (dari server Laravel — hanya tampilan).
    // Semua siswa memiliki waktu mulai sama 17:00.
    var mulaiMin   = cfg.jamMulai ? jamKeMenit(cfg.jamMulai) : null;
    var selesaiMin = cfg.jamSelesai ? jamKeMenit(cfg.jamSelesai) : 1380;

    function jamKeMenit(str) {
        var p = String(str).split(':');
        return (+p[0]) * 60 + (+p[1]);
    }

    function serverNowMs() {
        return new Date(Date.now() + offset);
    }

    function duaDigit(n) {
        return String(n).padStart(2, '0');
    }

    function perbaruiWaktuStatus() {
        // Jika exempt, jangan ubah banner selesai
        if (isExempt) return;
        if (!waktuBannerTitle || !cfg.serverTime) return;
        if (mulaiMin === null) return; // belum absen masuk / jendela tak tersedia

        var d = serverNowMs();
        var menit = d.getHours() * 60 + d.getMinutes() + d.getSeconds() / 60;

        var status = 'buka';
        if (menit < mulaiMin) status = 'belum';
        else if (menit > selesaiMin) status = 'tutup';

        cfg.waktuStatus = status;

        var title, sub;

        if (status === 'belum') {
            title = 'Absen Pulang belum dibuka.';
            sub = 'Anda dapat melakukan Absen Pulang mulai pukul ' + cfg.jamMulai + ' WIB.';

            var target = new Date(d);
            target.setHours(Math.floor(mulaiMin / 60), mulaiMin % 60, 0, 0);
            var diff = Math.max(0, target - d);
            var hh = Math.floor(diff / 3600000);
            var mm = Math.floor((diff % 3600000) / 60000);
            var ss = Math.floor((diff % 60000) / 1000);
            if (waktuCountdown) {
                waktuCountdown.textContent = 'Absen Pulang tersedia dalam ' + duaDigit(hh) + ':' + duaDigit(mm) + ':' + duaDigit(ss);
            }
        } else if (status === 'tutup') {
            title = 'Absen Pulang sudah ditutup.';
            sub = 'Batas waktu Absen Pulang adalah pukul ' + cfg.jamSelesai + ' WIB.';
            if (waktuCountdown) waktuCountdown.textContent = 'Jam Absen Pulang: ' + cfg.jamMulai + ' - ' + cfg.jamSelesai + ' WIB';
        } else {
            title = 'Absen Pulang sudah dibuka.';
            sub = 'Silakan lengkapi foto dan GPS, lalu tekan tombol Absen Pulang.';
            if (waktuCountdown) waktuCountdown.textContent = 'Jam Absen Pulang: ' + cfg.jamMulai + ' - ' + cfg.jamSelesai + ' WIB';
        }

        waktuBannerTitle.textContent = title;
        waktuBannerSub.textContent = sub;

        evaluateAbsen();
    }

    if (cfg.serverTime) {
        setInterval(perbaruiWaktuStatus, 1000);
        perbaruiWaktuStatus();
    }

    /* ================= FUNGSI BANTU ================= */
    function setLocState(cls, text) {
        if (!locStatusEl) return;
        locStatusEl.className = 'loc-status ' + cls;
        locStatusEl.innerHTML = '<span class="dot"></span><span>' + text + '</span>';
    }

    function haversineMeters(lat1, lng1, lat2, lng2) {
        var R = 6371000;
        var toRad = function (x) { return x * Math.PI / 180; };
        var dLat = toRad(lat2 - lat1);
        var dLng = toRad(lng2 - lng1);
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function setHint(text) {
        if (hint) hint.textContent = text;
    }

     /* Tombol absen hanya aktif jika SEMUA syarat wajib terpenuhi:
       waktu buka FIXED 17:00 - 23:00 WIB, GPS valid,
       dan foto tersedia. Validasi akhir tetap oleh server.
       Untuk Izin/Dispensasi/Sakit, tombol tidak perlu aktif. */
    function evaluateAbsen() {
        if (!btn) return;
        if (isExempt) {
            btn.disabled = true;
            return;
        }
        if (!bolehAbsen) {
            btn.disabled = true;
            return;
        }
        var waktuOk = cfg.waktuStatus === 'buka';
        var gpsOk = !cfg.gpsWajib || locationOk;
        var hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
        var fotoOk = !cfg.fotoWajib || (fotoReady && hasFotoInForm);
        btn.disabled = !(waktuOk && gpsOk && fotoOk);
    }

    /* ================= KAMERA / SELFIE ================= */
    var camStatus    = document.getElementById('cameraStatus');
    var camVideo     = document.getElementById('cameraVideo');
    var camEmpty     = document.getElementById('cameraEmpty');
    var fotoPreview  = document.getElementById('fotoPreview');
    var btnAmbil     = document.getElementById('btnAmbilFoto');
    var btnUlang     = document.getElementById('btnAmbilUlang');
    var labelFile    = document.getElementById('labelPilihFile');
    var inputFile    = document.getElementById('inputPilihFile');
    var fallbackWrap = document.getElementById('fallbackFotoWrap');

    var fotoReady = false;
    var fotoFile  = null;
    var stream    = null;

    var FOTO_MAX_DIM = (window.FotoHelper && FotoHelper.MAX_DIM) ? FotoHelper.MAX_DIM : 1600;
    var FOTO_QUALITY = (window.FotoHelper && FotoHelper.QUALITY) ? FotoHelper.QUALITY : 0.82;

    function setCamState(cls, text) {
        if (!camStatus) return;
        camStatus.className = 'cam-status ' + cls;
        camStatus.innerHTML = '<span class="dot"></span><span>' + text + '</span>';
    }

    function stopStream() {
        if (stream) {
            try {
                stream.getTracks().forEach(function (t) { try { t.stop(); } catch(e){} });
            } catch(e){}
            stream = null;
        }
    }

    function showVideo() {
        if (camVideo) {
            camVideo.style.display = 'block';
            camVideo.hidden = false;
        }
        if (camEmpty) camEmpty.style.display = 'none';
        if (fotoPreview) {
            fotoPreview.hidden = true;
            fotoPreview.removeAttribute('src');
        }
        if (btnAmbil) btnAmbil.hidden = false;
        if (btnUlang) btnUlang.hidden = true;
        if (fallbackWrap) fallbackWrap.style.display = 'none';
    }

    function showFotoPreviews(dataUrl) {
        if (camVideo) camVideo.style.display = 'none';
        if (fotoPreview) {
            fotoPreview.hidden = false;
            fotoPreview.src = dataUrl;
        }
        if (btnAmbil) btnAmbil.hidden = true;
        if (btnUlang) btnUlang.hidden = false;
    }

    function showFallbackOption(show) {
        if (fallbackWrap) {
            fallbackWrap.style.display = show ? 'block' : 'none';
        }
        if (labelFile) labelFile.hidden = !show;
    }

    function setFotoFile(file) {
        fotoFile  = file;
        fotoReady = !!file;
        try { evaluateAbsen(); } catch(e){}
    }

    function hitungDimensiBaru(w, h, max) {
        if (window.FotoHelper && FotoHelper.hitungDimensiBaru) return FotoHelper.hitungDimensiBaru(w, h, max);
        max = max || FOTO_MAX_DIM;
        if (w <= max && h <= max) return {w:w, h:h};
        var scale = Math.min(max / w, max / h);
        return {w: Math.max(1, Math.round(w * scale)), h: Math.max(1, Math.round(h * scale))};
    }

    function injectFotoKeForm(file) {
        if (!fotoF) {
            setCamState('state-error', 'Elemen foto tidak ditemukan. Muat ulang halaman.');
            return false;
        }
        if (window.FotoHelper && FotoHelper.injectFilesToInput) {
            try {
                var okHelper = FotoHelper.injectFilesToInput(fotoF, [file]);
                if (okHelper) return true;
            } catch (e) {}
        }
        try {
            var dt = null;
            if (typeof DataTransfer !== 'undefined') {
                try { dt = new DataTransfer(); } catch (e) { dt = null; }
            }
            if (!dt && typeof ClipboardEvent !== 'undefined') {
                try {
                    var ce = new ClipboardEvent('');
                    if (ce.clipboardData) dt = ce.clipboardData;
                } catch (e) {}
            }
            if (!dt) {
                try { dt = new DataTransfer(); } catch (e2) {}
            }
            if (!dt || !dt.items) {
                setCamState('state-error', 'Browser tidak mendukung upload foto otomatis. Gunakan browser terbaru (Chrome/Firefox).');
                return false;
            }
            dt.items.add(file);
            if (!dt.files || dt.files.length === 0) {
                setCamState('state-error', 'Gagal menyiapkan file foto (DataTransfer kosong).');
                return false;
            }
            try {
                fotoF.files = dt.files;
            } catch (e) {
                try {
                    Object.defineProperty(fotoF, 'files', { value: dt.files, writable: false });
                } catch (e2) {
                    setCamState('state-error', 'Gagal menyiapkan foto untuk dikirim (file list read-only).');
                    return false;
                }
            }
            if (!fotoF.files || fotoF.files.length === 0) {
                setCamState('state-error', 'File foto tidak masuk ke form. Silakan ambil ulang.');
                return false;
            }
            if (fotoF.files[0].size === 0) {
                setCamState('state-error', 'File foto kosong. Silakan ambil ulang.');
                return false;
            }
            if (!fotoF.files[0].type || fotoF.files[0].type.indexOf('image/') !== 0) {
                if (fotoF.files[0].size === 0) {
                    setCamState('state-error', 'File foto tidak valid. Silakan ambil ulang.');
                    return false;
                }
            }
            return true;
        } catch (e) {
            setCamState('state-error', 'Gagal menyiapkan foto untuk dikirim.');
            return false;
        }
    }

    function prosesFileImageDenganResize(file, callback) {
        if (window.FotoHelper && FotoHelper.compressImageFile) {
            FotoHelper.compressImageFile(file, callback);
            return;
        }
        // fallback inline (same as absen.js)
        if (!file || !file.type || file.type.indexOf('image/') !== 0) {
            callback(null, 'File harus berupa gambar.');
            return;
        }
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function() {
            try {
                var dim = hitungDimensiBaru(img.naturalWidth || img.width, img.naturalHeight || img.height, FOTO_MAX_DIM);
                var canvas = document.createElement('canvas');
                canvas.width = dim.w;
                canvas.height = dim.h;
                var ctx = canvas.getContext('2d');
                if (!ctx) {
                    URL.revokeObjectURL(url);
                    callback(null, 'Gagal memproses foto (canvas tidak didukung).');
                    return;
                }
                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, dim.w, dim.h);
                ctx.drawImage(img, 0, 0, dim.w, dim.h);
                URL.revokeObjectURL(url);
                if (!canvas.toBlob) {
                    try {
                        var dataUrlFallback = canvas.toDataURL('image/jpeg', FOTO_QUALITY);
                        var bin = atob(dataUrlFallback.split(',')[1]);
                        var arr = new Uint8Array(bin.length);
                        for (var i=0;i<bin.length;i++) arr[i]=bin.charCodeAt(i);
                        var blobFallback = new Blob([arr], {type:'image/jpeg'});
                        if (!blobFallback || blobFallback.size===0) {
                            callback(null, 'Gagal memproses foto (blob kosong).');
                            return;
                        }
                        var fileOut = new File([blobFallback], 'attendance.jpg', {type:'image/jpeg'});
                        callback(fileOut, null);
                    } catch(e) {
                        callback(null, 'Gagal kompresi foto.');
                    }
                    return;
                }
                canvas.toBlob(function(blob){
                    if (!blob) {
                        callback(null, 'Gagal memproses foto (blob kosong).');
                        return;
                    }
                    if (blob.size === 0) {
                        callback(null, 'Hasil foto kosong.');
                        return;
                    }
                    try {
                        var fileOut2 = new File([blob], 'attendance.jpg', {type:'image/jpeg'});
                        if (!fileOut2 || fileOut2.size===0) {
                            callback(null, 'Gagal membuat file foto.');
                            return;
                        }
                        callback(fileOut2, null);
                    } catch(e){
                        callback(null, 'Gagal membuat file foto.');
                    }
                }, 'image/jpeg', FOTO_QUALITY);
            } catch(e) {
                try { URL.revokeObjectURL(url); } catch(err){}
                callback(null, 'Gagal memproses foto.');
            }
        };
        img.onerror = function(){
            try { URL.revokeObjectURL(url); } catch(e){}
            callback(null, 'File gambar tidak dapat dibaca atau rusak.');
        };
        img.src = url;
    }

    if (inputFile) {
        inputFile.addEventListener('change', function () {
            if (!inputFile.files || !inputFile.files.length) return;
            var rawFile = inputFile.files[0];
            if (cfg.maxFotoKb && rawFile.size > cfg.maxFotoKb * 1024) {
                setCamState('state-error', 'Ukuran foto terlalu besar. Maksimal 10 MB.');
                setFotoFile(null);
                if (fotoF) fotoF.value = '';
                return;
            }
            setCamState('state-loading', 'Memproses foto...');
            prosesFileImageDenganResize(rawFile, function(fileResized, errMsg){
                if (errMsg || !fileResized) {
                    setCamState('state-error', errMsg || 'Gagal memproses foto. Silakan coba lagi.');
                    setFotoFile(null);
                    if (fotoF) fotoF.value = '';
                    try { evaluateAbsen(); } catch(e){}
                    return;
                }
                var ok = injectFotoKeForm(fileResized);
                if (!ok) {
                    setCamState('state-error', 'Gagal menyiapkan foto untuk dikirim. Silakan pilih ulang.');
                    setFotoFile(null);
                    if (fotoF) fotoF.value = '';
                    try { evaluateAbsen(); } catch(e){}
                    return;
                }
                var previewUrl;
                try { previewUrl = URL.createObjectURL(fileResized); } catch(e){ previewUrl = ''; }
                if (previewUrl) showFotoPreviews(previewUrl);
                setCamState('state-found', 'Foto dipilih. Ukuran: ' + (fileResized.size/1024).toFixed(0) + ' KB');
                setFotoFile(fileResized);
                try { evaluateAbsen(); } catch(e){}
            });
        });
    }
    if (labelFile && inputFile) {
        labelFile.addEventListener('click', function(){ try { inputFile.click(); } catch(e){} });
    }

    function initKamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setCamState('state-error', 'Browser tidak mendukung kamera. Silakan gunakan browser terbaru (Chrome/Firefox) atau pilih foto dari perangkat.');
            if (camEmpty) camEmpty.style.display = 'flex';
            showFallbackOption(true);
            try { evaluateAbsen(); } catch(e){}
            return;
        }
        setCamState('state-loading', 'Meminta izin kamera...');
        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        }).then(function (s) {
            stream = s;
            var video = camVideo;
            if (!video) return;
            video.srcObject = s;
            video.onloadedmetadata = function() {
                showVideo();
                setCamState('state-found', 'Kamera aktif. Ambil foto selfie Anda.');
                try { evaluateAbsen(); } catch(e){}
            };
            var playPromise = video.play();
            if (playPromise && playPromise.catch) {
                playPromise.catch(function(err){
                    showVideo();
                    setCamState('state-found', 'Kamera aktif. Jika preview hitam, muat ulang halaman.');
                    try { evaluateAbsen(); } catch(e){}
                });
            } else {
                showVideo();
                setCamState('state-found', 'Kamera depan aktif. Ambil foto selfie Anda.');
                try { evaluateAbsen(); } catch(e){}
            }
        }).catch(function (err) {
            stopStream();
            var name = err && err.name ? err.name : '';
            var msg = '';
            if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
                msg = 'Izin kamera ditolak. Silakan izinkan akses kamera di pengaturan browser, lalu muat ulang halaman.';
            } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
                msg = 'Kamera tidak ditemukan. Pastikan perangkat memiliki kamera. Anda dapat pilih foto dari perangkat.';
            } else if (name === 'NotReadableError' || name === 'TrackStartError') {
                msg = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi lain dan coba lagi, atau pilih foto dari perangkat.';
            } else if (name === 'OverconstrainedError') {
                msg = 'Kamera tidak mendukung pengaturan yang diminta. Coba gunakan browser lain atau pilih foto dari perangkat.';
            } else if (name === 'SecurityError') {
                msg = 'Akses kamera diblokir keamanan browser. Pastikan menggunakan HTTPS dan izinkan kamera.';
            } else {
                if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                    msg = 'Kamera memerlukan koneksi aman (HTTPS). Silakan akses via HTTPS atau pilih foto dari perangkat.';
                } else {
                    msg = 'Kamera tidak dapat digunakan. Silakan berikan izin kamera, muat ulang halaman, atau pilih foto dari perangkat.';
                }
            }
            setCamState('state-error', msg);
            if (camEmpty) camEmpty.style.display = 'flex';
            showFallbackOption(true);
            try { evaluateAbsen(); } catch(e){}
        });
    }

    function ambilFoto() {
        var video = camVideo;
        if (!video || !video.videoWidth || !video.videoHeight) {
            setCamState('state-error', 'Kamera belum siap. Tunggu sebentar lalu coba lagi.');
            return;
        }
        var origW = video.videoWidth;
        var origH = video.videoHeight;
        if (!origW || !origH) {
            setCamState('state-error', 'Gagal mengambil gambar dari kamera (dimensi tidak valid).');
            return;
        }
        var dim = hitungDimensiBaru(origW, origH, FOTO_MAX_DIM);
        var canvas = document.createElement('canvas');
        canvas.width  = dim.w;
        canvas.height = dim.h;
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            setCamState('state-error', 'Gagal memproses foto (canvas tidak didukung browser).');
            return;
        }
        try {
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, dim.w, dim.h);
            ctx.drawImage(video, 0, 0, dim.w, dim.h);
        } catch (e) {
            setCamState('state-error', 'Gagal mengambil gambar dari kamera. Silakan coba lagi.');
            return;
        }
        if (!canvas.toBlob) {
            try {
                var dataUrlFallback = canvas.toDataURL('image/jpeg', FOTO_QUALITY);
                var bin = atob(dataUrlFallback.split(',')[1]);
                var arr = new Uint8Array(bin.length);
                for (var i=0;i<bin.length;i++) arr[i]=bin.charCodeAt(i);
                var blobFallback = new Blob([arr], {type:'image/jpeg'});
                if (!blobFallback || blobFallback.size===0) {
                    setCamState('state-error', 'Gagal memproses foto (hasil kamera kosong). Silakan coba lagi.');
                    return;
                }
                var fileFallback;
                try { fileFallback = new File([blobFallback], 'attendance.jpg', {type:'image/jpeg'}); } catch (e) {
                    setCamState('state-error', 'Gagal membuat file foto. Gunakan browser terbaru (Chrome/Firefox).');
                    return;
                }
                if (!fileFallback || fileFallback.size===0) {
                    setCamState('state-error', 'Gagal membuat file foto. Silakan coba lagi.');
                    return;
                }
                var okFb = injectFotoKeForm(fileFallback);
                if (!okFb) {
                    setCamState('state-error', 'Gagal menyiapkan foto untuk dikirim. Silakan ambil ulang.');
                    setFotoFile(null);
                    if (fotoF) fotoF.value='';
                    return;
                }
                var previewUrlFb;
                try { previewUrlFb = URL.createObjectURL(blobFallback); } catch (e) {
                    setCamState('state-error', 'Gagal menampilkan preview foto.');
                    setFotoFile(null);
                    if (fotoF) fotoF.value='';
                    return;
                }
                showFotoPreviews(previewUrlFb);
                setCamState('state-found', 'Foto berhasil diambil. Ukuran: ' + (fileFallback.size/1024).toFixed(0) + ' KB');
                setFotoFile(fileFallback);
                stopStream();
                try { evaluateAbsen(); } catch(e){}
                return;
            } catch (e) {
                setCamState('state-error', 'Browser tidak mendukung kompresi foto. Gunakan browser terbaru (Chrome/Firefox).');
                return;
            }
        }
        canvas.toBlob(function (blob) {
            if (!blob) {
                setCamState('state-error', 'Gagal memproses foto (hasil kamera kosong). Silakan coba lagi.');
                return;
            }
            if (blob.size === 0) {
                setCamState('state-error', 'Hasil foto kosong. Silakan coba lagi.');
                return;
            }
            var file;
            try {
                file = new File([blob], 'attendance.jpg', { type: 'image/jpeg' });
            } catch (e) {
                setCamState('state-error', 'Gagal membuat file foto. Gunakan browser terbaru (Chrome/Firefox).');
                return;
            }
            if (!file || file.size === 0) {
                setCamState('state-error', 'Gagal membuat file foto. Silakan coba lagi.');
                return;
            }
            var ok = injectFotoKeForm(file);
            if (!ok) {
                setFotoFile(null);
                if (fotoF) fotoF.value = '';
                return;
            }
            var previewUrl;
            try {
                previewUrl = URL.createObjectURL(blob);
            } catch (e) {
                setCamState('state-error', 'Gagal menampilkan preview foto.');
                setFotoFile(null);
                if (fotoF) fotoF.value = '';
                return;
            }
            showFotoPreviews(previewUrl);
            setCamState('state-found', 'Foto berhasil diambil. Ukuran: ' + (file.size/1024).toFixed(0) + ' KB');
            setFotoFile(file);
            stopStream();
            try { evaluateAbsen(); } catch(e){}
        }, 'image/jpeg', FOTO_QUALITY);
    }

    if (btnAmbil) btnAmbil.addEventListener('click', function(){ try { ambilFoto(); } catch(e){ setCamState('state-error','Gagal mengambil foto. Silakan coba lagi.'); } });
    if (btnUlang) btnUlang.addEventListener('click', function () {
        setFotoFile(null);
        if (fotoF) fotoF.value = '';
        try { if (fotoPreview) { fotoPreview.removeAttribute('src'); fotoPreview.hidden = true; } } catch(e){}
        showVideo();
        initKamera();
        try { evaluateAbsen(); } catch(e){}
    });
    // Jangan inisialisasi kamera jika exempt atau tidak boleh absen (elemen tidak ada)
    if (isExempt) {
        if (camStatus) camStatus.className = 'cam-status state-found';
        // camera elements may not exist when exempt, skip
    } else if (document.getElementById('cameraVideo')) {
        initKamera();
    }

    /* ================= GEOLOCATION (GPS) ================= */
    var locationOk = false;
    var locationFound = false;

    function applyLocation(lat, lng, accuracy) {
        var accuracyNum = Number(accuracy);
        var dist = haversineMeters(
            Number(lat), Number(lng),
            Number(cfg.lat), Number(cfg.lng)
        );

        if (latF) latF.value = lat;
        if (lngF) lngF.value = lng;
        if (accF) accF.value = accuracy;

        if (latValEl) latValEl.textContent = Number(lat).toFixed(6);
        if (lngValEl) lngValEl.textContent = Number(lng).toFixed(6);
        if (accValEl) accValEl.textContent = '±' + accuracyNum.toFixed(0) + ' m';
        if (distEl) distEl.textContent = Math.round(dist) + ' m';

        var akurasiOk = !(cfg.maxAccuracy > 0) || accuracyNum <= cfg.maxAccuracy;
        var dalamRadius = dist <= cfg.radius;

        locationFound = true;
        locationOk = akurasiOk && dalamRadius;

        setLocState('state-found', 'Lokasi ditemukan.');

        if (radiusEl) {
            if (!akurasiOk) {
                radiusEl.className = 'radius-pill no';
                radiusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Akurasi lokasi belum cukup baik (±' + accuracyNum.toFixed(0) + ' m). Tunggu beberapa saat dan coba lagi.';
            } else if (dalamRadius) {
                radiusEl.className = 'radius-pill ok';
                radiusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Lokasi valid — Anda berada dalam Radius Kantor';
            } else {
                radiusEl.className = 'radius-pill no';
                radiusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Anda berada di luar Radius Kantor';
            }
        }

        evaluateAbsen();
    }

    if (isExempt) {
        if (locStatusEl) setLocState('state-found', 'Tidak perlu GPS untuk status ' + (statusKehadiran || 'Izin/Dispensasi/Sakit') + '.');
        if (radiusEl) {
            radiusEl.className = 'radius-pill ok';
            radiusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Tidak perlu validasi lokasi';
        }
    } else if (!bolehAbsen) {
        if (locStatusEl) {
            if (btn && btn.getAttribute('data-boleh-absen') === '0') {
                setLocState('state-error', 'Absen pulang tidak tersedia saat ini.');
            }
        }
    } else if (!('geolocation' in navigator)) {
        setLocState('state-error', 'Browser tidak mendukung Geolocation. Gunakan HP atau Chrome/Firefox terbaru dengan GPS.');
    } else {
        setLocState('state-loading', 'Mencari lokasi...');
        try {
            navigator.geolocation.watchPosition(
                function (pos) {
                    try {
                        applyLocation(
                            pos.coords.latitude,
                            pos.coords.longitude,
                            pos.coords.accuracy
                        );
                    } catch(e){ try { evaluateAbsen(); } catch(err){} }
                },
                function (err) {
                    try {
                        locationFound = false;
                        locationOk = false;
                        try { evaluateAbsen(); } catch(e){}
                        setLocState('state-error', lokasiErrorMessage(err && err.code));
                    } catch(e){
                        setLocState('state-error', 'Gagal mendapatkan lokasi. Coba lagi.');
                        try { evaluateAbsen(); } catch(err){}
                    }
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        } catch(e){
            setLocState('state-error', 'Gagal memulai pencarian lokasi. Pastikan GPS aktif dan berikan izin lokasi.');
            try { evaluateAbsen(); } catch(err){}
        }
    }

    function lokasiErrorMessage(code) {
        switch (code) {
            case 1: return 'Izin lokasi ditolak. Aktifkan izin GPS di pengaturan browser (ikon gembok) lalu muat ulang.';
            case 2: return 'Lokasi tidak tersedia. Pastikan GPS aktif atau coba gunakan HP.';
            case 3: return 'Waktu pencarian lokasi habis. Pastikan sinyal GPS baik dan coba lagi.';
            default: return 'Gagal mendapatkan lokasi. Pastikan GPS aktif dan coba lagi.';
        }
    }

    /* ================= SUBMIT ABSEN PULANG ================= */
    if (form && btn) {
        form.addEventListener('submit', function (e) {
            if (submitting) {
                e.preventDefault();
                return;
            }

            if (isExempt) {
                e.preventDefault();
                setHint('Absensi selesai — Anda tidak perlu melakukan Absen Pulang karena status Anda adalah ' + (statusKehadiran || 'Izin/Dispensasi/Sakit') + '.');
                return;
            }

            if (!bolehAbsen) {
                e.preventDefault();
                return;
            }

            // Waktu resmi tetap dari server; guard sisi klien hanya tampilan.
            if (cfg.waktuStatus === 'belum') {
                e.preventDefault();
                setHint('Absen Pulang belum dapat dilakukan. Anda dapat melakukan Absen Pulang mulai pukul ' + cfg.jamMulai + ' WIB.');
                return;
            }
            if (cfg.waktuStatus === 'tutup') {
                e.preventDefault();
                setHint('Absen Pulang sudah ditutup. Batas waktu Absen Pulang adalah pukul ' + cfg.jamSelesai + ' WIB.');
                return;
            }

            // FIX: reinject jika file di memori ada tapi belum di input (mobile DataTransfer edge case)
            var hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
            if (!hasFotoInForm && fotoFile && fotoFile.size > 0) {
                try {
                    var reinjected = injectFotoKeForm(fotoFile);
                    if (reinjected) {
                        hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
                        if (hasFotoInForm) {
                            fotoReady = true;
                            try { evaluateAbsen(); } catch(e){}
                        }
                    }
                } catch(e){}
                if (!hasFotoInForm) {
                    hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
                }
            }
            if (cfg.fotoWajib && (!fotoReady || !hasFotoInForm)) {
                e.preventDefault();
                setCamState('state-error', 'Foto selfie wajib diambil sebelum absen pulang. Silakan ambil foto selfie terlebih dahulu.');
                setHint('Foto selfie wajib. Silakan ambil foto selfie terlebih dahulu.');
                return;
            }
            if (cfg.fotoWajib && hasFotoInForm) {
                try {
                    var f = fotoF.files[0];
                    if (!f || f.size === 0) {
                        e.preventDefault();
                        setCamState('state-error', 'File foto kosong. Silakan ambil ulang.');
                        setHint('File foto kosong. Silakan ambil ulang.');
                        return;
                    }
                    if (f.size > (cfg.maxFotoKb ? cfg.maxFotoKb*1024 : 10*1024*1024)) {
                        e.preventDefault();
                        setCamState('state-error', 'Ukuran foto terlalu besar. Maksimal 10 MB.');
                        setHint('Ukuran foto terlalu besar. Maksimal 10 MB.');
                        return;
                    }
                } catch(e){}
            }

            if (cfg.gpsWajib) {
                if (!locationFound) {
                    e.preventDefault();
                    setLocState('state-error', 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.');
                    return;
                }
                if (!locationOk) {
                    e.preventDefault();
                    setLocState('state-error', 'Lokasi belum valid. Pastikan Anda berada dalam Radius Kantor dengan sinyal GPS yang baik.');
                    return;
                }
            }

            submitting = true;
            btn.disabled = true;
            btn.classList.add('loading');
            btn.innerHTML = '<span class="btn-spinner"><i class="fa-solid fa-spinner fa-spin"></i></span> Menyimpan absen pulang...';
            setHint('Menyimpan ke server...');
        });
    }

    evaluateAbsen();
});
