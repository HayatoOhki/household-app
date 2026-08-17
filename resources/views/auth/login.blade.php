<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン - 家計簿</title>
</head>
<body>
    <h1>家計簿</h1>

    <h2>ログイン</h2>

    @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email">メールアドレス</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
            >
        </div>

        <div>
            <label for="password">パスワード</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <div>
            <label>
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                >
                ログイン状態を保持する
            </label>
        </div>

        <button type="submit">ログイン</button>
    </form>

    @if (Route::has('register'))
        <p>
            <a href="{{ route('register') }}">新規登録</a>
        </p>
    @endif

    @if (Route::has('password.request'))
        <p>
            <a href="{{ route('password.request') }}">パスワードを忘れた場合</a>
        </p>
    @endif
</body>
</html>