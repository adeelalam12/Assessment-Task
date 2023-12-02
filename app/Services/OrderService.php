<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
       // Check for existing order with the same order_id
       $existingOrder = Order::where('order_id', $data['order_id'])->first();

       if ($existingOrder) {
           // If the order already exists, ignore it
           return;
       }

       // Check if there is an existing affiliate with the customer_email
       $affiliate = $this->affiliateService->findAffiliateByEmail($data['customer_email']);

       if (!$affiliate) {
           // If the affiliate doesn't exist, create a new one
           $merchant = Merchant::where('domain', $data['merchant_domain'])->first();

           if ($merchant) {
               // Create a new user for the affiliate
               $user = User::create([
                   'name' => $data['customer_name'],
                   'email' => $data['customer_email'],
                   'password' => bcrypt($data['customer_email']),
                   'type' => User::AFFILIATE_TYPE,
               ]);

               // Create a new affiliate associated with the user and merchant
               $affiliate = Affiliate::create([
                   'user_id' => $user->id,
                   'merchant_id' => $merchant->id,
                   'commission_rate' => $merchant->default_commission_rate,
                   'discount_code' => $data['discount_code'],
               ]);
           }
       }

       // Create the order
       $order = Order::create([
           'order_id' => $data['order_id'],
           'subtotal' => $data['subtotal_price'],
           'merchant_id' => isset($merchant) ? $merchant->id : null,
           'affiliate_id' => isset($affiliate) ? $affiliate->id : null,
           'customer_email' => $data['customer_email'],
       ]);

       // Log any commissions or additional processing based on your business logic
       $this->logCommissions($order, $affiliate);
    }
    protected function logCommissions(Order $order, ?Affiliate $affiliate)
    {
        // Business logic
    }
}
