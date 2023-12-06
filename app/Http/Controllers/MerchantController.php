<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    private $merchantService;

    public function __construct(
        MerchantService $merchantService
    ) {
        $this->merchantService = $merchantService;
    }

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        // Validate the request parameters
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        
        // Get the 'from' and 'to' dates from the request
        $fromDate = Carbon::parse($request->input('from'));
        $toDate = Carbon::parse($request->input('to'));
        
        // Fetch relevant orders from the database
        $orders = Merchant::find($this->merchantService->getMerchantId())
        ->orders()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        // Calculate statistics
        $orderCount = $orders->count();
        $commissionOwed = $orders->whereHas('affiliate')->sum('unpaid_commission');
        $revenue = $orders->sum('subtotal');

        // Return the statistics in a JSON response
        return response()->json([
            'count' => $orderCount,
            'commission_owed' => $commissionOwed,
            'revenue' => $revenue,
        ]);
    }
}
