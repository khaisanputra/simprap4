<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAPORAN KEGIATAN SISWA PKL{{ ($laporan->user?->nama ?? $laporan->user?->username) ? ' - '.($laporan->user?->nama ?? $laporan->user?->username) : '' }}</title>
    <style>
        /* ========== BASE (screen + print + dompdf) ========== */
        * { margin:0; padding:0; box-sizing:border-box; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color:#1F2937;
            background:#F1F5F9;
            line-height:1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        /* A4 portrait — margin: Atas 4cm, Kanan 4cm, Bawah 4cm, Kiri 5cm */
        @page {
            size: A4 portrait;
            margin: 40mm 40mm 40mm 50mm;
        }

        /* ---------- NO-PRINT BAR (screen only, mobile friendly) ---------- */
        .no-print {
            position: sticky;
            top:0;
            z-index: 100;
            display:flex;
            flex-wrap: wrap;
            justify-content:center;
            gap:10px;
            padding:14px 16px;
            background:#FFFFFF;
            border-bottom:1px solid #E5E7EB;
            box-shadow:0 4px 12px rgba(15,76,129,0.06);
        }
        .no-print button,
        .no-print a {
            appearance:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:7px;
            padding:10px 18px;
            border-radius:999px;
            font-size:13px;
            font-weight:600;
            text-decoration:none;
            cursor:pointer;
            border:1px solid transparent;
            transition: all .2s;
            white-space: nowrap;
            line-height:1;
            font-family: inherit;
        }
        .btn-print {
            background:#0F4C81;
            color:#fff;
            border-color:#0F4C81;
        }
        .btn-print:hover { background:#0A3A63; transform: translateY(-1px); box-shadow:0 6px 16px rgba(15,76,129,.22); }
        .btn-back {
            background:#fff;
            color:#374151;
            border-color:#E5E7EB;
        }
        .btn-back:hover { background:#F8FAFC; border-color:#CBD5E1; color:#1F2937; }

        /* ---------- DOCUMENT CONTAINER (preview screen = card, print = plain) ---------- */
        .doc {
            max-width:210mm;
            margin:24px auto;
            background:#fff;
            box-shadow:0 12px 40px rgba(15,76,129,0.10);
            border:1px solid #E5E7EB;
            border-radius:14px;
            overflow:hidden;
        }
        .doc-inner {
            padding: 22mm 18mm 18mm 18mm;
        }

        /* ---------- HEADER (table for dompdf compat) ---------- */
        .header-table {
            width:100%;
            border-collapse:collapse;
            margin-bottom:10px;
            border-bottom:2.5px solid #0F4C81;
            padding-bottom:12px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0 0 12px 0;
        }
        .header-logo-cell {
            width:64px;
            padding-right:14px;
            text-align:center;
            vertical-align: middle;
        }
        .header-logo-cell img {
            width:56px;
            height:auto;
            max-height:56px;
            object-fit:contain;
            display:block;
            margin:0 auto;
        }
        .header-text-cell h1 {
            font-size:15px;
            font-weight:800;
            color:#0F4C81;
            letter-spacing:0.02em;
            line-height:1.2;
            margin:0 0 3px 0;
        }
        .header-text-cell p {
            font-size:10px;
            color:#6B7280;
            line-height:1.45;
            margin:0;
        }

        /* ---------- TITLE ---------- */
        .title {
            text-align:center;
            margin:16px 0 18px;
        }
        .title h2 {
            font-size:14pt;
            font-weight:800;
            color:#0F4C81;
            letter-spacing:0.06em;
            text-transform:uppercase;
            line-height:1.25;
            display:inline-block;
            border-bottom:2px solid #0F4C81;
            padding-bottom:6px;
        }
        .title-sub {
            margin-top:8px;
            font-size:9.5pt;
            color:#6B7280;
            font-weight:500;
        }

        /* ---------- SECTION ---------- */
        .section {
            margin-bottom:18px;
        }
        .section-title {
            font-size:10.5pt;
            font-weight:800;
            color:#0F4C81;
            text-transform:uppercase;
            letter-spacing:0.06em;
            border-left:4px solid #0F4C81;
            padding-left:10px;
            margin-bottom:10px;
            line-height:1.3;
        }

        /* ---------- IDENTITAS (table, consistent alignment) ---------- */
        .ident-table {
            width:100%;
            border-collapse:collapse;
            table-layout: fixed;
        }
        .ident-table td {
            padding:5px 0;
            font-size:10.5pt;
            vertical-align:top;
            line-height:1.5;
        }
        .ident-label {
            width:34%;
            color:#374151;
            font-weight:700;
            text-transform:uppercase;
            font-size:9.5pt;
            letter-spacing:0.04em;
            white-space: nowrap;
        }
        .ident-sep {
            width:22px;
            text-align:center;
            color:#6B7280;
            font-weight:600;
        }
        .ident-value {
            width:62%;
            color:#1F2937;
            font-weight:500;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .ident-value strong { font-weight:700; color:#111827; }

        /* ---------- TEKS LAPORAN ---------- */
        .teks-content {
            font-size:11pt;
            line-height:1.62;
            text-align:justify;
            color:#1F2937;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
            /* no background, no height, no overflow hidden per spec */
            background: transparent;
            border: none;
            padding:0;
        }
        .teks-content p {
            margin:0 0 0.85em 0;
            text-align:justify;
            line-height:1.62;
        }
        .teks-content p:last-child { margin-bottom:0; }

        /* ---------- DOKUMENTASI ---------- */
        .foto-section {
            margin-top:8px;
        }
        .foto-block {
            display:block;
            width:100%;
            max-width:100%;
            margin:0 auto 12mm auto;
            text-align:center;
            break-inside: avoid;
            page-break-inside: avoid;
            -webkit-column-break-inside: avoid;
            overflow: visible;
        }
        .foto-block:last-child { margin-bottom:0; }
        .foto-img {
            display:block;
            width:100%;
            height:auto;
            max-width:100%;
            object-fit: contain;
            border:1px solid #D1D5DB;
            border-radius:6px;
            background:#fff;
            /* screen preview limit, print will override */
            max-height: 560px;
        }
        .foto-caption {
            margin-top:7px;
            font-size:8.5pt;
            color:#6B7280;
            font-style:italic;
            line-height:1.4;
            word-break: break-word;
        }
        .foto-number {
            font-size:8pt;
            color:#9CA3AF;
            font-weight:600;
            letter-spacing:0.04em;
            text-transform:uppercase;
            margin-bottom:6px;
        }
        .no-foto {
            font-size:10pt;
            color:#9CA3AF;
            font-style:italic;
            border:1px dashed #D1D5DB;
            padding:20px;
            text-align:center;
            border-radius:8px;
            background:#F9FAFB;
        }
        .page-break {
            display:block;
            height:0;
            margin:0;
            padding:0;
            border:none;
            page-break-after: always;
            break-after: page;
        }

        /* ---------- FOOTER ---------- */
        .footer {
            margin-top:22px;
            padding-top:10px;
            border-top:1px solid #E5E7EB;
            text-align:right;
            font-size:8pt;
            color:#6B7280;
            line-height:1.5;
        }

        /* ========== SCREEN PREVIEW ADAPTIVE ========== */
        @media screen {
            body { background:#F1F5F9; }
        }
        @media screen and (max-width: 640px) {
            .no-print { padding:10px 12px; gap:8px; }
            .no-print button, .no-print a { flex:1 1 auto; min-width: 110px; padding:10px 12px; font-size:12.5px; }
            .doc { margin:12px auto; border-radius:10px; max-width:100%; }
            .doc-inner { padding: 16px 14px 18px; }
            .header-logo-cell { width:52px; padding-right:10px; }
            .header-logo-cell img { width:44px; max-height:44px; }
            .header-text-cell h1 { font-size:13.5px; }
            .header-text-cell p { font-size:9px; line-height:1.4; }
            .title { margin:14px 0 14px; }
            .title h2 { font-size:12pt; }
            .title-sub { font-size:9pt; }
            .section-title { font-size:9.5pt; }
            .ident-table td { font-size:9.5pt; padding:4px 0; }
            .ident-label { font-size:8.5pt; width:36%; }
            .ident-value { font-size:9.5pt; }
            .teks-content { font-size:10.5pt; line-height:1.58; }
            .foto-img { max-height: 420px; }
            .footer { font-size:7.5pt; text-align:center; }
        }
        @media screen and (max-width: 360px) {
            .doc-inner { padding:12px 10px 14px; }
            .ident-label { width:38%; }
        }

        /* ========== PRINT STYLES ========== */
        @media print {
            html, body { background:#fff !important; }
            body { margin:0; padding:0; }
            .no-print { display:none !important; }
            .doc {
                max-width:none !important;
                width:auto !important;
                margin:0 !important;
                border:none !important;
                box-shadow:none !important;
                border-radius:0 !important;
            }
            .doc-inner {
                padding:0 !important;
            }
            .header-table { border-bottom-color:#0F4C81 !important; }
            .teks-content { background: transparent !important; }
            /* Foto print: utilise max page height, keep aspect */
            .foto-img {
                max-height: 115mm !important; /* max 2 per page */
                width:100% !important;
                height:auto !important;
                object-fit: contain !important;
                border:1px solid #D1D5DB !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .foto-block {
                margin-bottom: 10mm !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
            /* Enforce max 2 per page via CSS + Blade page-break div */
            .foto-block:nth-child(2n):not(:last-child) {
                /* fallback CSS, Blade inserts .page-break div anyway */
            }
            /* Avoid cutting header/title */
            .title, .section-title, .ident-table { break-after: avoid; page-break-after: avoid; }
            .footer { margin-top:14mm; }
            /* Ensure text flows */
            .teks-content p { orphans:3; widows:3; }
            a { color: inherit; text-decoration: none; }
        }

        /* ========== DOMPDF SPECIFIC: dompdf uses CPDF, respects @page and page-break-inside */
        /* No extra needed; base styles above already dompdf-friendly (no flex for doc). */
    </style>
</head>
<body>
    @php
        $namaSiswa = $laporan->user?->nama ?? $laporan->user?->username ?? '-';
        $kelas = $laporan->user?->kelas ?? $laporan->user?->kelas_jurusan ?? null;
        $jurusan = $laporan->user?->jurusan ?? null;
        $kelasJurusan = null;
        if ($kelas && $jurusan) $kelasJurusan = $kelas . ' / ' . $jurusan;
        elseif ($kelas) $kelasJurusan = $kelas;
        elseif ($jurusan) $kelasJurusan = $jurusan;
        $tgl = $laporan->tanggal_laporan ? \Carbon\Carbon::parse($laporan->tanggal_laporan)->translatedFormat('d F Y') : '-';
        $perusahaan = $lokasi['nama'] ?? 'PT Sandya Network';
        $alamatPerusahaan = $lokasi['alamat'] ?? '';
        // Prepare paragraphs for teks: split by 2+ line breaks, preserve single breaks inside para
        $teksRaw = trim((string)($laporan->teks_laporan ?? ''));
        $paras = preg_split('/\R{2,}/u', $teksRaw);
        if ($paras === false || empty($paras[0])) {
            $paras = $teksRaw !== '' ? [$teksRaw] : [];
        }
    @endphp

    <div class="no-print">
        <button class="btn-print" onclick="window.print()"><span style="font-size:15px;">🖨</span> Cetak / Save as PDF</button>
        <a class="btn-back" href="{{ route('laporan.index') }}">← Kembali</a>
        <a class="btn-back" href="{{ route('laporan.show', $laporan->id) }}">Lihat Detail</a>
    </div>

    <div class="doc">
        <div class="doc-inner">
            <!-- 1. HEADER + LOGO -->
            <table class="header-table" role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="header-logo-cell">
                        @if(!empty($logoBase64))
                            <img src="{{ $logoBase64 }}" alt="Logo {{ $perusahaan }}">
                        @else
                            <img src="{{ asset('images/logo-sandya.png') }}" alt="Logo" onerror="this.style.display='none'">
                        @endif
                    </td>
                    <td class="header-text-cell">
                        <h1>{{ $perusahaan }}</h1>
                        <p>{{ $alamatPerusahaan ?: 'Sistem Absensi PKL — Laporan Kegiatan Siswa' }}</p>
                    </td>
                </tr>
            </table>

            <!-- 2. JUDUL LAPORAN -->
            <div class="title">
                <h2>LAPORAN KEGIATAN SISWA PKL</h2>
                @if(!empty($alamatPerusahaan) && $perusahaan !== ($lokasi['nama'] ?? ''))
                    <div class="title-sub">{{ $perusahaan }}</div>
                @endif
            </div>

            <!-- 3. IDENTITAS SISWA -->
            <div class="section">
                <div class="section-title">Identitas Siswa</div>
                <table class="ident-table" role="presentation">
                    <tr>
                        <td class="ident-label">Nama Siswa</td>
                        <td class="ident-sep">:</td>
                        <td class="ident-value"><strong>{{ $namaSiswa }}</strong></td>
                    </tr>
                    <tr>
                        <td class="ident-label">User ID</td>
                        <td class="ident-sep">:</td>
                        <td class="ident-value">{{ $laporan->user?->user_id ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="ident-label">Kelas</td>
                        <td class="ident-sep">:</td>
                        <td class="ident-value">{{ $kelasJurusan ?: '-' }}</td>
                    </tr>
                    <tr>
                        <td class="ident-label">Perusahaan</td>
                        <td class="ident-sep">:</td>
                        <td class="ident-value">{{ $perusahaan }}</td>
                    </tr>
                    <tr>
                        <td class="ident-label">Tanggal Laporan</td>
                        <td class="ident-sep">:</td>
                        <td class="ident-value">{{ $tgl }}</td>
                    </tr>
                </table>
            </div>

            <!-- 4 & 5. DESKRIPSI KEGIATAN + ISI TEKS -->
            <div class="section">
                <div class="section-title">Deskripsi Kegiatan</div>
                <div class="teks-content">
                    @if(!empty($paras))
                        @foreach($paras as $para)
                            @php
                                $paraTrim = trim($para);
                                // preserve single line breaks within paragraph as <br> for lists, but keep justify
                                $paraHtml = nl2br(e($paraTrim), false);
                            @endphp
                            <p>{!! $paraHtml !!}</p>
                        @endforeach
                    @else
                        <p style="color:#9CA3AF; font-style:italic; text-align:center;">Tidak ada deskripsi kegiatan.</p>
                    @endif
                </div>
            </div>

            <!-- 6 & 7. DOKUMENTASI KEGIATAN + FOTO -->
            <div class="section foto-section">
                <div class="section-title">Dokumentasi Kegiatan</div>

                @if(!empty($fotoBase64s) && count($fotoBase64s) > 0)
                    @foreach($fotoBase64s as $idx => $foto)
                        <div class="foto-block">
                            <div class="foto-number">Foto {{ $idx + 1 }} dari {{ count($fotoBase64s) }}</div>
                            <img src="{{ $foto['base64'] }}" alt="Dokumentasi {{ $idx + 1 }}" class="foto-img" loading="lazy">
                            @if(!empty($foto['original_name']))
                                <div class="foto-caption">{{ $foto['original_name'] }}</div>
                            @endif
                        </div>
                        {{-- Page break after every 2 fotos, except after last --}}
                        @if( ($idx + 1) % 2 === 0 && ($idx + 1) < count($fotoBase64s))
                            <div class="page-break"></div>
                        @endif
                    @endforeach
                @elseif(!empty($fotoBase64))
                    <div class="foto-block">
                        <div class="foto-number">Foto 1 dari 1</div>
                        <img src="{{ $fotoBase64 }}" alt="Dokumentasi" class="foto-img">
                        @if(!empty($laporan->foto_original_name))
                            <div class="foto-caption">{{ $laporan->foto_original_name }}</div>
                        @endif
                    </div>
                @elseif(!empty($laporan->foto))
                    {{-- Fallback: route image (browser only, dompdf will miss but base64 above covers it) --}}
                    <div class="foto-block">
                        <div class="foto-number">Foto 1 dari 1</div>
                        <img src="{{ route('laporan.foto', $laporan->id) }}" alt="Dokumentasi" class="foto-img" onerror="this.style.display='none'; document.getElementById('noFotoFallback').style.display='block'">
                        <div id="noFotoFallback" style="display:none;" class="no-foto">Foto tidak dapat dimuat. Silakan buka via detail laporan.</div>
                    </div>
                @else
                    <div class="no-foto">Tidak ada foto dokumentasi untuk laporan ini.</div>
                @endif
            </div>

            <!-- FOOTER -->
            <div class="footer">
                Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB &nbsp;|&nbsp; ID Laporan: #{{ $laporan->id }} &nbsp;|&nbsp; {{ $namaSiswa }} — {{ $perusahaan }}
            </div>
        </div>
    </div>

    <script>
        // Auto-print if ?autoprint=1
        if (new URLSearchParams(location.search).get('autoprint') === '1') {
            window.addEventListener('load', () => setTimeout(()=>window.print(), 450));
        }
        // Ensure html overflow not hidden on mobile
        document.documentElement.style.overflowX = 'hidden';
        document.body.style.overflowX = 'hidden';
    </script>
</body>
</html>
