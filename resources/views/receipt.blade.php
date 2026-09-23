<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $order->receipt_number ?? $order->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
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
        body.sz-a4  { --rw: 194mm; --fs: 13px;   --th: 22px; --lh: 6px; }

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
.line { border-top: 1px dashed #999; margin: var(--lh) 0; }
.duo { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; }
.duo-l { display: flex; flex-direction: column; }
.duo-r { font-weight: 700; }
.item { margin: calc(var(--lh) * 2) 0; }
.iname { font-weight: 700; word-break: break-word; }
.irow { display: flex; justify-content: space-between; gap: 6px; color: #555; margin-top: calc(var(--lh) / 2); }
.totals { width: 100%; margin: 4px 0; }
.totals .r { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; padding: 1px 0; white-space: nowrap; }
.totals .r span:last-child { margin-left: auto; text-align: right; }
.powered-by { display: flex; align-items: center; justify-content: center; gap: 6px; color: #666; font-size: calc(var(--fs) - 2px); padding: 2px 0 4px; }
.powered-logo { max-height: 10mm; max-width: 40%; object-fit: contain; }

        h1 { font-size: calc(var(--fs) + 6px); }
        .subtitle { font-size: calc(var(--fs) - 2px); }
        .store-logo { max-width: calc(var(--rw) - 12mm); max-height: 22mm; object-fit: contain; margin: 0 auto; display: block; }
        body.sz-58 .store-logo { max-height: 14mm; }

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
@php
    $storeName = $store->name ?? 'POSZY';
    $storeTagline = $store->tagline ?? 'Point Of Sale';
    $defaultSize = isset($store->default_receipt_size) ? $store->default_receipt_size : '80';
    $poweredByEnabled = \App\Models\PlatformSetting::get('powered_by_enabled', true);
    $poweredByText = \App\Models\PlatformSetting::get('powered_by_text', 'Powered by SemestaPOS');
    $poweredByLogo = \App\Models\PlatformSetting::get('powered_by_logo');
@endphp
<body class="sz-{{ $defaultSize }}">
    <div class="picker no-print">
        <strong>Ukuran kertas:</strong>
        <label><input type="radio" name="size" value="58">58mm</label>
        <label><input type="radio" name="size" value="80" @if($defaultSize==='80')checked @endif>80mm</label>
        <label><input type="radio" name="size" value="a4" @if($defaultSize==='a4')checked @endif>A4</label>
        <button onclick="window.print()" style="margin-left:auto;padding:4px 12px;">Print / Save as PDF</button>
    </div>

    <div class="receipt">
        @if($store->logo_url ?? null)
            <img src="{{ $store->logo_url }}" alt="" class="store-logo">
        @endif
        <h1 class="center bold">{{ $storeName }}</h1>
        @if($storeTagline)
            <p class="center subtitle muted">{{ $storeTagline }}</p>
        @endif
        @if($store->address ?? null)
            <p class="center muted">{{ $store->address }}</p>
        @endif
        @if($store->phone ?? null)
            <p class="center muted">Telp: {{ $store->phone }}</p>
        @endif
        <p class="center bold">{{ $order->receipt_number ?? $order->id }}</p>
        <div class="line"></div>

        <div class="duo">
            <div class="duo-l">
                <span>{{ $order->order_date?->format('d M Y') }}</span>
                <span>{{ $order->order_date?->format('H:i') }}</span>
            </div>
            <div class="duo-r">{{ $order->cashier_name }}</div>
        </div>

        @if($order->customer_name)
        <div class="duo">
            <div class="duo-l">
                <span>{{ $order->customer_name }}</span>
                @if($order->customer?->phone)
                    <span class="muted">{{ $order->customer->phone }}</span>
                @endif
            </div>
            <div class="duo-r"></div>
        </div>
        @endif

        <div class="line"></div>

        @foreach($order->orderItems as $item)
            <div class="item">
                <div class="iname">{{ $item->product_name }}@if($item->discount > 0) ({{ $item->discount }}%)@endif</div>
                <div class="irow">
                    <span>{{ $item->quantity }} x {{ number_format($item->price, 0, ',', '.') }}</span>
                    <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
        @endforeach

        <div class="line"></div>

        <div class="totals">
            @if($order->discount_amount > 0)
            <div class="r">
                <span>Diskon</span>
                <span>- {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            @if($order->tax_amount > 0)
            <div class="r">
                <span>Pajak</span>
                <span>{{ number_format($order->tax_amount, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="r">
                <span>Total</span>
                <span>{{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="r">
                <span>Bayar ({{ $order->paymentMethod?->name ?? 'Lainnya' }})</span>
                <span>{{ number_format($order->amount_paid ?? $order->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="r">
                <span>Kembali</span>
                <span class="muted">{{ number_format(max(0, $order->change_due ?? 0), 0, ',', '.') }}</span>
            </div>
            @if(($order->points_earned ?? 0) > 0)
            <div class="r">
                <span>Poin Terkumpul</span>
                <span class="muted">+{{ number_format($order->points_earned, 0, ',', '.') }} poin</span>
            </div>
            @endif
        </div>

        <div class="line"></div>
        <p class="center">{{ $store->footer ?? 'Terima kasih telah berbelanja di ' . $storeName . '!' }}</p>

        @if($poweredByEnabled)
        <div class="line"></div>
        <div class="powered-by">
            @if($poweredByLogo)
                <img src="{{ url('/api/logo/' . ltrim($poweredByLogo, '/')) }}" alt="" class="powered-logo">
            @endif
            <span>{{ $poweredByText }}</span>
        </div>
        @endif
    </div>

    <script>
        (function () {
            var q = new URLSearchParams(window.location.search).get('size');
            var current = q || localStorage.getItem('receipt-size') || '{{ $defaultSize }}';
            if (!['58', '80', 'a4'].includes(current)) current = '{{ $defaultSize }}';
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