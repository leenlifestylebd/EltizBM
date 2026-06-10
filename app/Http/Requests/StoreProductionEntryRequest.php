<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'employee_id'        => 'required|exists:employees,id',
            'production_rate_id' => 'required|exists:production_rates,id',
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
