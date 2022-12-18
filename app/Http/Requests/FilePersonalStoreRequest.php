<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilePersonalStoreRequest extends FormRequest
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
            'nombres' => ['required', 'max:255', 'string'],
            'apellidos' => ['required', 'max:255', 'string'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'CI' => ['nullable', 'max:255', 'string'],
            'direccion' => ['nullable', 'max:255', 'string'],
            'email_personal' => ['nullable', 'max:255', 'string'],
            'telefono_1' => ['nullable', 'max:255', 'string'],
            'telefono_2' => ['nullable', 'max:255', 'string'],
            'presentacion' => ['nullable', 'max:255', 'string'],
        ];
    }
}
