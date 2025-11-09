<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Parser\ParserManager;
use Illuminate\Foundation\Http\FormRequest;

class BatchParseRequest extends FormRequest
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
            'requests' => ['required', 'array', 'min:1', 'max:100'],
            'requests.*.source' => ['required', 'string'],
            'requests.*.type' => ['required', 'string', "in:{$availableParsers}"],
            'requests.*.keywords' => ['sometimes', 'array'],
            'requests.*.keywords.*' => ['string'],
            'requests.*.options' => ['sometimes', 'array'],
            'requests.*.limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'requests.*.offset' => ['sometimes', 'integer', 'min:0'],
            'requests.*.filters' => ['sometimes', 'array'],
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
            'requests.max' => 'Maximum 100 requests allowed per batch.',
            'requests.*.type.in' => 'Invalid parser type. Use GET /api/parsers to see available parsers.',
        ];
    }
}
