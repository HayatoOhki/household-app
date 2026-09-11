@extends('layouts.app')

@section('title', 'カテゴリ一覧')

@section('content')
    <header>
        <h1>カテゴリ一覧</h1>

        <p>
            <a href="{{ route('home') }}">
                ホームへ戻る
            </a>
        </p>

        <p>
            <a href="{{ route('categories.create') }}">
                カテゴリを追加
            </a>
        </p>
    </header>

    <main>
        @if (session('success'))
            <p>
                {{ session('success') }}
            </p>
        @endif

        @if ($categories->isEmpty())
            <p>
                登録されているカテゴリはありません。
            </p>
        @else
            <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>カテゴリ名</th>
                        <th>操作</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>
                                {{ $category->name }}
                            </td>

                            <td>
                                <a href="{{ route('categories.edit', $category) }}">
                                    編集
                                </a>

                                <form
                                    method="POST"
                                    action="{{ route('categories.destroy', $category) }}"
                                    style="display: inline;"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        onclick="return confirm('このカテゴリを削除しますか？')"
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