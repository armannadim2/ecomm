<?php

namespace App\Http\Requests\Store;

use App\Support\SpanishProvinces;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:150'],
            'tax_id' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]{8,10}$/i'],
            'line1' => ['required', 'string', 'max:200'],
            'line2' => ['nullable', 'string', 'max:200'],
            'postal_code' => [
                'required', 'regex:/^\d{5}$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (SpanishProvinces::fromPostalCode((string) $value) === null) {
                        $fail(__('Este código postal no existe.'));
                    } elseif (! SpanishProvinces::isShippable((string) $value)) {
                        $fail(__('De momento solo enviamos a la Península y Baleares.'));
                    }
                },
            ],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s().-]{9,}$/'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'first_name' => __('nombre'),
            'last_name' => __('apellidos'),
            'company' => __('empresa'),
            'tax_id' => __('NIF'),
            'line1' => __('dirección'),
            'line2' => __('piso, puerta…'),
            'postal_code' => __('código postal'),
            'city' => __('población'),
            'phone' => __('teléfono'),
        ];
    }

    /** @return array<string, mixed> Validated data with the province filled from the postal code. */
    public function addressData(): array
    {
        $data = $this->validated();
        $data['province'] = SpanishProvinces::fromPostalCode($data['postal_code']);
        $data['tax_id'] = isset($data['tax_id']) ? strtoupper($data['tax_id']) : null;
        $data['is_default'] = $this->boolean('is_default');

        return $data;
    }
}
