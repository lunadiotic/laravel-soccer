<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MatchRequest extends FormRequest
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
            'match_date'   => $isUpdate ? 'sometimes|required|date' : 'required|date',
            'match_time'   => $isUpdate ? 'sometimes|required|date_format:H:i' : 'required|date_format:H:i',
            'home_team_id' => $isUpdate ? 'sometimes|required|exists:teams,id' : 'required|exists:teams,id',
            'away_team_id' => $isUpdate ? 'sometimes|required|exists:teams,id|different:home_team_id' : 'required|exists:teams,id|different:home_team_id',
        ];
    }

    public function messages(): array
    {
        return [
            'away_team_id.different' => 'The away team must be different from the home team.',
        ];
    }
}
