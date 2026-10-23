<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BotAccountingController extends Controller
{
    public function categories(Request $request)
    {
        return response()->json(['data' => AccountCategory::where('business_id', $request->attributes->get('business_id'))
            ->orderBy('name')->get(['id', 'name', 'type'])]);
    }

    public function store(Request $request)
    {
        $businessId = $request->attributes->get('business_id');
        $data = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category_id' => ['required', 'integer', Rule::exists('account_categories', 'id')->where('business_id', $businessId)->where('type', is_string($request->input('type')) ? $request->input('type') : '')],
            'title' => 'required|string|max:255',
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'regex:/^\d+(\.\d{1,2})?$/D'],
            'transaction_date' => 'required|date_format:Y-m-d',
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bkash', 'nagad', 'rocket', 'bank', 'other'])],
            'notes' => 'nullable|string|max:1000',
        ]);
        // Simple settled income/expenses only; payroll, bills and stock purchases use their own modules.
        $transaction = Transaction::create($data + [
            'business_id' => $businessId, 'created_by' => $request->attributes->get('bot_actor_id'),
        ]);

        return response()->json(['id' => $transaction->id, 'type' => $transaction->type, 'amount' => $transaction->amount,
            'category_id' => $transaction->category_id, 'transaction_date' => $transaction->transaction_date->toDateString()], 201);
    }

    public function configuration(Request $request)
    {
        $record = $request->attributes->get('bot_automation');

        return response()->json(['business_id' => $record->business_id, 'settings' => $record->settings ?? [],
            'credentials' => $record->secrets ?? []])->header('Cache-Control', 'no-store, private');
    }
}
