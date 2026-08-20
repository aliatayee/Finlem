<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $withRunningBalance = Transaction::query()
            ->where('user_id', $user->id)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->selectRaw("*, SUM(CASE WHEN type = 'collection' THEN amount ELSE -amount END) OVER (ORDER BY occurred_on, id) AS running_balance");

        $query = Transaction::query()
            ->fromSub($withRunningBalance, 'transactions')
            ->orderBy('occurred_on')
            ->orderBy('id');

        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;

        if ($from) {
            $query->whereDate('occurred_on', '>=', $from);
        }
        if ($to) {
            $query->whereDate('occurred_on', '<=', $to);
        }

        $transactions = $query->get();

        $totalCollected = $transactions->where('type', Transaction::TYPE_COLLECTION)->sum('amount');
        $totalExpenses = $transactions->where('type', Transaction::TYPE_EXPENSE)->sum('amount');

        $openingBalance = $transactions->isEmpty()
            ? $user->balance()
            : (float) $transactions->first()->running_balance - ($transactions->first()->type === Transaction::TYPE_COLLECTION ? (float) $transactions->first()->amount : -(float) $transactions->first()->amount);

        $closingBalance = $transactions->isEmpty() ? $openingBalance : (float) $transactions->last()->running_balance;

        $pdf = Pdf::loadView('pdf.transactions', [
            'user' => $user,
            'transactions' => $transactions,
            'from' => $from,
            'to' => $to,
            'totalCollected' => $totalCollected,
            'totalExpenses' => $totalExpenses,
            'openingBalance' => $openingBalance,
            'closingBalance' => $closingBalance,
        ])->setPaper('a4');

        $filename = Str::slug(config('app.name').'-petty-cash-'.$user->name.'-'.now()->format('Y-m-d')).'.pdf';

        return $pdf->download($filename);
    }
}
