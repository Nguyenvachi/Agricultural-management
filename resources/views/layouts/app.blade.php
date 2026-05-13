<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agricultural Management')</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #333;
        }

        /* ── Nav ── */
        nav {
            background: #2d6a4f;
            padding: 10px 20px;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            margin-right: 6px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        nav br {
            line-height: 2;
        }

        /* ── Main content ── */
        main {
            padding: 20px 28px;
        }

        h1 {
            font-size: 1.4em;
            margin-bottom: 12px;
        }

        h3 {
            font-size: 1.1em;
            margin-top: 16px;
        }

        fieldset {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 12px 16px;
            margin-bottom: 12px;
        }

        legend {
            font-weight: bold;
            padding: 0 6px;
        }

        /* ── Tables ── */
        table {
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            vertical-align: middle;
        }

        table thead {
            background: #e8f5e9;
        }

        table tfoot {
            background: #f1f8e9;
            font-weight: bold;
        }

        table tbody tr:hover {
            background: #fafafa;
        }

        /* ── Forms ── */
        input[type=text],
        input[type=number],
        input[type=date],
        input[type=password],
        select,
        textarea {
            padding: 4px 6px;
            border: 1px solid #bbb;
            border-radius: 3px;
            font-size: 14px;
        }

        button[type=submit] {
            background: #2d6a4f;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        button[type=submit]:hover {
            background: #1b4332;
        }

        button[type=button] {
            padding: 5px 12px;
            border-radius: 3px;
            cursor: pointer;
        }

        /* ── Flash messages ── */
        .flash-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 10px 16px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .flash-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 10px 16px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        /* ── Links ── */
        a {
            color: #2d6a4f;
        }

        a:hover {
            color: #1b4332;
        }

        /* ── Pagination ── */
        .pagination {
            margin-top: 10px;
        }

        .pagination span,
        .pagination a {
            margin-right: 4px;
        }
    </style>
</head>

<body>
    <nav>
        <a href="{{ route('agencies.index') }}">Đại lý</a> |
        <a href="{{ route('categories.index') }}">Danh mục</a> |
        <a href="{{ route('items.index') }}">Mặt hàng</a> |
        <a href="{{ route('price-lists.index') }}">Bảng giá</a> |
        <a href="{{ route('users.index') }}">Người dùng</a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="{{ route('inventories.index') }}">Tồn kho</a> |
        <a href="{{ route('inventory-transactions.index') }}">Biến động kho</a>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="{{ route('orders.index') }}">Đơn hàng</a>
    </nav>

    <main>
        @if (session('success'))
            <div class="flash-success">✅ {{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash-error">
                <strong>⚠️ Dữ liệu không hợp lệ:</strong>
                <ul style="margin:6px 0 0 16px; padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>

</html>