<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家計簿</title>
</head>
<body>
    <header>
        <h1>家計簿</h1>

        <p>
            {{ auth()->user()->name }} さん、こんにちは。
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">ログアウト</button>
        </form>
    </header>

    <main>
        <h2>ホーム</h2>
        <p>ここに家計簿のダッシュボードを作成します。</p>
    </main>
</body>
</html>