<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        // Use a database transaction to ensure data consistency
        DB::transaction(function () use ($data) {
            // Find or create the merchant based on the domain
            $merchant = Merchant::firstOrCreate(['domain' => $data['merchant_domain']]);

            // Find or create the user based on the customer_email
            $user = User::firstOrCreate(['email' => $data['customer_email']], [
                'name' => $data['customer_name'],
            ]);

            // Find or create the affiliate based on the user
            $affiliate = $this->affiliateService->getAffiliateForUser($user);

            // Check for duplicate orders based on order_id
            if (Order::where('order_id', $data['order_id'])->exists()) {
                // Ignore duplicate order
                return;
            }

            // Create the order and associate it with the merchant, user, and affiliate
            $order = new Order([
                'order_id' => $data['order_id'],
                'subtotal_price' => $data['subtotal_price'],
                'discount_code' => $data['discount_code'],
            ]);

            $order->merchant()->associate($merchant);
            $order->user()->associate($user);
            $order->affiliate()->associate($affiliate);
            $order->save();

            // Log any commissions for the affiliate
            $this->affiliateService->logCommission($affiliate, $order);
        });
    }
}
