<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Agricultural Management')</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <nav>
            <a href="{{ route('agencies.index') }}">Đại lý</a> |
            <a href="{{ route('categories.index') }}">Danh mục</a> |
            <a href="{{ route('items.index') }}">Mặt hàng</a> |
            <a href="{{ route('price-lists.index') }}">Bảng giá</a> |
            <a href="{{ route('users.index') }}">Người dùng</a>
            <br>
            <a href="{{ route('inventories.index') }}">Tồn kho</a> |
            <a href="{{ route('inventory-transactions.index') }}">Biến động kho</a>
            <br>
            <a href="{{ route('orders.index') }}">Đơn hàng</a>
        </nav>

        <hr>

        @if (session('success'))
            <p><strong>{{ session('success') }}</strong></p>
        @endif

        @if ($errors->any())
            <div>
                <p><strong>Dữ liệu không hợp lệ:</strong></p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </body>
</html>
