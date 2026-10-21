/* =========================================================
   Halaman Absen Masuk - Sistem Absensi PKL Sandya Network
   Jam realtime (WIB), kamera/selfie, Geolocation (GPS),
   kompas, pilihan jenis kehadiran, dan submit absen.
   Waktu resmi & validasi akhir ditentukan oleh server Laravel.
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    var cfg   = window.ABSEN_CONFIG || {};
    var form  = document.getElementById('formAbsen');
    var btn   = document.getElementById('btnAbsen');
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
    // Debug panel (diagnosis, tidak disimpan DB)
    var dbgLat      = document.getElementById('dbgLat');
    var dbgLng      = document.getElementById('dbgLng');
    var dbgAcc      = document.getElementById('dbgAcc');
    var dbgJarak    = document.getElementById('dbgJarak');
    var dbgRadius   = document.getElementById('dbgRadius');
    var dbgMaxAcc   = document.getElementById('dbgMaxAcc');
    var dbgStatus   = document.getElementById('dbgStatus');

    var submitting  = false;
    var sudahAbsen  = !!cfg.sudahAbsen;

    // Init debug panel (diagnosis) dengan nilai konfigurasi yang konsisten frontend/backend
    (function initGpsDebug(){
        try {
            if (dbgRadius) dbgRadius.textContent = (cfg.radius != null ? cfg.radius : '-') + (cfg.radius != null ? ' m' : '');
            if (dbgMaxAcc) dbgMaxAcc.textContent = (cfg.maxAccuracy != null ? cfg.maxAccuracy : '-') + (cfg.maxAccuracy != null ? ' m' : '');
            if (dbgStatus) dbgStatus.textContent = 'MENUNGGU GPS';
        } catch(e){}
    })();

    /* ================= JAM REALTIME (WIB, sinkron server) ================= */
    var clockEl  = document.getElementById('clockTime');
    var clockSec = document.getElementById('clockSec');

    // offset disiapkan di luar agar bisa dipakai serverNowMs() sebelum init
    // FIX: parse serverTime WIB dengan aman (Safari iOS gagal parse "YYYY-MM-DD HH:mm:ss")
    var offset = 0;
    if (cfg.serverTime) {
        try {
            var srvStr = String(cfg.serverTime).trim().replace(' ', 'T');
            var serverMs = new Date(srvStr).getTime();
            if (isNaN(serverMs)) {
                // fallback coba parse manual WIB
                var p = String(cfg.serverTime).split(/[- :]/);
                if (p.length >= 6) {
                    serverMs = new Date(+p[0], +p[1]-1, +p[2], +p[3], +p[4], +p[5]).getTime();
                }
            }
            if (!isNaN(serverMs)) offset = serverMs - Date.now();
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

    /* ============ STATUS JAM ABSEN MASUK (WIB) ============ */
    var waktuBannerTitle = document.getElementById('waktuBannerTitle');
    var waktuBannerSub   = document.getElementById('waktuBannerSub');
    var waktuCountdown   = document.getElementById('waktuCountdown');

    var mulaiMin   = cfg.jamMulai ? jamKeMenit(cfg.jamMulai) : 420;
    var selesaiMin = cfg.jamSelesai ? jamKeMenit(cfg.jamSelesai) : 510;

    function jamKeMenit(str) {
        var p = String(str).split(':');
        return (+p[0]) * 60 + (+p[1]);
    }

    function serverNowMs() {
        return new Date(Date.now() + offset);
    }

    function perbaruiWaktuStatus() {
        if (!waktuBannerTitle || !cfg.serverTime) return;

        var isMasuk = isMasukSelected();
        var banner = document.querySelector('.waktu-banner');

        // Untuk Izin/Dispensasi/Sakit: bebas waktu, tidak ada batas jam
        if (!isMasuk) {
            var jenisBebas = getSelectedJenis();
            cfg.waktuStatus = 'buka';
            if (banner) banner.className = 'waktu-banner buka';
            waktuBannerTitle.textContent = 'Pengajuan ' + jenisBebas + ' dapat dilakukan kapan saja';
            waktuBannerSub.textContent = 'Tidak ada batas waktu untuk status ' + jenisBebas + '. Silakan ajukan kapan saja.';
            if (waktuCountdown) waktuCountdown.textContent = 'Bebas waktu — kapan saja';
            evaluateAbsen();
            return;
        }

        var d = serverNowMs();
        var menit = d.getHours() * 60 + d.getMinutes() + d.getSeconds() / 60;

        var status = 'buka';
        if (menit < mulaiMin) status = 'belum';
        else if (menit > selesaiMin) status = 'tutup';

        cfg.waktuStatus = status;
        if (banner) banner.className = 'waktu-banner ' + status;

        var title, sub;

        if (status === 'belum') {
            title = 'Absen Masuk belum dibuka.';
            sub = 'Absen Masuk dibuka pukul ' + cfg.jamMulai + ' WIB.';

            var target = new Date(d);
            target.setHours(Math.floor(mulaiMin / 60), mulaiMin % 60, 0, 0);
            var diff = Math.max(0, target - d);
            var hh = Math.floor(diff / 3600000);
            var mm = Math.floor((diff % 3600000) / 60000);
            var ss = Math.floor((diff % 60000) / 1000);
            if (waktuCountdown) {
                waktuCountdown.textContent = 'Absen Masuk dibuka dalam ' + hh + 'j ' + mm + 'm ' + ss + 'd';
            }
            if (hint && !sudahAbsen && isMasukSelected()) {
                setHint('Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul ' + cfg.jamMulai + ' WIB.');
            }
        } else if (status === 'tutup') {
            title = 'Absen Masuk Telat — masih dibuka.';
            sub = 'Anda terlambat. Absen Masuk setelah pukul ' + cfg.jamSelesai + ' WIB akan dicatat sebagai Telat.';
            if (waktuCountdown) waktuCountdown.textContent = 'Jam Absen: ' + cfg.jamMulai + ' - ' + cfg.jamSelesai + ' WIB (Telat tetap bisa absen)';
            if (hint && !sudahAbsen && isMasukSelected()) {
                setHint('Anda terlambat. Absen Masuk akan dicatat sebagai Telat.');
            }
            // ubah banner class jadi telat
            if (banner) banner.className = 'waktu-banner telat';
            if (banner) banner.style.background = '#FEF3C7';
            if (banner) banner.style.borderColor = '#FDE68A';
        } else {
            title = 'Absen Masuk sedang dibuka.';
            sub = 'Silakan lengkapi foto dan GPS, lalu tekan tombol Absen Masuk.';
            if (waktuCountdown) waktuCountdown.textContent = 'Jam Absen: ' + cfg.jamMulai + ' - ' + cfg.jamSelesai + ' WIB';
            if (hint && !sudahAbsen && isMasukSelected()) {
                if (!fotoReady || !locationOk) {
                    setHint('Tombol aktif setelah foto diambil dan lokasi ditemukan dalam Radius Kantor.');
                }
            }
        }

        waktuBannerTitle.textContent = title;
        waktuBannerSub.textContent = sub;

        evaluateAbsen();
    }

    setInterval(perbaruiWaktuStatus, 1000);
    perbaruiWaktuStatus();

    /* ================= JENIS KEHADIRAN ================= */
    var jenisOptions = document.querySelectorAll('.jenis-option');
    var keteranganWrap = document.getElementById('keteranganWrap');
    var keteranganField = document.getElementById('keteranganField');
    var keteranganHelp = document.getElementById('keteranganHelp');
    var gpsInfoMasuk = document.getElementById('gpsInfoMasuk');
    var gpsInfoTanpa = document.getElementById('gpsInfoTanpa');
    var tanpaLabel = document.getElementById('tanpaLabel');
    var fotoNoteMasuk = document.getElementById('fotoNoteMasuk');
    var fotoNoteTanpa = document.getElementById('fotoNoteTanpa');
    var lokasiCard = document.getElementById('lokasiCard');
    var waktuCard = document.getElementById('waktuCard');
    var selfieCard = document.getElementById('selfieCard');
    var absenGrid = document.getElementById('absenGrid');
    var lokasiTanpaNote = document.getElementById('lokasiTanpaGpsNote');
    var btnIcon = document.getElementById('btnIcon');
    var btnText = document.getElementById('btnText');
    var buktiWrap = document.getElementById('buktiWrap');
    var buktiFile = document.getElementById('buktiFile');
    var btnPilihBukti = document.getElementById('btnPilihBukti');
    var buktiFileName = document.getElementById('buktiFileName');
    var btnHapusBukti = document.getElementById('btnHapusBukti');
    var buktiPreview = document.getElementById('buktiPreview');
    var buktiPreviewImg = document.getElementById('buktiPreviewImg');
    var buktiPreviewPdf = document.getElementById('buktiPreviewPdf');
    var buktiPdfName = document.getElementById('buktiPdfName');
    var buktiError = document.getElementById('buktiError');
    var buktiLabelStatus = document.getElementById('buktiLabelStatus');
    var buktiWajibMark = document.getElementById('buktiWajibMark');
    var buktiHint = document.getElementById('buktiHint');

    function getSelectedJenis() {
        var checked = document.querySelector('input[name="status_kehadiran"]:checked');
        if (checked) return checked.value;
        return cfg.oldStatus || 'Masuk';
    }

    function isMasukSelected() {
        return getSelectedJenis() === 'Masuk';
    }

    function isBuktiWajib(jenis) {
        if (!jenis) jenis = getSelectedJenis();
        return jenis === 'Dispensasi' || jenis === 'Sakit';
    }

    function hasBuktiFile() {
        return buktiFile && buktiFile.files && buktiFile.files.length > 0;
    }

    function updateBuktiLabel(jenis) {
        if (!buktiWrap) return;
        var wajib = isBuktiWajib(jenis);
        if (buktiLabelStatus) {
            buktiLabelStatus.textContent = wajib ? '(Wajib)' : '(Opsional)';
        }
        if (buktiWajibMark) {
            buktiWajibMark.style.display = wajib ? '' : 'none';
        }
        if (buktiHint) {
            if (jenis === 'Dispensasi') {
                buktiHint.textContent = 'Bukti dispensasi wajib diupload. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB.';
            } else if (jenis === 'Sakit') {
                buktiHint.textContent = 'Bukti sakit wajib diupload. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB.';
            } else if (jenis === 'Izin') {
                buktiHint.textContent = 'Upload bukti pendukung jika ada. File tidak wajib dilampirkan. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB.';
            } else {
                buktiHint.textContent = 'Upload bukti pendukung jika ada. File tidak wajib dilampirkan. Format: JPG, JPEG, PNG, PDF. Maksimal 5 MB.';
            }
        }
        // Toggle required attribute for native validation (optional, but helpful)
        if (buktiFile) {
            if (wajib) {
                buktiFile.setAttribute('required', 'required');
            } else {
                buktiFile.removeAttribute('required');
            }
        }
    }

    function updateJenisUI() {
        var jenis = getSelectedJenis();
        var isMasuk = jenis === 'Masuk';

        // Update active class on labels
        jenisOptions.forEach(function (label) {
            var val = label.getAttribute('data-jenis');
            if (val === jenis) {
                label.classList.add('active');
                var radio = label.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            } else {
                label.classList.remove('active');
            }
        });

        // Keterangan: tampil hanya untuk Izin/Dispensasi/Sakit + placeholder & help dinamis per jenis
        if (keteranganWrap) {
            keteranganWrap.style.display = isMasuk ? 'none' : 'block';
        }
        if (keteranganField) {
            var placeholderMap = {
                'Masuk': 'Contoh: Izin keperluan keluarga, Dispensasi lomba, Sakit demam...',
                'Izin': 'Contoh: izin kepentingan keluarga yang tidak bisa di tinggalkan',
                'Dispensasi': 'Contoh: dispensasi latihan dan lomba drumband',
                'Sakit': 'Contoh: sakit demam, batuk, badan lemas'
            };
            // Hanya ubah placeholder, jangan ubah value/keterangan yang sudah diisi user
            var newPlaceholder = placeholderMap[jenis] || placeholderMap['Masuk'];
            try { keteranganField.placeholder = newPlaceholder; } catch (e) {}
        }
        if (keteranganHelp) {
            var helpMap = {
                'Masuk': 'Isi keterangan singkat untuk Izin / Dispensasi / Sakit jika diperlukan.',
                'Izin': 'Isi keterangan singkat untuk Izin jika diperlukan.',
                'Dispensasi': 'Isi keterangan singkat untuk Dispensasi jika diperlukan.',
                'Sakit': 'Isi keterangan singkat untuk Sakit jika diperlukan.'
            };
            var newHelp = helpMap[jenis] || helpMap['Masuk'];
            try { keteranganHelp.textContent = newHelp; } catch (e) {}
        }

        if (gpsInfoMasuk) gpsInfoMasuk.style.display = isMasuk ? '' : 'none';
        if (gpsInfoTanpa) {
            gpsInfoTanpa.style.display = isMasuk ? 'none' : '';
            if (tanpaLabel) tanpaLabel.textContent = jenis;
        }

        if (fotoNoteMasuk) fotoNoteMasuk.style.display = isMasuk ? '' : 'none';
        if (fotoNoteTanpa) {
            fotoNoteTanpa.style.display = isMasuk ? 'none' : '';
            if (!isMasuk) fotoNoteTanpa.textContent = 'Foto opsional untuk ' + jenis + '.';
        }

        // ===== Tampilkan / sembunyikan 3 kartu utama: Waktu Server, Foto Selfie, Lokasi Kantor =====
        // Sesuai spec: untuk Izin/Dispensasi/Sakit sembunyikan total (display:none), untuk Masuk tampilkan kembali
        // JANGAN hanya display:none — harus matikan stream & hapus file
        if (!isMasuk) {
            // 1. Matikan camera stream jika aktif
            try { stopStream(); } catch(e){}
            // 2-5. Hapus preview, blob, reset variabel, kosongkan input
            setFotoFile(null);
            if (fotoF) fotoF.value = '';
            if (fotoPreview) { fotoPreview.hidden = true; try{ fotoPreview.removeAttribute('src'); }catch(e){} }
            if (btnAmbil) btnAmbil.hidden = false;
            if (btnUlang) btnUlang.hidden = true;
            if (camVideo) { try{ camVideo.style.display = 'block'; camVideo.hidden = false; }catch(e){} }
            if (camEmpty) camEmpty.style.display = 'none';
            setCamState('state-loading', 'Kamera dinonaktifkan untuk status ' + jenis + '. Pilih Masuk untuk mengaktifkan selfie.');
            if (fallbackWrap) fallbackWrap.style.display = 'none';
            if (labelFile) labelFile.hidden = true;
            // 7. GPS tidak diperlukan untuk non-Masuk — tombol tidak menunggu GPS, jadi tidak perlu reset locationOk
        } else {
            // Kembali ke Masuk: foto harus diambil ulang, GPS divalidasi kembali
            if (!stream) {
                // Reset foto agar tidak pakai foto lama dari status sebelumnya
                setFotoFile(null);
                if (fotoF) fotoF.value = '';
                if (fotoPreview) { fotoPreview.hidden = true; try{ fotoPreview.removeAttribute('src'); }catch(e){} }
                if (btnAmbil) btnAmbil.hidden = false;
                if (btnUlang) btnUlang.hidden = true;
                setCamState('state-loading', 'Menyiapkan kamera...');
                try { initKamera(); } catch(e){}
            }
        }
        if (waktuCard) waktuCard.style.display = isMasuk ? '' : 'none';
        if (selfieCard) selfieCard.style.display = isMasuk ? '' : 'none';
        if (lokasiCard) lokasiCard.style.display = isMasuk ? '' : 'none';
        // Grid adjustment: jika semua disembunyikan, grid tetap ada tapi kosong; kita atur agar tidak menyisakan gap besar
        if (absenGrid) {
            if (isMasuk) absenGrid.style.display = '';
            else absenGrid.style.display = 'none';
        }
        // Legacy note handling: tetap update meski card disembunyikan agar saat kembali ke Masuk status benar
        if (lokasiCard && isMasuk) {
            lokasiCard.classList.remove('lokasi-disabled');
            if (lokasiTanpaNote) lokasiTanpaNote.style.display = 'none';
            if (radiusEl) radiusEl.style.display = '';
            // Jika lokasi sudah ditemukan sebelumnya, perbarui lagi statusnya menjadi valid
            // Biarkan applyLocation yang mengatur, tapi pastikan tidak tertampil pesan tanpa-GPS
        } else if (!isMasuk) {
            if (lokasiTanpaNote) lokasiTanpaNote.style.display = 'none';
        }

        // Bukti pendukung: tampil untuk Izin/Dispensasi/Sakit, sembunyi untuk Masuk
        if (buktiWrap) {
            buktiWrap.style.display = isMasuk ? 'none' : 'block';
            updateBuktiLabel(jenis);
            if (isMasuk && buktiFile) {
                // Ketika kembali ke Masuk, reset bukti agar tidak terbawa
                // (file untuk Izin/Dispensasi/Sakit jangan sampai mempengaruhi validasi Masuk)
                buktiFile.value = '';
                if (buktiFileName) buktiFileName.textContent = 'Belum ada file dipilih';
                if (buktiPreview) buktiPreview.style.display = 'none';
                if (buktiPreviewImg) { buktiPreviewImg.style.display = 'none'; buktiPreviewImg.src = ''; }
                if (buktiPreviewPdf) buktiPreviewPdf.style.display = 'none';
                if (btnHapusBukti) btnHapusBukti.style.display = 'none';
                if (buktiError) { buktiError.style.display = 'none'; buktiError.textContent = ''; }
            } else if (!isMasuk) {
                // Jangan hapus file saat berpindah antar Izin/Dispensasi/Sakit,
                // biarkan file tetap ada; hanya perbarui pesan error jika sebelumnya ada
                // Clear error jika file sudah ada dan sekarang wajib tapi sudah terpenuhi
                if (hasBuktiFile() && buktiError) {
                    buktiError.style.display = 'none';
                    buktiError.textContent = '';
                }
                // Jika tidak ada file dan wajib, tampilkan hint error via evaluateAbsen nanti
                // Tidak langsung show error saat switch, hanya saat submit / evaluate
            }
        }

        // Tombol: ganti teks & ikon sesuai jenis
        if (btn && btnText) {
            var iconMap = {
                'Masuk': 'fa-location-dot',
                'Izin': 'fa-envelope-open-text',
                'Dispensasi': 'fa-file-circle-check',
                'Sakit': 'fa-house-medical'
            };
            var textMap = {
                'Masuk': 'Absen Masuk',
                'Izin': 'Ajukan Izin',
                'Dispensasi': 'Ajukan Dispensasi',
                'Sakit': 'Ajukan Sakit'
            };
            btnText.textContent = textMap[jenis] || 'Absen Masuk';
            if (btnIcon) {
                btnIcon.className = 'fa-solid ' + (iconMap[jenis] || 'fa-location-dot');
            }
            // Warna tombol: hijau untuk Masuk, biru/kuning/merah untuk lainnya
            btn.style.background = '';
            if (jenis === 'Izin') btn.style.background = 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)';
            else if (jenis === 'Dispensasi') btn.style.background = 'linear-gradient(135deg, #3B82F6 0%, #2563EB 100%)';
            else if (jenis === 'Sakit') btn.style.background = 'linear-gradient(135deg, #EF4444 0%, #DC2626 100%)';
            else btn.style.background = 'linear-gradient(135deg, #10B981 0%, #059669 100%)';
        }

        // Update hint & banner langsung saat ganti jenis
        if (hint && !sudahAbsen) {
            if (isMasuk) {
                if (cfg.waktuStatus === 'buka') {
                    if (!fotoReady || !locationOk) {
                        setHint('Tombol aktif setelah foto diambil dan lokasi ditemukan dalam Radius Kantor.');
                    } else {
                        setHint('Silakan lengkapi foto dan GPS, lalu tekan tombol Absen Masuk.');
                    }
                } else if (cfg.waktuStatus === 'belum') {
                    setHint('Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul ' + cfg.jamMulai + ' WIB.');
                } else if (cfg.waktuStatus === 'tutup') {
                    setHint('Absen Masuk sudah ditutup. Batas waktu Absen Masuk adalah pukul ' + cfg.jamSelesai + ' WIB.');
                }
            } else {
                if (isBuktiWajib(jenis) && !hasBuktiFile()) {
                    setHint('Untuk ' + jenis + ', bukti wajib diupload. Silakan pilih file terlebih dahulu.');
                } else {
                    setHint('Untuk ' + jenis + ', tombol aktif kapan saja tanpa GPS. Foto bersifat opsional.' + (isBuktiWajib(jenis) ? ' Bukti sudah dilampirkan.' : ''));
                }
            }
        }

        // Perbarui banner waktu agar langsung mencerminkan aturan bebas waktu untuk non-Masuk
        perbaruiWaktuStatus();
        evaluateAbsen();
    }

    // Bind click pada label jenis
    jenisOptions.forEach(function (label) {
        label.addEventListener('click', function () {
            var radio = label.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            updateJenisUI();
        });
    });
    // Juga dengarkan change pada radio
    document.querySelectorAll('input[name="status_kehadiran"]').forEach(function (r) {
        r.addEventListener('change', updateJenisUI);
    });

    // Init UI sesuai oldStatus
    updateJenisUI();

    /* ================= BUKTI PENDUKUNG (Izin opsional, Dispensasi/Sakit wajib) ================= */
    function showBuktiError(msg) {
        if (buktiError) {
            buktiError.textContent = msg;
            buktiError.style.display = 'block';
        }
    }
    function resetBukti() {
        if (buktiFile) buktiFile.value = '';
        if (buktiFileName) buktiFileName.textContent = 'Belum ada file dipilih';
        if (buktiPreview) buktiPreview.style.display = 'none';
        if (buktiPreviewImg) { buktiPreviewImg.style.display = 'none'; buktiPreviewImg.src = ''; }
        if (buktiPreviewPdf) buktiPreviewPdf.style.display = 'none';
        if (btnHapusBukti) btnHapusBukti.style.display = 'none';
        if (buktiError) { buktiError.style.display = 'none'; buktiError.textContent = ''; }
        evaluateAbsen();
        // Update hint setelah hapus
        var jenis = getSelectedJenis();
        if (!isMasukSelected() && isBuktiWajib(jenis) && !hasBuktiFile()) {
            showBuktiError(jenis === 'Dispensasi' ? 'Bukti dispensasi wajib diupload.' : 'Bukti sakit wajib diupload.');
            if (hint) setHint('Untuk ' + jenis + ', bukti wajib diupload. Silakan pilih file terlebih dahulu.');
        }
    }
    if (btnPilihBukti && buktiFile) {
        btnPilihBukti.addEventListener('click', function () {
            buktiFile.click();
        });
    }
    if (buktiFile) {
        buktiFile.addEventListener('change', function () {
            var file = buktiFile.files[0];
            if (!file) { resetBukti(); return; }
            var allowedExts = ['jpg','jpeg','png','pdf'];
            var ext = file.name.split('.').pop().toLowerCase();
            if (allowedExts.indexOf(ext) === -1) {
                showBuktiError('Format bukti harus JPG, JPEG, PNG, atau PDF.');
                resetBukti();
                // ensure file cleared
                if (buktiFile) buktiFile.value = '';
                evaluateAbsen();
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showBuktiError('Ukuran bukti maksimal 5 MB.');
                resetBukti();
                if (buktiFile) buktiFile.value = '';
                evaluateAbsen();
                return;
            }
            if (buktiError) { buktiError.style.display = 'none'; buktiError.textContent=''; }
            if (buktiFileName) buktiFileName.textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
            if (btnHapusBukti) btnHapusBukti.style.display = 'inline-flex';
            if (buktiPreview) buktiPreview.style.display = 'block';
            if (file.type.startsWith('image/')) {
                var url = URL.createObjectURL(file);
                if (buktiPreviewImg) { buktiPreviewImg.src = url; buktiPreviewImg.style.display='block'; }
                if (buktiPreviewPdf) buktiPreviewPdf.style.display='none';
            } else if (file.type === 'application/pdf' || ext === 'pdf') {
                if (buktiPreviewImg) buktiPreviewImg.style.display='none';
                if (buktiPreviewPdf) buktiPreviewPdf.style.display='block';
                if (buktiPdfName) buktiPdfName.textContent = file.name;
            } else {
                if (buktiPreviewImg) buktiPreviewImg.style.display='none';
                if (buktiPreviewPdf) buktiPreviewPdf.style.display='none';
            }
            evaluateAbsen();
            // update hint after selecting
            var jenis = getSelectedJenis();
            if (!isMasukSelected()) {
                if (hint) setHint('Untuk ' + jenis + ', tombol aktif kapan saja tanpa GPS. Foto bersifat opsional. Bukti sudah dilampirkan.');
            }
        });
    }
    if (btnHapusBukti) {
        btnHapusBukti.addEventListener('click', resetBukti);
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

    /* Tombol absen: Masuk/Telat butuh GPS+foto, Izin opsional, Dispensasi/Sakit wajib bukti
       Masuk: 08:00-08:30, Telat: >08:30 tetap boleh absen (dinilai Telat) */
    function evaluateAbsen() {
        if (!btn) return;
        if (sudahAbsen) {
            btn.disabled = true;
            return;
        }
        var isMasuk = isMasukSelected();
        // Sebelum 08:00 belum boleh, 08:00-08:30 (buka) dan >08:30 (tutup/Telat) tetap boleh
        var waktuOk = isMasuk ? (cfg.waktuStatus === 'buka' || cfg.waktuStatus === 'tutup') : true;

        if (isMasuk) {
            var gpsOk = !cfg.gpsWajib || locationOk;
            // Perbaikan BUG: jangan hanya cek fotoReady, tapi pastikan file benar-benar ada di input form
            var hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
            var fotoOk = !cfg.fotoWajib || (fotoReady && hasFotoInForm);
            btn.disabled = !(waktuOk && gpsOk && fotoOk);
            if (hint && !sudahAbsen) {
                if (!waktuOk) {
                    if (cfg.waktuStatus === 'belum') setHint('Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul ' + cfg.jamMulai + ' WIB.');
                } else if (!fotoOk) {
                    if (cfg.fotoWajib) setHint('Foto selfie wajib. Silakan ambil foto selfie terlebih dahulu.');
                } else if (!gpsOk) {
                    if (!locationFound) setHint('Lokasi belum ditemukan. Silakan aktifkan GPS dan tunggu hingga lokasi valid.');
                    else if (lastAccuracy != null && cfg.maxAccuracy > 0 && lastAccuracy > cfg.maxAccuracy) setHint('Akurasi buruk (±' + Math.round(lastAccuracy) + ' m > ' + cfg.maxAccuracy + ' m). Pindah ke area terbuka / dekat jendela.');
                    else if (lastDist != null && lastDist > cfg.radius) setHint('Di luar radius kantor (jarak ' + Math.round(lastDist) + ' m > ' + cfg.radius + ' m).');
                    else setHint('Lokasi belum valid. Pastikan Anda berada dalam Radius Kantor (' + cfg.radius + ' m) dengan akurasi GPS baik (±' + (lastAccuracy!=null?Math.round(lastAccuracy):'?') + ' m).');
                } else if (!btn.disabled) {
                    setHint('Silakan lengkapi foto dan GPS, lalu tekan tombol Absen Masuk.');
                }
            }
            // Jika tombol nonaktif karena bukti tidak berkaitan dengan Masuk, pastikan hint tidak menyebut bukti
        } else {
            // Untuk Izin: bebas waktu, tanpa GPS, foto opsional, bukti opsional => selalu aktif
            // Untuk Dispensasi/Sakit: bukti wajib => tombol hanya aktif jika ada file
            var jenis = getSelectedJenis();
            if (isBuktiWajib(jenis)) {
                btn.disabled = !hasBuktiFile();
                // Tampilkan/clear error sesuai keadaan (tapi jangan spam saat init)
                if (!hasBuktiFile()) {
                    if (buktiError && buktiError.style.display === 'none') {
                        // hanya set hint, error akan ditampilkan saat submit jika belum ada
                    }
                    if (hint && document.activeElement !== buktiFile) {
                        // hint sudah diupdate di updateJenisUI
                    }
                } else {
                    if (buktiError) { buktiError.style.display = 'none'; buktiError.textContent = ''; }
                }
            } else {
                // Izin: selalu aktif
                btn.disabled = false;
                if (buktiError) { /* jangan sembunyikan error format jika ada */ }
            }
        }
    }

    function setAlert(type, text) {
        if (!locStatusEl) return;
        setLocState(type === 'ok' ? 'state-found' : 'state-error', text);
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

    var FOTO_MAX_DIM = 1600;
    var FOTO_QUALITY = 0.82;

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
        if (w <= max && h <= max) return {w: w, h: h};
        var scale = Math.min(max / w, max / h);
        return {w: Math.max(1, Math.round(w * scale)), h: Math.max(1, Math.round(h * scale))};
    }

    /* Salin file ke input hidden foto agar ikut terkirim bersama form. Return true jika berhasil. */
    // FIX MINIMAL: gunakan FotoHelper jika ada (lebih robust), fallback DataTransfer + ClipboardEvent untuk Safari/iOS lama,
    // dan verifikasi file benar-benar masuk ke input sebelum preview (hindari preview tanpa file).
    function injectFotoKeForm(file) {
        if (!fotoF) {
            setCamState('state-error', 'Elemen foto tidak ditemukan. Muat ulang halaman.');
            return false;
        }
        // 1. Coba via FotoHelper (shared, sudah teruji di Laporan & Pulang)
        if (window.FotoHelper && FotoHelper.injectFilesToInput) {
            try {
                var okHelper = FotoHelper.injectFilesToInput(fotoF, [file]);
                if (okHelper) return true;
            } catch (e) {}
            // jika helper gagal, lanjut ke fallback manual
        }
        try {
            var dt = null;
            // Coba DataTransfer modern
            if (typeof DataTransfer !== 'undefined') {
                try { dt = new DataTransfer(); } catch (e) { dt = null; }
            }
            // Fallback Safari lama: ClipboardEvent
            if (!dt && typeof ClipboardEvent !== 'undefined') {
                try {
                    var ce = new ClipboardEvent('');
                    if (ce.clipboardData) dt = ce.clipboardData;
                } catch (e) {}
            }
            // Fallback terakhir: coba DataTransfer via document
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
            // Beberapa browser jadul butuh defineProperty
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
            // Verifikasi tambahan: size harus >0 dan type image
            if (!fotoF.files[0].type || fotoF.files[0].type.indexOf('image/') !== 0) {
                // tetap izinkan jika size >0 (beberapa browser type kosong), tapi cek size
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
        // FIX: gunakan FotoHelper jika tersedia (lebih robust & konsisten dengan Pulang/Laporan)
        if (window.FotoHelper && FotoHelper.compressImageFile) {
            FotoHelper.compressImageFile(file, callback);
            return;
        }
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
                // Pastikan file benar-benar masuk ke form sebelum preview
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
        // FIX: canvas.toBlob tidak ada di browser lama -> fallback toDataURL (agar tetap bisa foto)
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
            // Pastikan file benar-benar masuk ke form sebelum tampilkan preview & aktifkan tombol
            var ok = injectFotoKeForm(file);
            if (!ok) {
                setCamState('state-error', 'Gagal menyiapkan foto untuk dikirim. Silakan ambil ulang.');
                setFotoFile(null);
                if (fotoF) fotoF.value = '';
                return;
            }
            var previewUrl;
            try {
                previewUrl = URL.createObjectURL(blob);
            } catch (e) {
                setCamState('state-error', 'Gagal menampilkan preview foto.');
                // rollback file yang sudah diinject
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
    try { initKamera(); } catch(e){ setCamState('state-error','Gagal menyiapkan kamera.'); showFallbackOption(true); }

    /* ================= GEOLOCATION (GPS) ================= */
    var locationOk = false;
    var locationFound = false;
    var lastAccuracy = null;
    var lastDist = null;

    function updateGpsDebug(lat, lng, accuracyNum, dist, akurasiOk, dalamRadius) {
        try {
            if (dbgLat) dbgLat.textContent = (lat != null ? Number(lat).toFixed(6) : '-');
            if (dbgLng) dbgLng.textContent = (lng != null ? Number(lng).toFixed(6) : '-');
            if (dbgAcc) dbgAcc.textContent = (accuracyNum != null && !isNaN(accuracyNum) ? '±' + Number(accuracyNum).toFixed(0) + ' m' : '-');
            if (dbgJarak) dbgJarak.textContent = (dist != null && !isNaN(dist) ? Math.round(dist) + ' m' : '-');
            if (dbgRadius) dbgRadius.textContent = (cfg.radius != null ? cfg.radius : '-') + (cfg.radius != null ? ' m' : '');
            if (dbgMaxAcc) dbgMaxAcc.textContent = (cfg.maxAccuracy != null ? cfg.maxAccuracy : '-') + (cfg.maxAccuracy != null ? ' m' : '');
            if (dbgStatus) {
                if (!locationFound) dbgStatus.textContent = 'GPS TIDAK TERSEDIA';
                else if (!akurasiOk) dbgStatus.textContent = 'AKURASI BURUK (±' + accuracyNum.toFixed(0) + ' m > ' + cfg.maxAccuracy + ' m)';
                else if (!dalamRadius) dbgStatus.textContent = 'DI LUAR RADIUS (' + Math.round(dist) + ' m > ' + cfg.radius + ' m)';
                else dbgStatus.textContent = 'VALID';
            }
        } catch(e){}
    }

    function applyLocation(lat, lng, accuracy) {
        // Jika sedang mode tanpa GPS, tetap simpan koordinat tapi tidak mempengaruhi validasi tombol
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
        lastAccuracy = accuracyNum;
        lastDist = dist;

        // Update debug panel (selalu, bahkan untuk non-Masuk agar terlihat)
        updateGpsDebug(lat, lng, accuracyNum, dist, akurasiOk, dalamRadius);

        // Jika jenis bukan Masuk, jangan override pesan "tidak perlu GPS"
        if (!isMasukSelected()) {
            // Tetap catat tapi tidak ubah UI radius yang sudah di-set ke tanpa-validasi
            evaluateAbsen();
            return;
        }

        // Tampilkan pesan yang memisahkan penyebab akurasi vs radius (agar tidak salah sangka radius)
        if (!akurasiOk) {
            setLocState('state-error', 'Akurasi GPS buruk (±' + accuracyNum.toFixed(0) + ' m). Max: ' + cfg.maxAccuracy + ' m. Pindah ke area terbuka / dekat jendela.');
            if (hint) setHint('Akurasi lokasi terlalu rendah (±' + accuracyNum.toFixed(0) + ' m > ' + cfg.maxAccuracy + ' m). Tunggu GPS membaik.');
        } else if (!dalamRadius) {
            setLocState('state-error', 'Di luar radius kantor (jarak ' + Math.round(dist) + ' m > ' + cfg.radius + ' m).');
            if (hint) setHint('Anda di luar Radius Kantor (jarak ' + Math.round(dist) + ' m, radius ' + cfg.radius + ' m).');
        } else {
            setLocState('state-found', 'Lokasi ditemukan.');
        }

        if (radiusEl) {
            if (!akurasiOk) {
                radiusEl.className = 'radius-pill no';
                radiusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Akurasi buruk (±' + accuracyNum.toFixed(0) + ' m &gt; ' + cfg.maxAccuracy + ' m). Tunggu...';
            } else if (dalamRadius) {
                radiusEl.className = 'radius-pill ok';
                radiusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Lokasi valid — jarak ' + Math.round(dist) + ' m dalam radius ' + cfg.radius + ' m';
            } else {
                radiusEl.className = 'radius-pill no';
                radiusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Di luar radius (' + Math.round(dist) + ' m &gt; ' + cfg.radius + ' m)';
            }
        }

        evaluateAbsen();
    }

    if (!('geolocation' in navigator)) {
        var msgNoGeo = 'Browser tidak mendukung Geolocation. Gunakan HP atau Chrome/Firefox terbaru di laptop dengan GPS/on location.';
        setLocState('state-error', msgNoGeo);
        if (dbgStatus) dbgStatus.textContent = 'BROWSER TIDAK DUKUNG GEOLOCATION';
        if (hint) setHint(msgNoGeo);
        try { console.warn('[GPS] geolocation not in navigator'); } catch(e){}
    } else {
        setLocState('state-loading', 'Mencari lokasi...');
        if (dbgStatus) dbgStatus.textContent = 'MENCARI LOKASI...';
        try {
            navigator.geolocation.watchPosition(
                function (pos) {
                    try {
                        applyLocation(
                            pos.coords.latitude,
                            pos.coords.longitude,
                            pos.coords.accuracy
                        );
                    } catch(e){
                        // jangan hentikan proses absen karena error lokasi
                        try { evaluateAbsen(); } catch(err){}
                        try { console.error('[GPS] applyLocation error', e); } catch(err2){}
                    }
                },
                function (err) {
                    try {
                        locationFound = false;
                        locationOk = false;
                        // Update debug: kosongkan koordinat, tampilkan error spesifik
                        try {
                            if (dbgLat) dbgLat.textContent = '-';
                            if (dbgLng) dbgLng.textContent = '-';
                            if (dbgAcc) dbgAcc.textContent = '-';
                            if (dbgJarak) dbgJarak.textContent = '-';
                            if (dbgStatus) {
                                var code = err && err.code;
                                if (code === 1) dbgStatus.textContent = 'PERMISSION_DENIED (1)';
                                else if (code === 2) dbgStatus.textContent = 'POSITION_UNAVAILABLE (2)';
                                else if (code === 3) dbgStatus.textContent = 'TIMEOUT (3)';
                                else dbgStatus.textContent = 'GPS ERROR (' + (code||'?') + ')';
                            }
                            console.warn('[GPS] watchPosition error', err);
                        } catch(e2){}
                        try { evaluateAbsen(); } catch(e){}
                        if (!isMasukSelected()) {
                            setLocState('state-found', 'GPS tidak diperlukan untuk ' + getSelectedJenis() + '.');
                            if (radiusEl) {
                                radiusEl.className = 'radius-pill ok';
                                radiusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Tanpa validasi radius untuk ' + getSelectedJenis();
                            }
                            return;
                        }
                        var errMsg = lokasiErrorMessage(err && err.code);
                        setLocState('state-error', errMsg);
                        if (hint) setHint(errMsg);
                    } catch(e){
                        setLocState('state-error', 'Gagal mendapatkan lokasi. Coba lagi.');
                        if (dbgStatus) dbgStatus.textContent = 'GPS ERROR';
                        try { evaluateAbsen(); } catch(err){}
                    }
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            );
        } catch(e){
            var msgStart = 'Gagal memulai pencarian lokasi. Pastikan GPS aktif dan berikan izin lokasi.';
            setLocState('state-error', msgStart);
            if (dbgStatus) dbgStatus.textContent = 'GAGAL MEMULAI GPS';
            if (hint) setHint(msgStart);
            try { evaluateAbsen(); } catch(err){}
            try { console.error('[GPS] watchPosition start error', e); } catch(e2){}
        }
    }

    function lokasiErrorMessage(code) {
        switch (code) {
            case 1: return 'Izin lokasi ditolak. Aktifkan izin GPS di pengaturan browser (ikon gembok di address bar) lalu muat ulang.';
            case 2: return 'Lokasi tidak tersedia. Pastikan GPS aktif, di luar ruangan, atau coba gunakan HP.';
            case 3: return 'Waktu pencarian lokasi habis. Pastikan sinyal GPS baik dan coba lagi.';
            default: return 'Gagal mendapatkan lokasi. Pastikan GPS aktif dan coba lagi.';
        }
    }

    /* ================= SUBMIT ABSEN MASUK ================= */
    if (form && btn) {
        form.addEventListener('submit', function (e) {
            if (submitting) {
                e.preventDefault();
                return;
            }

            if (sudahAbsen) {
                e.preventDefault();
                return;
            }

            var jenis = getSelectedJenis();
            var isMasuk = jenis === 'Masuk';

            // Waktu resmi hanya berlaku untuk Masuk; Izin/Dispensasi/Sakit bebas waktu
            // Masuk: sebelum 08:00 -> tolak, 08:00-08:30 -> Masuk, >08:30 -> Telat (tetap boleh)
            if (isMasuk) {
                if (cfg.waktuStatus === 'belum') {
                    e.preventDefault();
                    setHint('Absen Masuk belum dibuka. Silakan melakukan absensi mulai pukul ' + cfg.jamMulai + ' WIB.');
                    return;
                }
                // tutup => Telat, jangan tolak, biarkan lanjut
            }

            if (isMasuk) {
                // FIX: reinject jika file di memori ada tapi belum di input (mobile DataTransfer edge case)
                var hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
                if (!hasFotoInForm && fotoFile && fotoFile.size > 0) {
                    // coba reinject sekali sebelum menolak
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
                    // jika masih gagal, tetap lanjut ke validasi error
                    if (!hasFotoInForm) {
                        hasFotoInForm = fotoF && fotoF.files && fotoF.files.length > 0 && fotoF.files[0].size > 0;
                    }
                }
                if (cfg.fotoWajib && (!fotoReady || !hasFotoInForm)) {
                    e.preventDefault();
                    // pastikan hint juga muncul agar user mobile paham (camStatus mungkin tertutup)
                    setCamState('state-error', 'Foto selfie wajib diambil sebelum absen masuk. Silakan ambil foto selfie terlebih dahulu.');
                    setHint('Foto selfie wajib. Silakan ambil foto selfie terlebih dahulu.');
                    return;
                }
                // Validasi tambahan: pastikan file yang akan dikirim valid (size>0, image)
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
                        var msgGps = 'Lokasi belum ditemukan. Silakan aktifkan GPS dan coba lagi.';
                        setLocState('state-error', msgGps);
                        setHint(msgGps);
                        try { if (dbgStatus) dbgStatus.textContent = 'GPS TIDAK TERSEDIA - GAGAL SUBMIT'; } catch(e){}
                        return;
                    }
                    if (!locationOk) {
                        e.preventDefault();
                        var msgGps2;
                        if (lastAccuracy != null && cfg.maxAccuracy > 0 && lastAccuracy > cfg.maxAccuracy) {
                            msgGps2 = 'Akurasi GPS buruk (±' + Math.round(lastAccuracy) + ' m > ' + cfg.maxAccuracy + ' m). Pindah ke area terbuka.';
                        } else if (lastDist != null && lastDist > cfg.radius) {
                            msgGps2 = 'Di luar radius kantor (jarak ' + Math.round(lastDist) + ' m > ' + cfg.radius + ' m).';
                        } else {
                            msgGps2 = 'Lokasi belum valid. Akurasi: ±' + (lastAccuracy!=null?Math.round(lastAccuracy):'?') + ' m, Jarak: ' + (lastDist!=null?Math.round(lastDist):'?') + ' m, Radius: ' + cfg.radius + ' m.';
                        }
                        setLocState('state-error', msgGps2);
                        setHint(msgGps2);
                        try { if (dbgStatus) dbgStatus.textContent = 'GAGAL SUBMIT: ' + msgGps2; } catch(e){}
                        return;
                    }
                }
            } else {
                // Untuk Izin: bukti opsional
                // Untuk Dispensasi/Sakit: bukti wajib
                if (jenis === 'Dispensasi' && !hasBuktiFile()) {
                    e.preventDefault();
                    showBuktiError('Bukti dispensasi wajib diupload.');
                    setHint('Bukti dispensasi wajib diupload.');
                    if (buktiFile) buktiFile.focus();
                    return;
                }
                if (jenis === 'Sakit' && !hasBuktiFile()) {
                    e.preventDefault();
                    showBuktiError('Bukti sakit wajib diupload.');
                    setHint('Bukti sakit wajib diupload.');
                    if (buktiFile) buktiFile.focus();
                    return;
                }
                // Untuk Izin tanpa file: biarkan submit
                // Foto opsional, GPS tidak divalidasi -> tidak perlu kosongkan, server akan mengabaikan
            }

            submitting = true;
            btn.disabled = true;
            btn.classList.add('loading');
            var savingText = isMasuk ? 'Menyimpan absen masuk...' : 'Menyimpan ' + jenis + '...';
            btn.innerHTML = '<span class="btn-spinner"><i class="fa-solid fa-spinner fa-spin"></i></span> ' + savingText;
            setHint('Menyimpan ke server...');
        });
    }

    evaluateAbsen();
});
