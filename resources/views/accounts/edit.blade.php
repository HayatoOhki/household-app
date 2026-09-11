@extends('layouts.app')

@section('title', '口座編集')

@section('content')
    <header>
        <h1>口座編集</h1>

        <p>
            <a href="{{ route('accounts.index') }}">
                口座一覧へ戻る
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

        <form
            method="POST"
            action="{{ route('accounts.update', $account) }}"
        >
            @csrf
            @method('PUT')

            <div>
                <label for="name">
                    口座名
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $account->name) }}"
                    maxlength="100"
                    required
                >
            </div>

            <button type="submit">
                更新
            </button>
        </form>
    </main>
@endsection