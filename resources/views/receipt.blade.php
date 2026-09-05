<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', ui-monospace, monospace;
            background: #fff;
            width: 302px; /* 80mm thermal at ~96dpi */
            margin: 0 auto;
            color: #000;
            font-size: 12px;
        }
        @media print {
            body { width: 302px; }
            .no-print { display: none; }
        }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .muted { color: #555; }
        .row { display: flex; justify-content: space-between; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { text-align: left; padding: 3px 2px; }
        td.q, th.q { text-align: center; }
        td.p, th.p, td.s, th.s { text-align: right; }
        @page { size: 80mm auto; margin: 0; }
        .totals { width: 100%; margin: 6px 0; }
        .totals .r { display: flex; justify-content: space-between; padding: 2px 0; }
        .totals .grand { display: flex; justify-content: space-between; font-size: 14px; border-top: 1px solid #000; }
    </style>
</head>
<body>
    <h1 class="center bold">POSZY</h1>
    <p class="center muted">Point Of Sale</p>
    <div class="line"></div>

    <p>Receipt No : {{ $order->id }}</p>
    <p>Date      : {{ $order->order_date?->format('d M Y H:i') }}</p>
    <p>Cashier   : {{ $order->cashier_name }}</p>
    <p>Customer  : {{ $order->customer_name ?? ($order->customer?->name ?? '-') }}</p>
    <p>Payment   : {{ $order->paymentMethod?->name ?? '-' }}</p>
    <p class="muted">Status: {{ $order->payment_status }}</p>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th style="width:44%">Item</th>
                <th class="q" style="width:12%">Qty</th>
                <th class="p" style="width:22%">Price</th>
                <th class="s" style="width:22%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderItems as $item)
                <tr>
                    <td>{{ $item->product_name }}@if($item->discount > 0) ({{ $item->discount }}%)@endif</td>
                    <td class="q">{{ $item->quantity }}</td>
                    <td class="p">{{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="s">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <div class="totals">
        <div class="r">
            <span>Subtotal</span>
            <span>{{ number_format($order->orderItems->sum(fn ($i) => $i->subtotal), 0, ',', '.') }}</span>
        </div>
        @if($order->discount_amount > 0)
        <div class="r">
            <span>Discount</span>
            <span>- {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        @if($order->tax_amount > 0)
        <div class="r">
            <span>Tax</span>
            <span>{{ number_format($order->tax_amount, 0, ',', '.') }}</span>
        </div>
        @endif
        <div class="grand">
            <span class="bold">TOTAL</span>
            <span class="bold">{{ number_format($order->total_amount, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="line"></div>
    <p class="center muted">Thank you!</p>
    <p class="center no-print" style="margin-top:12px">
        <button onclick="window.print()">Print / Save as PDF</button>
    </p>
</body>
</html>