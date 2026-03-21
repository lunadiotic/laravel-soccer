<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MatchResultRequest extends FormRequest
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
        return [
            'home_score'          => 'required|integer|min:0',
            'away_score'          => 'required|integer|min:0',
            'goals'               => 'sometimes|array',
            'goals.*.player_id'   => 'required_with:goals|exists:players,id',
            'goals.*.minute'      => 'required_with:goals|integer|min:1|max:120',
        ];
    }
}
