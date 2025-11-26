<?php

namespace App\Traits;

trait HasControllerFlashMessages
{
    /**
     * Flash a success message
     */
    protected function flashSuccess(string $message): void
    {
        session()->flash('success', $message);
    }

    // Removed error, warning, and info flash methods - only success messages allowed

    /**
     * Generate standard success messages for CRUD operations
     */
    protected function flashCrudSuccess(string $resource, string $action): void
    {
        $message = ucfirst($resource) . ' ' . $action . ' successfully.';
        $this->flashSuccess($message);
    }

    // Removed CRUD error flash method - only success messages allowed
}