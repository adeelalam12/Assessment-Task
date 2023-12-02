<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;


class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        try {

            // Create a new user for the affiliate
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($email),
                'type' => User::AFFILIATE_TYPE,
            ]);

            // Create a new affiliate associated with the user and merchant
            $affiliate = Affiliate::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'commission_rate' => $commissionRate,
            ]);

            return $affiliate;
        } catch (\Exception $e) {
            // Handle any exception
            throw new AffiliateCreateException('Failed to create affiliate.', $e->getCode(), $e);
        }
    }
}
