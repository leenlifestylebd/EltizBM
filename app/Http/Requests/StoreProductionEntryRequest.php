<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $bizId = auth()->user()->business_id;

        return [
            // Scope to the current business so an entry can never be filed against
            // another tenant's employee or pay rate.
            'employee_id'        => ['required', Rule::exists('employees', 'id')->where('business_id', $bizId)],
            'production_rate_id' => ['required', Rule::exists('production_rates', 'id')->where('business_id', $bizId)],
            'entry_date'         => 'required|date|before_or_equal:today',
            'quantity'           => 'required|integer|min:1|max:10000',
            'color'              => 'nullable|string|max:100',
            'size'               => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'entry_date.before_or_equal' => 'Entry date cannot be in the future.',
            'quantity.max'               => 'Quantity cannot exceed 10,000 per entry.',
        ];
    }
}
