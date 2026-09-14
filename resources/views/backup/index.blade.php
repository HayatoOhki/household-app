@extends('layouts.app')

@section('title', 'バックアップ・復元')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">
                バックアップ・復元
            </h1>

            <p class="page-description">
                家計簿データをJSONファイルに保存し、
                必要なときに復元できます。
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="backup-message backup-message-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="backup-message backup-message-error">
            <strong>
                復元できませんでした。
            </strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="backup-grid">
        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    バックアップ
                </h2>
            </div>

            <div class="panel-body">
                <p class="backup-description">
                    現在の家計簿データを
                    1つのJSONファイルとしてダウンロードします。
                </p>

                <div class="backup-target">
                    <p class="backup-target-title">
                        保存されるデータ
                    </p>

                    <ul>
                        <li>口座</li>
                        <li>カテゴリ</li>
                        <li>取引</li>
                        <li>振替</li>
                        <li>取引補助設定</li>
                        <li>初期残高</li>
                    </ul>
                </div>

                <p class="backup-note">
                    ログイン情報やパスワードは
                    バックアップに含まれません。
                </p>

                <form
                    method="GET"
                    action="{{ route('backup.download') }}"
                >
                    <button
                        type="submit"
                        class="button button-primary"
                    >
                        バックアップをダウンロード
                    </button>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    復元
                </h2>
            </div>

            <div class="panel-body">
                <div class="backup-warning">
                    <strong>
                        復元すると現在の家計簿データは
                        すべて置き換えられます。
                    </strong>

                    <p>
                        現在の口座・カテゴリ・取引・振替・
                        取引補助設定・初期残高を削除し、
                        選択したバックアップの内容に戻します。
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('backup.restore') }}"
                    enctype="multipart/form-data"
                    id="restore-form"
                >
                    @csrf

                    <div class="backup-field">
                        <label
                            for="backup_file"
                            class="backup-label"
                        >
                            バックアップファイル
                        </label>

                        <input
                            type="file"
                            id="backup_file"
                            name="backup_file"
                            accept=".json,application/json"
                            class="backup-file"
                            required
                        >

                        <p class="backup-help">
                            このアプリからダウンロードした
                            JSONファイルを選択してください。
                        </p>
                    </div>

                    <label class="backup-confirm">
                        <input
                            type="checkbox"
                            name="confirm_restore"
                            value="1"
                            required
                        >

                        <span>
                            現在の家計簿データが
                            すべて置き換えられることを確認しました
                        </span>
                    </label>

                    <button
                        type="submit"
                        class="button backup-restore-button"
                    >
                        バックアップから復元
                    </button>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .backup-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1fr)
                minmax(0, 1fr);
            gap: 24px;
        }

        .backup-grid .panel + .panel {
            margin-top: 0;
        }

        .backup-description {
            margin: 0 0 20px;
            color: var(--text-subtle);
            line-height: 1.8;
        }

        .backup-target {
            padding: 18px 20px;
            margin-bottom: 18px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface-subtle);
        }

        .backup-target-title {
            margin: 0 0 10px;
            font-weight: 700;
        }

        .backup-target ul {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 6px 24px;
            margin: 0;
            padding-left: 20px;
            color: var(--text-subtle);
        }

        .backup-note {
            margin: 0 0 22px;
            color: var(--text-subtle);
            font-size: 13px;
        }

        .backup-warning {
            padding: 16px 18px;
            margin-bottom: 22px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #991b1b;
        }

        .backup-warning strong {
            display: block;
            margin-bottom: 6px;
        }

        .backup-warning p {
            margin: 0;
            line-height: 1.7;
        }

        .backup-field {
            margin-bottom: 20px;
        }

        .backup-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .backup-file {
            display: block;
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: #ffffff;
        }

        .backup-help {
            margin: 7px 0 0;
            color: var(--text-subtle);
            font-size: 12px;
        }

        .backup-confirm {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .backup-confirm input {
            margin-top: 3px;
        }

        .backup-restore-button {
            border-color: #dc2626;
            background: #dc2626;
            color: #ffffff;
        }

        .backup-restore-button:hover {
            background: #b91c1c;
        }

        .backup-message {
            padding: 14px 18px;
            margin-bottom: 24px;
            border-radius: 8px;
        }

        .backup-message-success {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }

        .backup-message-error {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .backup-message-error ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document
            .getElementById('restore-form')
            ?.addEventListener('submit', function (event) {
                const confirmed = window.confirm(
                    '現在の家計簿データをすべて削除し、'
                    + '選択したバックアップの内容に置き換えます。\n\n'
                    + '本当に復元しますか？'
                );

                if (! confirmed) {
                    event.preventDefault();
                }
            });
    </script>
@endpush