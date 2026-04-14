<?php

namespace App\Console\Commands;

use App\Enums\RequestStatus;
use App\Models\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdatePrioritiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'requests:update-priorities';

    /**
     * The console command description.
     */
    protected $description = 'Update automatic priorities for all requests based on deadlines';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting priority update process...');

        $requests = Request::whereNotNull('deadline')
            ->notTrulyComplete()
            ->get();

        $updatedCount = 0;
        $highPriorityCount = 0;
        $urgentCount = 0;

        foreach ($requests as $request) {
            $originalPriority = $request->is_priority;
            $originalUrgent = $request->isUrgent();

            // Update priority based on deadline
            $request->updatePriorityFromDeadline();

            if ($request->is_priority !== $originalPriority) {
                $updatedCount++;
                $this->line("Updated request {$request->ref_number}: priority changed from " . 
                    ($originalPriority ? 'HIGH' : 'NORMAL') . ' to ' . 
                    ($request->is_priority ? 'HIGH' : 'NORMAL'));
            }

            if ($request->isUrgent()) {
                $urgentCount++;
            } elseif ($request->is_priority) {
                $highPriorityCount++;
            }
        }

        $this->info("Priority update completed!");
        $this->info("Total requests processed: {$requests->count()}");
        $this->info("Requests with priority changes: {$updatedCount}");
        $this->info("Current urgent requests (≤3 days): {$urgentCount}");
        $this->info("Current high priority requests (≤5 days): {$highPriorityCount}");

        Log::info('Priority update completed', [
            'total_processed' => $requests->count(),
            'priority_changes' => $updatedCount,
            'urgent_count' => $urgentCount,
            'high_priority_count' => $highPriorityCount,
        ]);

        return self::SUCCESS;
    }
}
