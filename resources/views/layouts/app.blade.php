<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', '家計簿')</title>
</head>
<body>
    @yield('content')

    <script>
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            if (event.target.tagName === 'TEXTAREA') {
                return;
            }

            if (event.target.closest('form')) {
                event.preventDefault();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>