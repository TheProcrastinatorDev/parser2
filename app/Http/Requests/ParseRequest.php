<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Parser\ParserManager;
use Illuminate\Foundation\Http\FormRequest;

class ParseRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parserManager = app(ParserManager::class);
        $availableParsers = implode(',', $parserManager->names());

        return [
            'source' => ['required', 'string'],
            'type' => ['required', 'string', "in:{$availableParsers}"],
            'keywords' => ['sometimes', 'array'],
            'keywords.*' => ['string'],
            'options' => ['sometimes', 'array'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'filters' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'The selected parser type is not available. Use GET /api/parsers to see available parsers.',
        ];
    }
}
