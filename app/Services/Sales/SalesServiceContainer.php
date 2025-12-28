<?php

namespace App\Services\Sales;

class SalesServiceContainer
{
    private array $services = [];

    public function __construct()
    {
        $this->initializeServices();
    }

    private function initializeServices(): void
    {
        $this->services = [
            'saleForm' => new SaleFormService(),
            'itemSelection' => new ItemSelectionService(),
            'payment' => new PaymentValidationService(),
            'location' => new LocationService(),
            'customer' => new CustomerService(),
            'cart' => new CartService(),
            'stockValidation' => new StockValidationService(),
            'saleValidation' => new SaleValidationService(),
            'saleConfirmation' => new SaleConfirmationService(),
        ];
    }

    public function get(string $serviceName)
    {
        return $this->services[$serviceName] ?? null;
    }

    public function saleForm(): SaleFormService
    {
        return $this->services['saleForm'];
    }

    public function itemSelection(): ItemSelectionService
    {
        return $this->services['itemSelection'];
    }

    public function payment(): PaymentValidationService
    {
        return $this->services['payment'];
    }

    public function location(): LocationService
    {
        return $this->services['location'];
    }

    public function customer(): CustomerService
    {
        return $this->services['customer'];
    }

    public function cart(): CartService
    {
        return $this->services['cart'];
    }

    public function stockValidation(): StockValidationService
    {
        return $this->services['stockValidation'];
    }

    public function saleValidation(): SaleValidationService
    {
        return $this->services['saleValidation'];
    }

    public function saleConfirmation(): SaleConfirmationService
    {
        return $this->services['saleConfirmation'];
    }
}