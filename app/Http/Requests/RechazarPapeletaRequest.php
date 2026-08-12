<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RechazarPapeletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('decidir', $this->route('papeleta'));
    }

    public function rules(): array
    {
        return [
            'comentario' => ['required', 'string', 'max:1000'],
        ];
    }
}
