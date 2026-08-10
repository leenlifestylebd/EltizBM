<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name'              => 'required|string|max:255',
            'sku'               => "nullable|string|max:100|unique:products,sku,{$productId}",
            'barcode'           => "nullable|string|max:100|unique:products,barcode,{$productId}",
            'category_id'       => ['nullable', Rule::exists('categories', 'id')->where('business_id', auth()->user()->business_id)],
            'brand_id'          => ['nullable', Rule::exists('brands', 'id')->where('business_id', auth()->user()->business_id)],
            'type'              => 'nullable|in:simple,variable,service',
            'unit'              => 'nullable|string|max:50',
            'price'             => 'required|numeric|min:0',
            'cost_price'        => 'nullable|numeric|min:0',
            'sale_price'        => 'nullable|numeric|min:0',
            'stock'             => 'nullable|numeric|min:0',
            'low_stock_alert'   => 'nullable|integer|min:0',
            'weight'            => 'nullable|numeric|min:0',
            'fabric_consumption'=> 'nullable|numeric|min:0',
            'description'       => 'nullable|string|max:2000',
            'is_active'         => 'nullable|boolean',
            'track_stock'       => 'nullable|boolean',
            'images.*'          => 'nullable|image|max:2048',
        ];
    }
}
