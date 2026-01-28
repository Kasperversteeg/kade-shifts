<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
                Rule::unique('time_entries')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('time_entry')),
            ],
            'shift_start' => 'required|date_format:H:i',
            'shift_end' => 'required|date_format:H:i',
            'break_minutes' => 'required|integer|min:0|max:480',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'You already have an entry for this date.',
        ];
    }
}
