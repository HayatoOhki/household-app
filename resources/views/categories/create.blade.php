@extends('layouts.app')

@section('title', 'カテゴリ追加')

@section('content')
    <header>
        <h1>カテゴリ追加</h1>

        <p>
            <a href="{{ route('categories.index') }}">
                カテゴリ一覧へ戻る
            </a>
        </p>
    </header>

    <main>
        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('categories.store') }}">
            @csrf

            <div>
                <label for="name">
                    カテゴリ名
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    maxlength="100"
                    required
                >
            </div>

            <button type="submit">
                登録
            </button>
        </form>
    </main>
@endsection