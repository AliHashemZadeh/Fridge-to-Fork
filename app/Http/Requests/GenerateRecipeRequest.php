<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateRecipeRequest extends FormRequest
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
            'ingredients' => ['required', 'string', 'min:3'],
            'country'     => ['required', 'string', 'min:2'],
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'ingredients.required' => 'Please provide at least one ingredient.',
            'country.required'     => 'Please specify a country cuisine.',
        ];
    }
}
