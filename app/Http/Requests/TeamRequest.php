<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        return [
            'name' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
            'founded_year' => $isUpdate ? 'sometimes|required|integer|min:1800|max:' . date('Y') : 'required|integer|min:1800|max:' . date('Y'),
            'address' => $isUpdate ? 'sometimes|required|string' : 'required|string',
            'city' => $isUpdate ? 'sometimes|required|string|max:100' : 'required|string|max:100',
        ];
    }
}
