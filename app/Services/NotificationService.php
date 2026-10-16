<?php

namespace App\Services;

use App\Models\DailyCashRegister;
use App\Models\Expense;
use App\Models\ProductVariant;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Send in-app notification to a user.
     */
    public function notifyUser(User $user, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'App\Notifications\GenericSystemNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl,
                'time' => now()->toIso8601String(),
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Broadcast notification to all users holding a specific role.
     */
    public function notifyRole(string $role, string $title, string $message, string $type = 'info', ?string $actionUrl = null): int
    {
        $users = User::where('role', $role)->where('is_active', true)->get();
        foreach ($users as $u) {
            $this->notifyUser($u, $title, $message, $type, $actionUrl);
        }
        return $users->count();
    }

    /**
     * Scan and trigger low-stock alerts.
     */
    public function checkLowStockAlerts(int $threshold = 5): int
    {
        $lowBalances = StockBalance::where('quantity_on_hand', '<=', $threshold)
            ->where('quantity_on_hand', '>', 0)
            ->with(['variant.product', 'store'])
            ->get();

        $alertsSent = 0;
        foreach ($lowBalances as $b) {
            $msg = "Variant {$b->variant->product->name} ({$b->variant->color}-{$b->variant->size}) has only {$b->quantity_on_hand} pcs remaining at {$b->store->name}.";
            $this->notifyRole('store_manager', "Low Stock Alert: {$b->variant->product->name}", $msg, 'warning', route('reports.inventory'));
            $this->notifyRole('super_admin', "Low Stock Alert: {$b->store->name}", $msg, 'warning', route('reports.inventory'));
            $alertsSent++;
        }

        return $alertsSent;
    }

    /**
     * Scan and trigger transfer delay alerts (> 48 hours in transit).
     */
    public function checkTransferDelays(): int
    {
        $delayedTransfers = StockTransfer::where('status', 'in_transit')
            ->where('dispatched_at', '<=', now()->subHours(48))
            ->with(['sourceStore', 'destinationStore'])
            ->get();

        foreach ($delayedTransfers as $t) {
            $msg = "Transfer {$t->reference_number} from {$t->sourceStore->name} to {$t->destinationStore->name} has been in transit for over 48 hours.";
            $this->notifyRole('warehouse_staff', "Delayed Transfer Alert", $msg, 'danger', route('transfers.show', $t));
            $this->notifyRole('super_admin', "Delayed Transfer Alert", $msg, 'danger', route('transfers.show', $t));
        }

        return $delayedTransfers->count();
    }

    /**
     * Scan and alert unclosed daily cash registers by night time.
     */
    public function checkUnclosedCashRegisters(): int
    {
        $today = now()->format('Y-m-d');
        $openRegisters = DailyCashRegister::where('business_date', $today)
            ->where('status', 'open')
            ->with('store')
            ->get();

        foreach ($openRegisters as $r) {
            $msg = "Daily cash register for {$r->store->name} is still open. Please perform mandatory daily closing.";
            $this->notifyRole('store_manager', "Daily Cash Closing Pending", $msg, 'warning', route('cash-register.index'));
            $this->notifyRole('accountant', "Daily Cash Closing Pending", $msg, 'warning', route('cash-register.index'));
        }

        return $openRegisters->count();
    }
}
