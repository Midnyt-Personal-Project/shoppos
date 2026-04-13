<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $sale->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 12px; background: #fff; color: #000; width: 300px; margin: 20px auto; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .center { text-align: center; }
        .row { display: flex; justify-content: space-between; padding: 2px 0; }
        .bold { font-weight: bold; }
        .large { font-size: 16px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="center bold large" style="margin-bottom:4px">{{ $sale->branch->shop->name }}</div>
    <div class="center" style="font-size:10px">{{ $sale->branch->name }}</div>
    @if($sale->branch->phone)
    <div class="center" style="font-size:10px">Tel: {{ $sale->branch->phone }}</div>
    @endif
    <div class="divider"></div>

    <div class="row"><span>Receipt #</span><span class="bold">{{ $sale->reference }}</span></div>
    <div class="row"><span>Date</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
    <div class="row"><span>Cashier</span><span>{{ $sale->user->name }}</span></div>
    @if($sale->customer)
    <div class="row"><span>Customer</span><span>{{ $sale->customer->name }}</span></div>
    @endif
    <div class="divider"></div>

    {{-- Items --}}
    @foreach($sale->items as $item)
    <div style="margin-bottom:4px">
        <div class="bold">{{ $item->product_name }}</div>
        <div class="row">
            <span>{{ number_format($item->quantity, 0) }} x {{ $sale->branch->shop->currency_symbol }}{{ number_format($item->price, 2) }}</span>
            <span class="bold">{{ $sale->branch->shop->currency_symbol }}{{ number_format($item->total, 2) }}</span>
        </div>
        @if($item->discount > 0)
        <div class="row" style="font-size:10px;color:#555"><span>  Discount</span><span>-{{ $sale->branch->shop->currency_symbol }}{{ number_format($item->discount, 2) }}</span></div>
        @endif
    </div>
    @endforeach

    <div class="divider"></div>
    <div class="row"><span>Subtotal</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->subtotal, 2) }}</span></div>
    @if($sale->discount > 0)
    <div class="row"><span>Discount</span><span>-{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->discount, 2) }}</span></div>
    @endif
    @if($sale->tax > 0)
    <div class="row"><span>Tax</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->tax, 2) }}</span></div>
    @endif
    <div class="divider"></div>
    <div class="row bold large"><span>TOTAL</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->total, 2) }}</span></div>
    <div class="divider"></div>

    {{-- Payments --}}
    @foreach($sale->payments as $pay)
    <div class="row"><span>{{ $pay->methodLabel() }}</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($pay->amount, 2) }}</span></div>
    @endforeach
    @if($sale->change > 0)
    <div class="row bold"><span>Change</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->change, 2) }}</span></div>
    @endif
    @if($sale->balance_due > 0)
    <div class="row bold" style="color:#c00"><span>BALANCE DUE</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->balance_due, 2) }}</span></div>
    @endif

    <div class="divider"></div>
    <div class="center" style="font-size:10px;margin-top:8px">Thank you for your purchase!</div>
    <div class="center" style="font-size:10px">Please come again</div>

    <div class="no-print center" style="margin-top:20px">
        <button onclick="window.print()" style="padding:8px 24px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Print</button>
        <button onclick="window.close()" style="padding:8px 24px;background:#334155;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
    </div>
    <script>
        // Auto-print when opened in a new tab
        window.addEventListener('load', () => {
            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>