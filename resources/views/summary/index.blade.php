@extends('layouts.app')

@section('title', '年間収支')

@push('styles')
<style>
    .annual-summary {
        width: calc(100vw - 240px);
        margin-left: calc((100vw - 240px - 100%) / -2);
        padding: 8px 24px 48px;
        box-sizing: border-box;
    }

    .annual-summary__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        margin-bottom: 28px;
    }

    .annual-summary__title {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        color: #111827;
    }

    .year-switcher {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
    }

    .year-switcher__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #4b5563;
        font-size: 22px;
        line-height: 1;
        text-decoration: none;
    }

    .year-switcher__button:hover {
        background: #f3f4f6;
        color: #111827;
    }

    .year-switcher__year {
        min-width: 82px;
        color: #111827;
        font-size: 18px;
        font-weight: 700;
        text-align: center;
        white-space: nowrap;
    }

    .annual-table-wrap {
        width: 100%;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #ffffff;
    }

    .annual-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: auto;
    }

    .annual-table th,
    .annual-table td {
        height: 50px;
        padding: 10px 6px;
        border-right: 1px solid #f0f1f3;
        border-bottom: 1px solid #f0f1f3;
        font-size: 12px;
        white-space: nowrap;
        box-sizing: border-box;
    }

    .annual-table th:last-child,
    .annual-table td:last-child {
        border-right: 0;
    }

    .annual-table tbody tr:last-child td {
        border-bottom: 0;
    }

    /*
     * 項目名の列は固定幅を持たせず、
     * 最長の文言に合わせて自動調整する。
     */
    .annual-table th:first-child,
    .annual-table td:first-child {
        width: 1%;
        white-space: nowrap;
    }

    /*
     * 金額列は約9桁＋カンマ＋円を想定。
     */
    .annual-table th:not(:first-child),
    .annual-table td:not(:first-child) {
        width: 94px;
        max-width: 94px;
    }

    .annual-table thead th {
        background: #f9fafb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
    }

    .annual-table__label {
        padding-left: 12px;
        padding-right: 12px;
        color: #111827;
        font-weight: 600;
        text-align: center;
    }

    .annual-table__amount {
        color: #374151;
        font-size: 12px;
        font-variant-numeric: tabular-nums;
        text-align: right;
    }

    .annual-table__average {
        background: #fafafa;
        font-weight: 600;
    }

    .annual-table__total {
        background: #f8fafc;
        color: #111827;
        font-weight: 700;
    }

    .annual-table__summary-row td {
        height: 54px;
        font-weight: 700;
    }

    .annual-table__income td:not(:first-child) {
        color: #15803d;
    }

    .annual-table__expense td:not(:first-child) {
        color: #dc2626;
    }

    .annual-table__balance td:not(:first-child) {
        color: #2563eb;
    }

    .annual-table__section td {
        height: 48px;
        padding-top: 18px;
        padding-bottom: 10px;
        border-right: 0;
        background: #f8fafc;
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        text-align: left;
    }

    .annual-table__empty td {
        height: 52px;
        color: #9ca3af;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="annual-summary">

    <div class="annual-summary__header">
        <h1 class="annual-summary__title">
            年間収支
        </h1>

        <div class="year-switcher">
            <a
                href="{{ route('summary.index', ['year' => $previousYear]) }}"
                class="year-switcher__button"
                aria-label="{{ $previousYear }}年を表示"
            >
                ‹
            </a>

            <span class="year-switcher__year">
                {{ $year }}年
            </span>

            <a
                href="{{ route('summary.index', ['year' => $nextYear]) }}"
                class="year-switcher__button"
                aria-label="{{ $nextYear }}年を表示"
            >
                ›
            </a>
        </div>
    </div>

    <div class="annual-table-wrap">
        <table class="annual-table">

            <thead>
                <tr>
                    <th>項目</th>

                    @for ($month = 1; $month <= 12; $month++)
                        <th>{{ $month }}月</th>
                    @endfor

                    <th>平均</th>
                    <th>合計</th>
                </tr>
            </thead>

            <tbody>

                @foreach ($summaryRows as $row)

                    @if ($row['label'] === '収入')
                        <tr class="annual-table__summary-row annual-table__income">
                    @elseif ($row['label'] === '支出')
                        <tr class="annual-table__summary-row annual-table__expense">
                    @else
                        <tr class="annual-table__summary-row annual-table__balance">
                    @endif

                        <td class="annual-table__label">
                            {{ $row['label'] }}
                        </td>

                        @for ($month = 1; $month <= 12; $month++)
                            <td class="annual-table__amount">
                                {{ number_format($row['months'][$month]) }}円
                            </td>
                        @endfor

                        <td class="annual-table__amount annual-table__average">
                            {{ number_format($row['average']) }}円
                        </td>

                        <td class="annual-table__amount annual-table__total">
                            {{ number_format($row['total']) }}円
                        </td>

                    </tr>

                @endforeach


                <tr class="annual-table__section">
                    <td colspan="15">
                        カテゴリ別支出
                    </td>
                </tr>

                @forelse ($categoryRows as $row)

                    <tr>
                        <td class="annual-table__label">
                            {{ $row['label'] }}
                        </td>

                        @for ($month = 1; $month <= 12; $month++)
                            <td class="annual-table__amount">
                                {{ number_format($row['months'][$month]) }}円
                            </td>
                        @endfor

                        <td class="annual-table__amount annual-table__average">
                            {{ number_format($row['average']) }}円
                        </td>

                        <td class="annual-table__amount annual-table__total">
                            {{ number_format($row['total']) }}円
                        </td>
                    </tr>

                @empty

                    <tr class="annual-table__empty">
                        <td colspan="15">
                            この年のカテゴリ別支出はありません。
                        </td>
                    </tr>

                @endforelse


                <tr class="annual-table__section">
                    <td colspan="15">
                        クレジットカード別支出
                    </td>
                </tr>

                @forelse ($creditCardRows as $row)

                    <tr>
                        <td class="annual-table__label">
                            {{ $row['label'] }}
                        </td>

                        @for ($month = 1; $month <= 12; $month++)
                            <td class="annual-table__amount">
                                {{ number_format($row['months'][$month]) }}円
                            </td>
                        @endfor

                        <td class="annual-table__amount annual-table__average">
                            {{ number_format($row['average']) }}円
                        </td>

                        <td class="annual-table__amount annual-table__total">
                            {{ number_format($row['total']) }}円
                        </td>
                    </tr>

                @empty

                    <tr class="annual-table__empty">
                        <td colspan="15">
                            この年のクレジットカード支出はありません。
                        </td>
                    </tr>

                @endforelse


                <tr class="annual-table__section">
                    <td colspan="15">
                        口座別支出
                    </td>
                </tr>

                @forelse ($accountRows as $row)

                    <tr>
                        <td class="annual-table__label">
                            {{ $row['label'] }}
                        </td>

                        @for ($month = 1; $month <= 12; $month++)
                            <td class="annual-table__amount">
                                {{ number_format($row['months'][$month]) }}円
                            </td>
                        @endfor

                        <td class="annual-table__amount annual-table__average">
                            {{ number_format($row['average']) }}円
                        </td>

                        <td class="annual-table__amount annual-table__total">
                            {{ number_format($row['total']) }}円
                        </td>
                    </tr>

                @empty

                    <tr class="annual-table__empty">
                        <td colspan="15">
                            この年の口座支出はありません。
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>
    </div>

</div>
@endsection