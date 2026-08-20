<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Statement</title>
    <style>
        @page {
            margin: 28px 32px 46px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #1f2937;
            margin: 0;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .header td {
            vertical-align: bottom;
        }

        .app-name {
            font-size: 12px;
            font-weight: bold;
            color: #4f46e5;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }

        .title {
            font-size: 19px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .meta {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
            line-height: 1.5;
        }

        .meta strong {
            color: #111827;
        }

        .summary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 0 18px -8px;
            width: calc(100% + 16px);
        }

        .summary-cell {
            width: 25%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-top: 3px solid #9ca3af;
            border-radius: 4px;
            padding: 9px 10px;
        }

        .summary-cell.in { border-top-color: #10b981; }
        .summary-cell.out { border-top-color: #f43f5e; }
        .summary-cell.balance { border-top-color: #4f46e5; }

        .summary-label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #6b7280;
            margin: 0 0 4px 0;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        table.ledger {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.ledger thead th {
            background: #4f46e5;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
            padding: 7px 8px;
        }

        table.ledger th.amount, table.ledger td.amount {
            text-align: right;
        }

        table.ledger tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
            vertical-align: top;
        }

        table.ledger tbody tr.alt td {
            background: #f9fafb;
        }

        .cash-in { color: #059669; font-weight: bold; }
        .cash-out { color: #e11d48; font-weight: bold; }
        .balance-cell { font-weight: bold; color: #111827; }

        .muted { color: #9ca3af; }

        .empty {
            text-align: center;
            padding: 30px 0;
            color: #9ca3af;
            font-size: 10px;
        }

        .footer {
            position: fixed;
            bottom: -32px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <p class="app-name">{{ config('app.name') }}</p>
                <p class="title">Petty Cash Statement</p>
            </td>
            <td class="meta">
                <div><strong>{{ $user->name }}</strong></div>
                <div>{{ $user->email }}</div>
                <div>
                    Period:
                    @if ($from || $to)
                        {{ $from ? \Illuminate\Support\Carbon::parse($from)->format('M j, Y') : 'Start' }}
                        &ndash;
                        {{ $to ? \Illuminate\Support\Carbon::parse($to)->format('M j, Y') : 'Today' }}
                    @else
                        All time
                    @endif
                </div>
                <div>Generated {{ now()->format('M j, Y \a\t g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="summary-cell balance">
                <p class="summary-label">Opening Balance</p>
                <p class="summary-value">{{ \Illuminate\Support\Number::currency($openingBalance, in: config('app.currency')) }}</p>
            </td>
            <td class="summary-cell in">
                <p class="summary-label">Total Cash In</p>
                <p class="summary-value">{{ \Illuminate\Support\Number::currency($totalCollected, in: config('app.currency')) }}</p>
            </td>
            <td class="summary-cell out">
                <p class="summary-label">Total Cash Out</p>
                <p class="summary-value">{{ \Illuminate\Support\Number::currency($totalExpenses, in: config('app.currency')) }}</p>
            </td>
            <td class="summary-cell balance">
                <p class="summary-label">Closing Balance</p>
                <p class="summary-value">{{ \Illuminate\Support\Number::currency($closingBalance, in: config('app.currency')) }}</p>
            </td>
        </tr>
    </table>

    @if ($transactions->isEmpty())
        <div class="empty">No transactions recorded for this period.</div>
    @else
        <table class="ledger">
            <thead>
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 14%;">Category</th>
                    <th style="width: 27%;">Notes</th>
                    <th class="amount" style="width: 14%;">Cash In</th>
                    <th class="amount" style="width: 14%;">Cash Out</th>
                    <th class="amount" style="width: 19%;">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $index => $transaction)
                    @php $isCollection = $transaction->type === 'collection'; @endphp
                    <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                        <td style="white-space: nowrap;">{{ $transaction->occurred_on->format('M j, Y') }}</td>
                        <td>{{ $transaction->category ?: '—' }}</td>
                        <td>{{ $transaction->description ?: '—' }}</td>
                        <td class="amount {{ $isCollection ? 'cash-in' : 'muted' }}">
                            {{ $isCollection ? \Illuminate\Support\Number::currency($transaction->amount, in: config('app.currency')) : '—' }}
                        </td>
                        <td class="amount {{ ! $isCollection ? 'cash-out' : 'muted' }}">
                            {{ ! $isCollection ? \Illuminate\Support\Number::currency($transaction->amount, in: config('app.currency')) : '—' }}
                        </td>
                        <td class="amount balance-cell">
                            {{ \Illuminate\Support\Number::currency($transaction->running_balance, in: config('app.currency')) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">{{ config('app.name') }} &middot; Petty Cash Statement &middot; Generated {{ now()->format('M j, Y g:i A') }}</div>
</body>
</html>
