<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     * Hint: Use the password field to store the API key.
     * Hint: Be sure to set the correct user type according to the constants in the User model.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['api_key']),
            'type' => User::MERCHANT_TYPE,
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['display_name'],
            'turn_customers_into_affiliates' => $data['turn_customers_into_affiliates'],
            'default_commission_rate' => $data['default_commission_rate'],
        ]);

        return $merchant;
    }

    /**
     * Update the user
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data): void
    {
        try {
            // Update the user details
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['api_key']),
            ]);

            // Update the associated merchant details
            $merchant = $user->merchant;
            if ($merchant) {
                $merchant->update([
                    'domain' => $data['domain'],
                    'display_name' => $data['display_name'],
                    'turn_customers_into_affiliates' => $data['turn_customers_into_affiliates'],
                    'default_commission_rate' => $data['default_commission_rate'],
                ]);
            }
        } catch (\Exception $exception) {
            // Handle the exception
            throw $exception;
        }
    }
    /**
     * Find a merchant by their email.
     * Hint: You'll need to look up the user first.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        // Find the user by email
        $user = User::where('email', $email)->first();

        // If the user is found, return the associated merchant
        return $user ? $user->merchant : null;
    }

    /**
     * Pay out all of an affiliate's orders.
     * Hint: You'll need to dispatch the job for each unpaid order.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        // Get unpaid orders for the affiliate
        $unpaidOrders = $affiliate->orders()->where('payout_status', Order::STATUS_UNPAID)->get();

        // Dispatch a payout job for each unpaid order
        foreach ($unpaidOrders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }
}
