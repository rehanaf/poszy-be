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
            background: #eee;
            color: #000;
            padding: 12px 0 24px;
        }

        /* ===== Penyangga kelas ukuran =====
           58mm  -> sempit: font kecil, jarak rapat
           80mm  -> lebar standar thermal
           a4    -> kertas A4 (210mm), font normal */
        :root {
            --rw: 80mm;
            --fs: 12px;
            --th: 16px;
            --lh: 4px;
        }
        body.sz-58  { --rw: 58mm;  --fs: 9.5px;  --th: 12px; --lh: 2px; }
        body.sz-80  { --rw: 80mm;  --fs: 12px;   --th: 16px; --lh: 4px; }
        body.sz-a4  { --rw: 210mm; --fs: 13px;   --th: 22px; --lh: 6px; }

        .picker {
            max-width: 220mm;
            margin: 0 auto 10px;
            padding: 8px 10px;
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 8px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            font-family: system-ui, sans-serif;
            font-size: 13px;
        }
        .picker label { display: flex; gap: 4px; align-items: center; cursor: pointer; }

        .receipt {
            width: var(--rw);
            margin: 0 auto;
            background: #fff;
            padding-top: 6px;
            font-size: var(--fs);
            line-height: 1.35;
            overflow: hidden;
        }
        .center { text-align: center; }
        .bold { font-weight: 700; }
        .muted { color: #555; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .line { border-top: 1px dashed #000; margin: var(--lh) 0; }
        table { width: 100%; border-collapse: collapse; font-size: inherit; }
        th, td { text-align: left; padding: calc(var(--lh) / 2) 2px; vertical-align: top; word-break: break-word; }
        td.q, th.q { text-align: center; white-space: nowrap; }
        td.p, th.p, td.s, th.s { text-align: right; white-space: nowrap; }
        .totals { width: 100%; margin: 4px 0; }
        .totals .r { display: flex; justify-content: space-between; padding: 1px 0; }
        .totals .grand { display: flex; justify-content: space-between; font-size: calc(var(--fs) + 2px); border-top: 1px solid #000; }

        h1 { font-size: calc(var(--fs) + 6px); }
        .subtitle { font-size: calc(var(--fs) - 2px); }

        /* Ukuran kertas untuk print, ikut kelas terpilih */
        body.sz-58 .receipt { page: p58; }
        @page p58 { size: 58mm auto; margin: 0; }

        body.sz-80 .receipt { page: p80; }
        @page p80 { size: 80mm auto; margin: 0; }

        body.sz-a4 .receipt { page: pa4; }
        @page pa4 { size: 210mm 297mm; margin: 8mm; }

        .no-print { display: block; }
        @media print {
            body { background: #fff; padding: 0; }
            .picker { display: none; }
            .no-print { display: none; }
            .receipt { box-shadow: none; margin: 0; }
        }
        @media screen {
            .receipt { box-shadow: 0 1px 6px rgba(0,0,0,0.25); padding-bottom: 8px; }
        }
    </style>
</head>
<body class="sz-80">
    <div class="picker no-print">
        <strong>Ukuran kertas:</strong>
        <label><input type="radio" name="size" value="58">58mm</label>
        <label><input type="radio" name="size" value="80" checked>80mm</label>
        <label><input type="radio" name="size" value="a4">A4</label>
        <button onclick="window.print()" style="margin-left:auto;padding:4px 12px;">Print / Save as PDF</button>
    </div>

    <div class="receipt">
        <h1 class="center bold">POSZY</h1>
        <p class="center subtitle muted">Point Of Sale</p>
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
            @if($order->points_redeemed > 0)
            <div class="r">
                <span>Tukar Poin</span>
                <span>- {{ $order->points_redeemed }} poin</span>
            </div>
            @endif
            @if($order->points_discount > 0)
            <div class="r">
                <span>Diskon Poin</span>
                <span>- {{ number_format($order->points_discount, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($order->points_earned > 0)
            <div class="r">
                <span>Poin Diterima</span>
                <span>+ {{ $order->points_earned }} poin</span>
            </div>
            @endif
            <div class="grand">
                <span class="bold">TOTAL</span>
                <span class="bold">{{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="line"></div>
        <p class="center">Terima kasih telah berbelanja di POSZY!</p>
        <p class="center muted">#{{ $order->id }}</p>
    </div>

    <script>
        (function () {
            var q = new URLSearchParams(window.location.search).get('size');
            var current = q || localStorage.getItem('receipt-size') || '80';
            if (!['58', '80', 'a4'].includes(current)) current = '80';
            document.body.className = 'sz-' + current;
            var inputs = document.querySelectorAll('.picker input[name="size"]');
            inputs.forEach(function (el) {
                el.checked = el.value === current;
                el.addEventListener('change', function () {
                    if (el.checked) {
                        document.body.className = 'sz-' + el.value;
                        localStorage.setItem('receipt-size', el.value);
                    }
                });
            });
        })();
    </script>
</body>
</html>