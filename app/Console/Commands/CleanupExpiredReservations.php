<?php

namespace App\Console\Commands;

use App\Services\StockMovementService;
use Illuminate\Console\Command;

class CleanupExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:cleanup-reservations 
                            {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired stock reservations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stockMovementService = new StockMovementService();
        
        if ($this->option('dry-run')) {
            // Show what would be deleted
            $expiredCount = \App\Models\StockReservation::expired()->count();
            $this->info("Dry run: {$expiredCount} expired reservations would be deleted.");
            
            if ($expiredCount > 0) {
                $expiredReservations = \App\Models\StockReservation::expired()
                    ->with(['item', 'creator'])
                    ->get();
                
                $this->table(
                    ['ID', 'Item', 'Quantity', 'Type', 'Expired At', 'Created By'],
                    $expiredReservations->map(function ($reservation) {
                        return [
                            $reservation->id,
                            $reservation->item->name,
                            $reservation->quantity,
                            "{$reservation->reference_type}:{$reservation->reference_id}",
                            $reservation->expires_at->format('Y-m-d H:i:s'),
                            $reservation->creator->name ?? 'Unknown',
                        ];
                    })
                );
            }
        } else {
            // Actually delete expired reservations
            $deletedCount = $stockMovementService->cleanupExpiredReservations();
            
            if ($deletedCount > 0) {
                $this->info("Successfully cleaned up {$deletedCount} expired stock reservations.");
            } else {
                $this->info("No expired stock reservations found.");
            }
        }

        return 0;
    }
} 