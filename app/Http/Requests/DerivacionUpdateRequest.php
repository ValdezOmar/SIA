<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DerivacionUpdateRequest extends FormRequest
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
            'tramite_id' => ['required', 'exists:cor_tramites,id'],
            'remitente_id' => ['nullable', 'exists:users,id'],
            'destinatario_id' => ['nullable', 'max:255'],
            'proveido' => ['required', 'max:255', 'string'],
            'fecha_derivacion' => ['nullable', 'date'],
            'fecha_recepcion' => ['nullable', 'date'],
            'fecha_rechazo' => ['nullable', 'date'],
            'fecha_anulado' => ['nullable', 'date'],
            'fecha_archivo' => ['nullable', 'date'],
        ];
    }
}
