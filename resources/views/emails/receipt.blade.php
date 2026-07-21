<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
        .total { font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <h1>Receipt #{{ $sale->reference }}</h1>
    <p><strong>Date:</strong> {{ $sale->created_at->format('Y-m-d H:i') }}</p>
    <p><strong>Customer:</strong> {{ $sale->customer->name ?? 'Walk-in Customer' }}</p>

    <table>
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $sale->branch->shop->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                    <td>{{ $sale->branch->shop->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Subtotal:</strong> {{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->subtotal, 2) }}</p>
    <p><strong>Discount:</strong> -{{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->discount, 2) }}</p>
    <p><strong>Tax:</strong> {{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->tax_total, 2) }}</p>
    <p class="total"><strong>Total:</strong> {{ $sale->branch->shop->currency_symbol }}{{ number_format($sale->total, 2) }}</p>

    <p>Thank you for your business!</p>
</body>
</html>