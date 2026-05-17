<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\StockReservations;

use Illuminate\Foundation\Http\FormRequest;

class ExtendReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfers.edit');
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'integer', 'min:1', 'max:168'],
        ];
    }

    public function messages(): array
    {
        return [
            'hours.max' => 'Reservations can be extended by at most 168 hours (7 days) at a time.',
        ];
    }
}
