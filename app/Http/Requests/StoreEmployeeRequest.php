<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'designation'  => 'nullable|string|max:100',
            'type'         => 'required|in:salary,production',
            'salary'       => 'nullable|numeric|min:0',
            'join_date'    => 'nullable|date',
            'address'      => 'nullable|string|max:500',
            'status'       => 'nullable|in:active,inactive',
            'working_days' => 'nullable|integer|min:1|max:31',
            'notes'        => 'nullable|string|max:500',
        ];
    }
}
