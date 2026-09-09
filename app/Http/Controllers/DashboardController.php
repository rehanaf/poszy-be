<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Return dashboard summary: total cards, revenue series, top products,
     * sales by payment method, and low stock items.
     *
     * Query params:
     * - period: day|week|month|year (default: current month)
     * - date:      Y-m-d reference date (default: today)
     * - low_stock_threshold: int (default: 5)
     */
    public function summary(Request $request)
    {
        $period = in_array($request->get('period'), ['day', 'week', 'month', 'year']) ? $request->get('period') : 'month';
        $date = Carbon::parse($request->get('date', now()->toDateString()));
        $threshold = (int) $request->get('low_stock_threshold', 5);

        [$start, $end] = $this->rangeForPeriod($period, $date);
        $start = $start->startOfDay();
        $end = $end->endOfDay();

        $ordersQuery = Order::where('status', 'completed')
            ->whereBetween('order_date', [$start, $end]);

        $totalRevenue = (float) $ordersQuery->sum('total_amount');
        $totalOrders = $ordersQuery->count();
        $itemsSold = (int) OrderItem::whereIn('order_id', $ordersQuery->pluck('id'))
            ->sum('quantity');
        $totalCustomers = Customer::where('created_at', '<=', $end)->count();

        $revenueSeries = $this->buildRevenueSeries($period, $ordersQuery, $start, $end);

        $topProducts = OrderItem::whereIn('order_id', $ordersQuery->pluck('id'))
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get()
            ->map(fn ($item) => [
                'name' => $item->product_name,
                'quantity' => (int) $item->total_qty,
                'revenue' => (float) $item->total_revenue,
            ]);

        $salesByPayment = Order::where('status', 'completed')
            ->whereBetween('order_date', [$start, $end])
            ->leftJoin('payment_methods', 'orders.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('COALESCE(payment_methods.name, "Lainnya") as name, SUM(orders.total_amount) as total')
            ->groupBy('payment_methods.name')
            ->orderByDesc('total')
            ->get();

        $lowStockProducts = Product::where('is_active', true)
            ->whereNotNull('stock')
            ->where('stock', '<=', $threshold)
            ->orderBy('stock')
            ->take(10)
            ->get();
        $lowStockVariants = ProductVariant::whereNotNull('stock')
            ->where('stock', '<=', $threshold)
            ->with('product:id,name')
            ->orderBy('stock')
            ->take(10)
            ->get();

        $lowStockItems = collect()
            ->merge($lowStockProducts->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => (int) $p->stock,
                'type' => 'product',
                'variant_name' => null,
            ]))
            ->merge($lowStockVariants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->product?->name ?? 'Produk',
                'sku' => $v->sku,
                'stock' => (int) $v->stock,
                'type' => 'variant',
                'variant_name' => $v->name,
            ]))
            ->take(10)
            ->values();

        return response()->json([
            'period' => $period,
            'date' => $date->toDateString(),
            'range' => ['start' => $start->toDateTimeString(), 'end' => $end->toDateTimeString()],
            'totals' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_orders' => $totalOrders,
                'items_sold' => $itemsSold,
                'total_customers' => $totalCustomers,
            ],
            'revenue_series' => $revenueSeries,
            'top_products' => $topProducts,
            'sales_by_payment' => $salesByPayment,
            'low_stock_items' => $lowStockItems,
            'low_stock_threshold' => $threshold,
        ], 200);
    }

    private function rangeForPeriod(string $period, Carbon $date): array
    {
        return match ($period) {
            'day' => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()],
            'year' => [$date->copy()->startOfYear(), $date->copy()->endOfYear()],
            default => [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()],
        };
    }

    private function buildRevenueSeries(string $period, $ordersQuery, Carbon $start, Carbon $end): array
    {
        $orders = $ordersQuery->get(['order_date', 'total_amount']);

        if ($period === 'day') {
            $buckets = [];
            for ($h = 0; $h < 24; $h++) {
                $label = sprintf('%02d:00', $h);
                $buckets[$h] = ['label' => $label, 'revenue' => 0.0];
            }
            foreach ($orders as $order) {
                $h = Carbon::parse($order->order_date)->hour;
                $buckets[$h]['revenue'] += (float) $order->total_amount;
            }
            return array_values($buckets);
        }

        if ($period === 'year') {
            $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $buckets = [];
            foreach ($monthNames as $mIndex => $mName) {
                $buckets[$mIndex] = ['label' => $mName, 'revenue' => 0.0];
            }
            foreach ($orders as $order) {
                $m = Carbon::parse($order->order_date)->month - 1;
                $buckets[$m]['revenue'] += (float) $order->total_amount;
            }
            return array_values($buckets);
        }

        // week / month: bucket per hari
        $days = [];
        $cursor = $start->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $dayKey = $cursor->toDateString();
            $days[$dayKey] = ['label' => $cursor->format('d/m'), 'revenue' => 0.0];
            $cursor->addDay();
        }
        foreach ($orders as $order) {
            $dayKey = Carbon::parse($order->order_date)->toDateString();
            if (isset($days[$dayKey])) {
                $days[$dayKey]['revenue'] += (float) $order->total_amount;
            }
        }
        return array_values($days);
    }
}