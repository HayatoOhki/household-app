@extends('layouts.app')

@section('title', '取引ルール追加')

@section('content')
    <h1>取引ルール追加</h1>

    <p>
        <a href="{{ route('transaction-rules.index') }}">
            取引ルール一覧へ戻る
        </a>
    </p>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form
        action="{{ route('transaction-rules.store') }}"
        method="POST"
    >
        @csrf

        <div>
            <label for="account_id">
                口座
            </label>

            <select
                id="account_id"
                name="account_id"
                required
            >
                <option value="">
                    選択してください
                </option>

                @foreach ($accounts as $account)
                    <option
                        value="{{ $account->id }}"
                        @selected(
                            (string) old('account_id')
                            === (string) $account->id
                        )
                    >
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label for="keyword">
                キーワード
            </label>

            <input
                type="text"
                id="keyword"
                name="keyword"
                value="{{ old('keyword') }}"
                maxlength="255"
                required
            >
        </div>

        <br>

        <div>
            <label for="display_name">
                表示名
            </label>

            <input
                type="text"
                id="display_name"
                name="display_name"
                value="{{ old('display_name') }}"
                maxlength="255"
            >

            <p>
                未入力の場合は元の取引先名をそのまま使用します。
            </p>
        </div>

        <br>

        <div>
            <label for="category_id">
                カテゴリ
            </label>

            <select
                id="category_id"
                name="category_id"
            >
                <option value="">
                    設定しない
                </option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected(
                            (string) old('category_id')
                            === (string) $category->id
                        )
                    >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <button type="submit">
            登録
        </button>
    </form>
@endsection