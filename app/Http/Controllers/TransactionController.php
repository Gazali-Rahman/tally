<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request, $group_id)
    {
        $group = $request->user()->groups()->findOrFail($group_id);

        $query = $group->transactions()->with('user');

        if ($request->has('month')) {
            $query->whereMonth('transaction_date', $request->month);
        }

        if ($request->has('year')) {
            $query->whereYear('transaction_date', $request->year);
        }

        return response()->json([
            'transactions' => $query->orderBy('transaction_date', 'desc')
                                    ->orderBy('created_at', 'desc')
                                    ->orderBy('id', 'desc')
                                    ->get()
        ]);
    }

    public function store(Request $request, $group_id)
    {
        $group = $request->user()->groups()->findOrFail($group_id);

        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date'
        ]);

        $transaction = $group->transactions()->create([
            'user_id' => $request->user()->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'category' => $request->category,
            'description' => $request->description,
            'transaction_date' => $request->transaction_date
        ]);

        $transaction->load('user');

        try {
            broadcast(new \App\Events\TransactionCreatedEvent($transaction))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast TransactionCreated failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Transaksi berhasil dicatat',
            'transaction' => $transaction
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        
        $group = $request->user()->groups()->findOrFail($transaction->group_id);

        $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string',
            'transaction_date' => 'required|date'
        ]);

        $transaction->update($request->only(['type', 'amount', 'category', 'description', 'transaction_date']));
        $transaction->load('user');

        try {
            broadcast(new \App\Events\TransactionUpdatedEvent($transaction))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast TransactionUpdated failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Transaksi berhasil diubah',
            'transaction' => $transaction
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        
        $group = $request->user()->groups()->findOrFail($transaction->group_id);

        $transactionId = (int) $transaction->id;
        $groupId = (int) $transaction->group_id;
        $amount = (float) $transaction->amount;
        $type = (string) $transaction->type;
        $userName = $request->user()->name;

        $transaction->delete();

        try {
            broadcast(new \App\Events\TransactionDeletedEvent($transactionId, $groupId, $amount, $type, $userName))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast TransactionDeleted failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }

    public function summary(Request $request, $group_id)
    {
        $group = $request->user()->groups()->findOrFail($group_id);

        $income = $group->transactions()->where('type', 'income')->sum('amount');
        $expense = $group->transactions()->where('type', 'expense')->sum('amount');
        $balance = $income - $expense;

        return response()->json([
            'summary' => [
                'total_income' => $income,
                'total_expense' => $expense,
                'balance' => $balance
            ]
        ]);
    }

    public function analytics(Request $request, $group_id)
    {
        $group = $request->user()->groups()->findOrFail($group_id);

        $period = $request->query('period', 'this_month');
        $now = now();

        $query = $group->transactions();

        if ($period === 'this_month') {
            $query->whereMonth('transaction_date', $now->month)->whereYear('transaction_date', $now->year);
            $daysCount = max(1, $now->day);
        } elseif ($period === '3_months') {
            $query->where('transaction_date', '>=', $now->copy()->subMonths(2)->startOfMonth()->toDateString());
            $daysCount = max(1, $now->copy()->subMonths(2)->startOfMonth()->diffInDays($now));
        } elseif ($period === 'this_year') {
            $query->whereYear('transaction_date', $now->year);
            $daysCount = max(1, $now->dayOfYear);
        } else {
            // all time
            $earliest = $group->transactions()->min('transaction_date');
            $daysCount = $earliest ? max(1, \Carbon\Carbon::parse($earliest)->diffInDays($now)) : 1;
        }

        $periodIncome = (float)(clone $query)->where('type', 'income')->sum('amount');
        $periodExpense = (float)(clone $query)->where('type', 'expense')->sum('amount');
        $netSavings = $periodIncome - $periodExpense;
        $savingsRate = $periodIncome > 0 ? round(($netSavings / $periodIncome) * 100, 1) : 0;
        $dailyAvgExpense = $daysCount > 0 ? round($periodExpense / $daysCount) : 0;

        // Category breakdown for expense
        $categoryBreakdown = (clone $query)->where('type', 'expense')
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($periodExpense) {
                $item->total = (float)$item->total;
                $item->percentage = $periodExpense > 0 ? round(($item->total / $periodExpense) * 100, 1) : 0;
                return $item;
            });

        // Member contributions (Shared Wallet)
        $memberContributions = (clone $query)
            ->selectRaw('user_id, SUM(CASE WHEN type = "income" THEN amount ELSE 0 END) as income_total, SUM(CASE WHEN type = "expense" THEN amount ELSE 0 END) as expense_total, COUNT(*) as tx_count')
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->get()
            ->map(function ($item) use ($periodIncome, $periodExpense) {
                $item->income_total = (float)$item->income_total;
                $item->expense_total = (float)$item->expense_total;
                $item->income_percentage = $periodIncome > 0 ? round(($item->income_total / $periodIncome) * 100, 1) : 0;
                $item->expense_percentage = $periodExpense > 0 ? round(($item->expense_total / $periodExpense) * 100, 1) : 0;
                return $item;
            });

        // Top 5 largest expenses
        $topExpenses = (clone $query)->where('type', 'expense')
            ->with('user:id,name')
            ->orderByDesc('amount')
            ->limit(5)
            ->get();

        // 6-Month Cashflow Trend
        $monthlyTrend = [];
        $monthsNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        for ($i = 5; $i >= 0; $i--) {
            $targetDate = $now->copy()->subMonths($i);
            $m = (int)$targetDate->format('n');
            $y = (int)$targetDate->format('Y');
            $label = $monthsNames[$m - 1] . ' ' . $y;

            $inc = (float)$group->transactions()->where('type', 'income')->whereMonth('transaction_date', $m)->whereYear('transaction_date', $y)->sum('amount');
            $exp = (float)$group->transactions()->where('type', 'expense')->whereMonth('transaction_date', $m)->whereYear('transaction_date', $y)->sum('amount');

            $monthlyTrend[] = [
                'month' => $m,
                'year' => $y,
                'label' => $label,
                'income' => $inc,
                'expense' => $exp,
                'net' => $inc - $exp,
            ];
        }

        return response()->json([
            'period' => $period,
            'metrics' => [
                'income' => $periodIncome,
                'expense' => $periodExpense,
                'net_savings' => $netSavings,
                'savings_rate' => $savingsRate,
                'daily_avg_expense' => $dailyAvgExpense,
                'health_status' => $netSavings > 0 ? 'surplus' : ($netSavings == 0 ? 'balanced' : 'deficit'),
            ],
            'category_breakdown' => $categoryBreakdown,
            'member_contributions' => $memberContributions,
            'top_expenses' => $topExpenses,
            'monthly_trend' => $monthlyTrend,
        ]);
    }
}
