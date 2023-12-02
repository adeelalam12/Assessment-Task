<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        try {
            // Calculate the payout amount based on your business logic
            $payoutAmount = $this->calculatePayoutAmount();

            // Use the ApiService to send the payout
            $apiService->sendPayout($this->order->affiliate->user, $payoutAmount);

            // If the payout is successful, update the order status to 'paid'
            $this->order->update([
                'payout_status' => Order::STATUS_PAID,
            ]);

            Log::info("Payout processed successfully for order #{$this->order->id}");
        } catch (\Exception $e) {
            // If an exception occurs, log the error and leave the order status as 'unpaid'
            Log::error("Error processing payout for order #{$this->order->id}: " . $e->getMessage());
        }
    }

    protected function calculatePayoutAmount()
    {
        // For example, you might use the order subtotal and commission_owed
        return $this->order->subtotal - $this->order->commission_owed;
    }
}
