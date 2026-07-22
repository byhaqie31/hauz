<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => 'sometimes|string|max:255',
            'phone'            => 'sometimes|string|max:30',
            'personal'         => 'nullable|array',   // camelCase interior stored verbatim
            'emergencyContact' => 'nullable|array',
        ];
    }

    public function toModelAttributes(): array
    {
        $v = $this->validated();
        $map = ['personal' => 'personal_info', 'emergencyContact' => 'emergency_contact'];
        $out = [];
        foreach ($v as $key => $value) {
            $out[$map[$key] ?? $key] = $value;
        }
        return $out;
    }
}
