<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;
    public function __construct(MerchantService $merchantService)
    {
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
            'to' => 'required|date',
        ]);

        // Parse the date parameters
        $fromDate = Carbon::parse($request->input('from'));
        $toDate = Carbon::parse($request->input('to'))->endOfDay();

        // Fetch orders within the specified date range
        $orders = Order::whereBetween('created_at', [$fromDate, $toDate])->get();

        // Calculate statistics
        $orderCount = $orders->count();
        $unpaidCommission = $this->merchantService->calculateUnpaidCommission($orders);
        $revenue = $orders->sum('subtotal');

        // Return JSON response
        return response()->json([
            'count' => $orderCount,
            'commission_owed' => $unpaidCommission,
            'revenue' => $revenue,
        ]);
    }
}
