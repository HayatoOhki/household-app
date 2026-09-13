@extends('layouts.app')

@section('title', '口座追加')

@section('content')
    <header>
        <h1>口座追加</h1>

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
            action="{{ route('accounts.store') }}"
        >
            @csrf

            <div>
                <label for="name">
                    口座名
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

            <div>
                <label for="type">
                    口座種別
                </label>

                <select
                    id="type"
                    name="type"
                    required
                >
                    @foreach ($accountTypes as $accountType)
                        <option
                            value="{{ $accountType->value }}"
                            @selected(
                                old(
                                    'type',
                                    'bank'
                                ) === $accountType->value
                            )
                        >
                            {{ $accountType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit">
                登録
            </button>
        </form>
    </main>
@endsection