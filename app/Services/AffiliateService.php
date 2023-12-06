<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if the affiliate with the given email already exists
        $existingAffiliate = Affiliate::where('email', $email)->first();
        if ($existingAffiliate) {
            throw new AffiliateCreateException('Affiliate with this email already exists.');
        }

        // Create a new user for the affiliate
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_AFFILIATE, // Assuming AFFILIATE_TYPE is a constant in the User model
        ]);

        // Create the affiliate and associate it with the user and merchant
        $affiliate = new Affiliate([
            'commission_rate' => $commissionRate,
        ]);

        $affiliate->user()->associate($user);
        $affiliate->merchant()->associate($merchant);
        $affiliate->save();

        // Send an email to notify the affiliate
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }

    public function getAffiliateForUser(User $user): Affiliate
    {
        return $user->affiliate ?? $this->createAffiliateForUser($user);
    }

    private function createAffiliateForUser(User $user): Affiliate
    {
        // Create a new affiliate and associate it with the user
        $affiliate = new Affiliate();
        $affiliate->user()->associate($user);
        $affiliate->save();

        return $affiliate;
    }

    public function logCommission(Affiliate $affiliate, Order $order)
    {
        // Associate the order with the affiliate, and log the commission
        $order->affiliate()->associate($affiliate);
        $order->save();

        // Optionally, you can perform additional commission logging logic here
    }
}
