<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Absensi PKL Sandya</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
</head>
<body>
    <div class="login-shell">
        <!-- ============ BRANDING KIRI ============ -->
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-wrap">
                    <img src="{{ asset('images/logo-sandya2.png') }}" alt="Sandya Absensi PKL" class="hero-logo">
                </div>
                <h1 class="hero-title">Sistem ABSENSI PKL</h1>
                <div class="hero-divider" aria-hidden="true"></div>
                <p class="hero-subtitle">Platform absensi modern untuk mendukung kegiatan PKL<br>PT Sandya Network — presisi, mudah, dan terintegrasi.</p>
            </div>
        </div>

        <!-- ============ FORM KANAN ============ -->
        <div class="login-card-wrapper">
            <div class="login-card">
                <div class="login-card-header">
                    <img src="{{ asset('images/logo-sandya.png') }}" alt="Sandya Absensi PKL" class="login-card-logo">
                    <h2 class="login-title">Selamat Datang Kembali</h2>
                    <p class="login-subtitle">Masuk ke akun Anda untuk melanjutkan</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @elseif ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        {{ $errors->first('message') ?: $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.process') }}">
                    @csrf

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan Username Anda" required autofocus>
                            <i class="fa-solid fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 8 karakter" required minlength="8">
                            <i class="fa-solid fa-eye input-icon" id="password-toggle"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Masuk</button>

                    <div class="forgot-wrap">
                        <a href="https://wa.me/62895341064278?text=Mbak%2C%20mau%20ganti%20password." class="forgot-link" target="_blank" rel="noopener noreferrer">Lupa Password?</a>
                    </div>
                </form>

                <p class="login-footer">© 2026 PT Sandya Network • Sistem Absensi PKL</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>
