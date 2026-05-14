<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agricultural Management')</title>
    {{-- Bootstrap 5 CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: .5px;
        }

        .nav-link {
            font-size: .92rem;
        }

        .table th {
            white-space: nowrap;
        }

        .badge-active {
            background: #198754;
        }

        .badge-inactive {
            background: #6c757d;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }
    </style>
</head>

<body>

    {{-- ── Navbar ── --}}
    <nav class="navbar navbar-expand-lg navbar-dark" style="background:#1b5e20;">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                🌾 AgroManage
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    {{-- Dashboard: ADMIN + AGENCY only (FIX 2) --}}
                    @if (auth()->user()->isAdmin() || auth()->user()->isAgency())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                    @endif

                    {{-- ADMIN only: Master Data --}}
                    @if (auth()->user()->isAdmin())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('agencies.*') ? 'active' : '' }}"
                                href="{{ route('agencies.index') }}">
                                <i class="bi bi-building"></i> Đại lý
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                                href="{{ route('categories.index') }}">
                                <i class="bi bi-tags"></i> Danh mục
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('items.*') ? 'active' : '' }}"
                                href="{{ route('items.index') }}">
                                <i class="bi bi-box-seam"></i> Mặt hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">
                                <i class="bi bi-people"></i> Người dùng
                            </a>
                        </li>
                    @endif

                    {{-- Price List: ADMIN + AGENCY + FARMER --}}
                    @if (auth()->user()->isAdmin() || auth()->user()->isAgency() || auth()->user()->isFarmer())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('price-lists.*') ? 'active' : '' }}"
                                href="{{ route('price-lists.index') }}">
                                <i class="bi bi-currency-dollar"></i> Bảng giá
                            </a>
                        </li>
                    @endif

                    {{-- Inventory: ADMIN + AGENCY --}}
                    @if (auth()->user()->isAdmin() || auth()->user()->isAgency())
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ request()->routeIs('inventories.*') || request()->routeIs('inventory-transactions.*') ? 'active' : '' }}"
                                href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-archive"></i> Kho
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventories.index') }}">
                                        <i class="bi bi-clipboard-data"></i> Tồn kho
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('inventory-transactions.index') }}">
                                        <i class="bi bi-arrow-left-right"></i> Biến động kho
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- Orders: tất cả role --}}
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}"
                            href="{{ route('orders.index') }}">
                            <i class="bi bi-receipt"></i> Đơn hàng
                        </a>
                    </li>
                </ul>

                {{-- User info + Logout --}}
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <strong>{{ auth()->user()->full_name }}</strong>
                            <span class="badge bg-light text-dark ms-1" style="font-size:.7rem;">
                                {{ auth()->user()->role?->display_name }}
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text text-muted small">
                                    <i class="bi bi-person me-1"></i>{{ auth()->user()->username }}
                                </span>
                            </li>
                            <li>
                                <span class="dropdown-item-text text-muted small">
                                    <i class="bi bi-shield me-1"></i>{{ auth()->user()->role?->code }}
                                </span>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- ── Main content ── --}}
    <div class="container-fluid py-4 px-3 px-md-4">

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Dữ liệu không hợp lệ:</strong>
                <ul class="mb-0 mt-1 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
