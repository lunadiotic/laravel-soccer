<?php

namespace App\Http\Requests;

use App\Models\Player;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlayerRequest extends FormRequest
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
        $playerModel = $this->route('player');
        $playerId = $playerModel instanceof Player ? $playerModel->id : null;

        // Update data pemain dari team tertentu
        $teamId = $this->team_id ?? ($playerModel instanceof Player ? $playerModel->team_id : null);

        // nomor punggung dan team milik pemain harus unik
        $jerseyRule = 'unique:players,jersey_number,' . ($playerId ?? NULL) . ',id' . ($teamId ? ',' . $teamId : '');

        return [
            'team_id' => $isUpdate ? 'sometimes|required|exists:teams,id' : 'required|exists:teams,id',
            'name' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'height' => $isUpdate ? 'sometimes|required|numeric|min:100|max:250' : 'required|numeric|min:100|max:250',
            'weight' => $isUpdate ? 'sometimes|required|numeric|min:30|max:200' : 'required|numeric|min:30|max:200',
            'position' => $isUpdate ? 'sometimes|required|string|in:penyerang,gelandang,bertahan,penjaga_gawang' : 'required|string|in:penyerang,gelandang,bertahan,penjaga_gawang',
            'jersey_number' => $isUpdate ? ['sometimes|required|integer|min:1|max:99', $jerseyRule] : ['required|integer|min:1|max:99', $jerseyRule]
        ];
    }
}
