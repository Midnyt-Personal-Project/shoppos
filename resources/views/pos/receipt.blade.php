<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $sale->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            background: #fff;
            color: #000;
            width: 300px;
            margin: 20px auto;
            font-weight: bold; /* All text bold */
        }
        .divider {
            border-top: 1px solid #000;
            margin: 8px 0;
        }
        .center { text-align: center; }
        .row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-weight: bold;
        }
        .bold { font-weight: bold; } /* explicitly keep bold */
        .large { font-size: 16px; font-weight: bold; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        /* Ensure any special case text is also bold */
        .row span, .center, .bold, .large, .divider, .button-container {
            font-weight: bold;
        }
        /* Remove any potential lighter shades */
        .row span { color: #000; }
        .center { color: #000; }
        /* For balance due, keep red but ensure it prints dark (red prints as dark gray on B&W) */
        .balance-due { color: #000; background: #fff; font-weight: bold; }
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

    {{-- Items table header --}}
    <div class="row bold" style="margin-bottom:4px">
        <span>QTY</span><span>ITEM</span><span>AMOUNT</span>
    </div>
    @foreach($sale->items as $item)
    <div style="margin-bottom:4px">
        <div class="row">
            <span>{{ number_format($item->quantity, 0) }}</span>
            <span>{{ $item->product_name }}</span>
            <span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($item->total, 2) }}</span>
        </div>
    </div>
    @endforeach

    <div class="divider"></div>
    <div class="row"><span>Subtotal</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->subtotal, 2) }}</span></div>
    @if($sale->discount > 0)
    <div class="row"><span>Discount</span><span>-{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->discount, 2) }}</span></div>
    @endif

    {{-- Display each tax from breakdown --}}
    @if($sale->tax_breakdown && count($sale->tax_breakdown) > 0)
        @foreach($sale->tax_breakdown as $taxName => $taxAmount)
        <div class="row">
            <span>{{ $taxName }}</span>
            <span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($taxAmount, 2) }}</span>
        </div>
        @endforeach
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
    <div class="row bold balance-due"><span>BALANCE DUE</span><span>{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->balance_due, 2) }}</span></div>
    @endif

    <div class="divider"></div>
    <div class="center" style="font-size:10px;margin-top:8px">Thank you for your purchase!</div>
    <div class="center" style="font-size:10px">Please come again</div>

    <div class="no-print center" style="margin-top:20px">
        <button onclick="window.print()" style="padding:8px 24px;background:#16a34a;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px">🖨️ Print</button>
        <button onclick="window.close()" style="padding:8px 24px;background:#334155;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:14px;margin-left:8px">Close</button>
    </div>
    <script>
        window.addEventListener('load', () => { setTimeout(() => window.print(), 500); });
    </script>
</body>
</html>