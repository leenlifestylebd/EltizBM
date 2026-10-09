<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'customer_name'         => 'required|string|max:100',
            'customer_id'          => [
                'nullable',
                Rule::exists('customers', 'id')->where('business_id', auth()->user()->business_id),
            ],
            'customer_phone'       => 'nullable|string|max:20',
            'source'               => 'nullable|in:manual,woocommerce,pos,web',
            'status'               => 'required|in:pending,confirmed,processing,shipped,delivered,completed,cancelled,returned',
            'payment_method'       => 'required|in:cash,card,bkash,nagad,rocket,bank,cod,other',
            'payment_status'       => 'nullable|string|max:30',
            'paid_amount'          => 'nullable|numeric|min:0',
            'total'                => 'nullable|numeric|min:0',
            'discount'             => 'nullable|numeric|min:0',
            'shipping_cost'        => 'nullable|numeric|min:0',
            'shipping_address'     => 'nullable|string|max:500',
            'notes'                => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('business_id', auth()->user()->business_id),
            ],
            'items.*.product_name' => 'nullable|string|max:255',
            'items.*.price'        => 'nullable|numeric|min:0',
            'items.*.cost_price'   => 'nullable|numeric|min:0',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'items.*.total'        => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'           => 'At least one product is required.',
            'items.min'               => 'At least one product is required.',
            'items.*.quantity.min'    => 'Quantity must be at least 1.',
        ];
    }
}
