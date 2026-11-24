<?php

namespace App\Http\Requests\Store;

use App\Domain\Orders\Enums\Fulfillment;
use App\Domain\Orders\Enums\PaymentMethod;
use App\Support\SpanishProvinces;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $delivery = Rule::requiredIf(fn () => $this->input('fulfillment') === Fulfillment::Delivery->value);

        return [
            'fulfillment' => ['required', Rule::enum(Fulfillment::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 ()-]{9,20}$/'],
            'tax_id' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]{8,20}$/'],
            'ship_line1' => [$delivery, 'nullable', 'string', 'max:150'],
            'ship_line2' => ['nullable', 'string', 'max:150'],
            'ship_postal_code' => [$delivery, 'nullable', 'regex:/^\d{5}$/', function (string $attribute, mixed $value, Closure $fail) {
                if ($value && ! SpanishProvinces::isShippable((string) $value)) {
                    $fail(__('De momento solo enviamos a la Península y Baleares.'));
                }
            }],
            'ship_city' => [$delivery, 'nullable', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:500'],
            'marketing' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, mixed> */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key === null && ! empty($data['ship_postal_code'])) {
            $data['ship_province'] = SpanishProvinces::fromPostalCode($data['ship_postal_code']);
        }

        return $data;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'first_name' => __('nombre'),
            'last_name' => __('apellidos'),
            'email' => __('correo electrónico'),
            'phone' => __('teléfono'),
            'tax_id' => __('NIF / DNI'),
            'ship_line1' => __('dirección'),
            'ship_postal_code' => __('código postal'),
            'ship_city' => __('población'),
        ];
    }
}
