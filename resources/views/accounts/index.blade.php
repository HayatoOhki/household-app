@extends('layouts.app')

@section('title', '口座一覧')

@section('content')
    <header>
        <h1>口座一覧</h1>

        <p>
            <a href="{{ route('home') }}">
                ホームへ戻る
            </a>
        </p>

        <p>
            <a href="{{ route('accounts.create') }}">
                口座を追加
            </a>
        </p>
    </header>

    <main>
        @if (session('success'))
            <p>
                {{ session('success') }}
            </p>
        @endif

        @if ($accounts->isEmpty())
            <p>
                登録されている口座はありません。
            </p>
        @else
            <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>口座名</th>
                        <th>操作</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($accounts as $account)
                        <tr>
                            <td>
                                {{ $account->name }}
                            </td>

                            <td>
                                <a href="{{ route('accounts.edit', $account) }}">
                                    編集
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('accounts.destroy', $account) }}"
                                    style="display: inline;"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        onclick="return confirm('この口座を削除しますか？')"
                                    >
                                        削除
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
@endsection