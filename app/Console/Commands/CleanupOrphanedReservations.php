<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockReservation;
use App\Models\Transfer;

class CleanupOrphanedReservations extends Command
{
    protected $signature = 'stock:cleanup-reservations {--dry-run : Show what would be cleaned without actually doing it}';
    protected $description = 'Clean up orphaned stock reservations from completed/cancelled transfers';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        $this->info('Scanning for orphaned stock reservations...');
        
        // Find reservations for completed/cancelled/rejected transfers
        $orphanedReservations = StockReservation::where('reference_type', 'transfer')
            ->whereHas('transfer', function($query) {
                $query->whereIn('status', ['completed', 'cancelled', 'rejected']);
            })
            ->with('transfer')
            ->get();
            
        // Find expired reservations
        $expiredReservations = StockReservation::where('expires_at', '<', now())
            ->get();
            
        $totalOrphaned = $orphanedReservations->count();
        $totalExpired = $expiredReservations->count();
        
        if ($totalOrphaned === 0 && $totalExpired === 0) {
            $this->info('✅ No orphaned or expired reservations found.');
            return 0;
        }
        
        $this->warn("Found {$totalOrphaned} orphaned reservations and {$totalExpired} expired reservations.");
        
        if ($isDryRun) {
            $this->info('DRY RUN - No changes will be made');
            
            if ($totalOrphaned > 0) {
                $this->info('Orphaned reservations that would be cleaned:');
                foreach ($orphanedReservations as $reservation) {
                    $this->line("- Transfer #{$reservation->reference_id} ({$reservation->transfer->status}): Item {$reservation->item_id}, Qty: {$reservation->quantity}");
                }
            }
            
            if ($totalExpired > 0) {
                $this->info('Expired reservations that would be cleaned:');
                foreach ($expiredReservations as $reservation) {
                    $this->line("- {$reservation->reference_type} #{$reservation->reference_id}: Item {$reservation->item_id}, Expired: {$reservation->expires_at}");
                }
            }
            
            return 0;
        }
        
        // Clean up orphaned reservations
        if ($totalOrphaned > 0) {
            $orphanedReservations->each(function($reservation) {
                $this->line("Cleaning orphaned reservation for transfer #{$reservation->reference_id} ({$reservation->transfer->status})");
                $reservation->delete();
            });
        }
        
        // Clean up expired reservations
        if ($totalExpired > 0) {
            $expiredReservations->each(function($reservation) {
                $this->line("Cleaning expired reservation for {$reservation->reference_type} #{$reservation->reference_id}");
                $reservation->delete();
            });
        }
        
        $this->info("✅ Cleaned up {$totalOrphaned} orphaned and {$totalExpired} expired reservations.");
        
        return 0;
    }
}