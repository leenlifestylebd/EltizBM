<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type'             => 'required|in:income,expense',
            'title'            => 'required|string|max:255',
            'amount'           => 'required|numeric|min:0.01',
            'category_id'      => ['nullable', Rule::exists('account_categories', 'id')->where('business_id', auth()->user()->business_id)],
            'payment_method'   => 'required|string|max:50',
            'transaction_date' => 'required|date',
            'reference'        => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:500',
        ];
    }
}
