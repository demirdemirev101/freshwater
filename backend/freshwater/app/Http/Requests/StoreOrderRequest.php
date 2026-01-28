<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        return [
            'customer_name'    => 'required|string|max:255',
            'customer_email'   => 'required|email',
            'shipping_address' => 'required|string',
            'shipping_city'    => 'required|string',
            'holiday_delivery_day' => 'nullable|date_format:Y-m-d',

            'subtotal'         => 'required|numeric|min:0',
            'shipping_price'   => 'nullable|numeric|min:0',
            'total'            => 'required|numeric|min:0',

            'payment_method'   => 'required|in:cod,card,bank_transfer',

            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|integer|min:1',
        ];
    }

}
