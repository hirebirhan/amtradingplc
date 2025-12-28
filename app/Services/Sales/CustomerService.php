<?php

namespace App\Services\Sales;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerService
{
    public function searchCustomers(string $search = ''): Collection
    {
        $query = Customer::query();
        
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        return $query->limit(50)->get();
    }

    public function getCustomerData(int $customerId): ?array
    {
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return null;
        }
        
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
        ];
    }

    public function validateWalkingCustomerPayment(string $paymentMethod): bool
    {
        return !in_array($paymentMethod, ['full_credit', 'credit_advance']);
    }
}