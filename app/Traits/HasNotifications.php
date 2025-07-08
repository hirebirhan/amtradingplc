<?php

namespace App\Traits;

trait HasNotifications
{
    /**
     * Standardized notification method for consistent toast messages
     * 
     * @param string $message The notification message
     * @param string $type The notification type (success, error, warning, info)
     */
    protected function notify(string $message, string $type = 'info'): void
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message
        ]);
    }
    
    /**
     * Show success notification
     * 
     * @param string $message The success message
     */
    protected function notifySuccess(string $message): void
    {
        $this->notify($message, 'success');
    }
    
    /**
     * Show error notification
     * 
     * @param string $message The error message
     */
    protected function notifyError(string $message): void
    {
        $this->notify($message, 'error');
    }
    
    /**
     * Show warning notification
     * 
     * @param string $message The warning message
     */
    protected function notifyWarning(string $message): void
    {
        $this->notify($message, 'warning');
    }
    
    /**
     * Show info notification
     * 
     * @param string $message The info message
     */
    protected function notifyInfo(string $message): void
    {
        $this->notify($message, 'info');
    }
} 