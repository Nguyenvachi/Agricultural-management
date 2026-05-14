<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập — AgroManage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1b5e20 0%, #2d6a4f 50%, #52b788 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .login-header {
            background: linear-gradient(135deg, #1b5e20, #2d6a4f);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 2rem;
            text-align: center;
        }

        .login-header .logo {
            font-size: 3rem;
        }

        .login-header h1 {
            font-size: 1.4rem;
            margin: .5rem 0 .25rem;
        }

        .login-header p {
            font-size: .85rem;
            opacity: .8;
            margin: 0;
        }

        .login-body {
            padding: 2rem;
        }

        .btn-login {
            background: linear-gradient(135deg, #1b5e20, #2d6a4f);
            border: none;
            font-weight: 600;
            letter-spacing: .5px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2d6a4f, #52b788);
        }
    </style>
</head>

<body>

    <div class="card login-card">
        <div class="login-header">
            <div class="logo">🌾</div>
            <h1>AgroManage</h1>
            <p>Hệ thống Quản lý Nông sản</p>
        </div>

        <div class="login-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="username">
                        <i class="bi bi-person me-1"></i>Tên đăng nhập
                    </label>
                    <input type="text" name="username" id="username"
                        class="form-control @error('username') is-invalid @enderror" value="{{ old('username') }}"
                        placeholder="admin" autocomplete="username" autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">
                        <i class="bi bi-lock me-1"></i>Mật khẩu
                    </label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="••••••••" autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Ghi nhớ đăng nhập</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-login w-100 py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Đăng nhập
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePwd').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    </script>
</body>

</html>
