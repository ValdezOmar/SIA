<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemitenteExternoUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nombre_completo' => ['required', 'max:255', 'string'],
            'cargo_empresa' => ['required', 'max:255', 'string'],
            'telefono_1' => ['required', 'max:255', 'string'],
            'telefono_2' => ['required', 'max:255', 'string'],
        ];
    }
}
