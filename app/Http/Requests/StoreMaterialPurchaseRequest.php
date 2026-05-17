<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'material_name'    => 'required|string|max:255',
            'category'         => 'nullable|string|max:100',
            'vendor_name'      => 'nullable|string|max:255',
            'quantity'         => 'required|numeric|min:0.01|max:999999',
            'unit'             => 'required|string|max:50',
            'unit_price'       => 'required|numeric|min:0',
            'total_amount'     => 'nullable|numeric|min:0',
            'payment_method'   => 'required|string|max:50',
            'payment_status'   => 'required|in:paid,partial,due',
            'paid_amount'      => 'nullable|numeric|min:0',
            'purchase_date'    => 'required|date',
            'notes'            => 'nullable|string|max:500',
            'color'            => 'nullable|string|max:100',
        ];
    }
}
