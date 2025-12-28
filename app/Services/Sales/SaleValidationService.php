<?php

namespace App\Services\Sales;

class SaleValidationService
{
    public function validateSaleData(array $form, array $items, float $totalAmount): array
    {
        $errors = [];
        
        if (empty($items)) {
            $errors['items'] = 'Cannot create sale: No items added';
        }

        if (!$form['is_walking_customer'] && empty($form['customer_id'])) {
            $errors['form.customer_id'] = 'Cannot create sale: Please select a customer or check walking customer';
        }

        if (empty($form['warehouse_id']) && empty($form['branch_id'])) {
            $errors['form.warehouse_id'] = 'Cannot create sale: Please select either a warehouse or branch';
        }

        if ($form['payment_method'] === 'credit_advance') {
            if (empty($form['advance_amount']) || $form['advance_amount'] <= 0) {
                $errors['form.advance_amount'] = 'Advance amount is required and must be greater than zero';
            } elseif ($form['advance_amount'] > $totalAmount) {
                $errors['form.advance_amount'] = 'Advance amount cannot exceed the total amount';
            }
        }

        return $errors;
    }
}