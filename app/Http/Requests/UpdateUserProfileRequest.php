<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'hourly_rate' => 'nullable|numeric|min:0|max:999.99',
            'contract_type' => 'nullable|in:vast,flex,oproep',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'birth_date' => 'nullable|date|before:today',
            'start_date' => 'nullable|date',
            'bsn' => 'nullable|string|size:9',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
        ];
    }
}
