<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $reference }}</title>
    <style>
        /* Reset & Page */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .invoice-wrapper {
            max-width: 900px;
            width: 100%;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 8px 24px rgba(0, 0, 0, 0.04);
            padding: 48px 56px;
            position: relative;
            overflow: hidden;
        }

        /* ========== WATERMARK ========== */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 140px;
            font-weight: 900;
            color: rgba(15, 23, 42, 0.07);   /* <-- slightly more visible */
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
            z-index: 0;
            letter-spacing: 18px;
        }

        .invoice-content {
            position: relative;
            z-index: 1;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 24px;
            margin-bottom: 28px;
        }
        .shop-info h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .shop-info p {
            font-size: 14px;
            color: #475569;
            margin: 2px 0;
        }
        .shop-info .brand-line {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .invoice-meta {
            text-align: right;
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }
        .invoice-meta strong {
            color: #0f172a;
            font-weight: 600;
        }
        .invoice-meta .badge {
            display: inline-block;
            background: #0ea5e9;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 12px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }

        /* Customer card */
        .customer-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e2e8f0;
        }
        .customer-card .label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
        }
        .customer-card .name {
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .customer-card .status {
            font-size: 13px;
            color: #475569;
            background: #f1f5f9;
            padding: 4px 14px;
            border-radius: 30px;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 24px;
        }
        .items-table th {
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 10px 6px 8px 6px;
        }
        .items-table td {
            padding: 12px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #1e293b;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .items-table tbody tr:hover {
            background: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-muted {
            color: #94a3b8;
            font-weight: 400;
        }

        /* Totals */
        .totals-box {
            margin-left: auto;
            width: 320px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
        }
        .totals-box .row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 14px;
            color: #334155;
        }
        .totals-box .row.discount {
            color: #ef4444;
        }
        .totals-box .row.grand {
            font-weight: 700;
            font-size: 20px;
            color: #0f172a;
            padding-top: 10px;
            margin-top: 4px;
            border-top: 1px solid #cbd5e1;
        }
        .totals-box .row .label {
            font-weight: 500;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #94a3b8;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .invoice-footer .thanks {
            color: #475569;
            font-weight: 500;
        }
        .invoice-footer .terms {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Print button (hidden on print) */
        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background: #1e293b;
        }
        .print-btn svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Print styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
                display: block;
            }
            .invoice-wrapper {
                box-shadow: none;
                border-radius: 0;
                padding: 30px 40px;
                max-width: 100%;
            }
            .print-btn {
                display: none !important;
            }
            .watermark {
                font-size: 100px;
                opacity: 0.6;   /* ensures watermark is visible on print */
            }
            .items-table tbody tr:hover {
                background: transparent;
            }
            .customer-card {
                background: #f8fafc;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Small screen */
        @media (max-width: 640px) {
            .invoice-wrapper {
                padding: 24px;
            }
            .invoice-header {
                flex-direction: column;
                gap: 12px;
            }
            .invoice-meta {
                text-align: left;
                width: 100%;
            }
            .customer-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .totals-box {
                width: 100%;
            }
            .invoice-footer {
                flex-direction: column;
                gap: 6px;
            }
        }
    </style>
</head>
<body>

    <div class="invoice-wrapper">
        <!-- ========== WATERMARK ========== -->
        <div class="watermark">INVOICE</div>

        <!-- Content -->
        <div class="invoice-content">

            <!-- Print Button (visible only on screen) -->
            <button class="print-btn no-print" onclick="window.print()">
                <svg viewBox="0 0 24 24"><path d="M6 9V3h12v6"/><path d="M6 21h12v-6H6v6z"/><path d="M18 9v6H6V9"/><path d="M18 15h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2h-2"/><path d="M6 15H4a2 2 0 0 1-2-2v-2a2 2 0 0 1 2-2h2"/></svg>
                Print Invoice
            </button>

            <!-- Header -->
            <div class="invoice-header">
                <div class="shop-info">
                    <h1>{{ $shop->name }}</h1>
                    <p>{{ $shop->address ?? '' }}</p>
                    <div class="brand-line">
                        <p>📞 {{ $shop->phone ?? '' }}</p>
                        <span style="color:#94a3b8; margin:0 6px;">|</span>
                        <p>✉️ {{ $shop->email ?? '' }}</p>
                    </div>
                </div>
                <div class="invoice-meta">
                    <div><strong>Invoice #</strong> <span class="badge">{{ $reference }}</span></div>
                    <div><strong>Date</strong> {{ $date }}</div>
                    <div><strong>Due</strong> {{ $dueDate ?? 'On receipt' }}</div>
                </div>
            </div>

            <!-- Customer -->
            <div class="customer-card">
                <div>
                    <div class="label">Bill To</div>
                    <div class="name">{{ $customer->name ?? 'Walk-in Customer' }}</div>
                    @if(isset($customer) && $customer)
                        <div style="font-size:13px; color:#475569; margin-top:2px;">
                            {{ $customer->phone ?? '' }} @if($customer->email) · {{ $customer->email }} @endif
                        </div>
                    @endif
                </div>
                <div class="status">🔹 Unpaid</div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:45%;">Item</th>
                        <th style="width:15%;" class="text-right">Qty</th>
                        <th style="width:20%;" class="text-right">Price</th>
                        <th style="width:15%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item['name'] }}</td>
                            <td class="text-right">{{ $item['qty'] }}</td>
                            <td class="text-right">{{ $shop->currency_symbol }}{{ number_format($item['price'], 2) }}</td>
                            <td class="text-right">{{ $shop->currency_symbol }}{{ number_format($item['price'] * $item['qty'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-box">
                <div class="row">
                    <span class="label">Subtotal</span>
                    <span>{{ $shop->currency_symbol }}{{ number_format($subtotal, 2) }}</span>
                </div>
                @if($discount > 0)
                    <div class="row discount">
                        <span class="label">Discount</span>
                        <span>-{{ $shop->currency_symbol }}{{ number_format($discount, 2) }}</span>
                    </div>
                @endif
                <div class="row">
                    <span class="label">Tax</span>
                    <span>{{ $shop->currency_symbol }}{{ number_format($tax, 2) }}</span>
                </div>
                <div class="row grand">
                    <span class="label">Grand Total</span>
                    <span>{{ $shop->currency_symbol }}{{ number_format($grandTotal, 2) }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                <span class="thanks">Thank you for your business!</span>
                <span class="terms">This is a computer-generated invoice · Valid without signature</span>
            </div>

        </div><!-- /invoice-content -->
    </div><!-- /invoice-wrapper -->

</body>
</html>