<?php

namespace App\Http\Requests;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:vakantie,bijzonder_verlof,onbetaald_verlof',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $overlapping = LeaveRequest::where('user_id', $this->user()->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('start_date', '<=', $this->end_date)
                            ->where('end_date', '>=', $this->start_date);
                    });
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('start_date', __('You already have a leave request for this period.'));
            }
        });
    }
}
