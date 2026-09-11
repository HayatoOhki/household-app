@extends('layouts.app')

@section('title', '取引ルール管理')

@section('content')
    <h1>取引ルール管理</h1>

    <p>
        <a href="{{ route('home') }}">
            ホームへ戻る
        </a>
    </p>

    <p>
        <a href="{{ route('transaction-rules.create') }}">
            取引ルールを追加
        </a>
    </p>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    @if ($transactionRules->isEmpty())
        <p>取引ルールはまだ登録されていません。</p>
    @else
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>口座</th>
                    <th>キーワード</th>
                    <th>表示名</th>
                    <th>カテゴリ</th>
                    <th>操作</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($transactionRules as $transactionRule)
                    <tr>
                        <td>
                            {{ $transactionRule->account?->name ?? '-' }}
                        </td>

                        <td>
                            {{ $transactionRule->keyword }}
                        </td>

                        <td>
                            {{ $transactionRule->display_name ?? '-' }}
                        </td>

                        <td>
                            {{ $transactionRule->category?->name ?? '-' }}
                        </td>

                        <td>
                            <a
                                href="{{ route(
                                    'transaction-rules.edit',
                                    $transactionRule
                                ) }}"
                            >
                                編集
                            </a>

                            <form
                                action="{{ route(
                                    'transaction-rules.destroy',
                                    $transactionRule
                                ) }}"
                                method="POST"
                                style="display: inline;"
                            >
                                @csrf
                                @method('DELETE')

                                <button
                                    type="submit"
                                    onclick="return confirm(
                                        'この取引ルールを削除しますか？'
                                    )"
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
@endsection