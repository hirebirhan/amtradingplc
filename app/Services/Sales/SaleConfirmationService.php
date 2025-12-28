<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;

class SaleConfirmationService
{
    private SaleValidationService $validationService;
    private SaleFormService $saleFormService;

    public function __construct()
    {
        $this->validationService = new SaleValidationService();
        $this->saleFormService = new SaleFormService();
    }

    public function confirmSale(array $form, array $items, float $totalAmount, float $taxAmount, float $shippingAmount): array
    {
        $validationErrors = $this->validationService->validateSaleData($form, $items, $totalAmount);

        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'type' => 'validation',
                'errors' => $validationErrors
            ];
        }

        try {
            DB::beginTransaction();

            $sale = $this->saleFormService->createSale(
                $form,
                $items,
                $totalAmount,
                $taxAmount,
                $shippingAmount
            );

            DB::commit();

            return [
                'success' => true,
                'sale' => $sale,
                'redirect' => route('admin.sales.index')
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            return [
                'success' => false,
                'type' => 'exception',
                'message' => $e->getMessage()
            ];
        }
    }
}