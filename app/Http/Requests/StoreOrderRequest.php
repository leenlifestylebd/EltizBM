<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_name'        => 'required|string|max:100',
            'customer_id'          => 'nullable|exists:customers,id',
            'customer_phone'       => 'nullable|string|max:20',
            'source'               => 'nullable|string|max:50',
            'order_date'           => 'nullable|date|before_or_equal:today',
            'status'               => 'required|string|max:30',
            'payment_method'       => 'required|string|max:50',
            'payment_status'       => 'nullable|string|max:30',
            'paid_amount'          => 'nullable|numeric|min:0',
            'total'                => 'required|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'shipping_charge'      => 'nullable|numeric|min:0',
            'shipping_cost'        => 'nullable|numeric|min:0',
            'shipping_address'     => 'nullable|string|max:500',
            'notes'                => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|integer',
            'items.*.product_name' => 'required|string',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.total'        => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'          => 'At least one product is required.',
            'items.min'               => 'At least one product is required.',
            'items.*.quantity.min'    => 'Quantity must be at least 1.',
        ];
    }
}
