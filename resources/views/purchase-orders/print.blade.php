<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Purchase Order {{ $purchaseOrder->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #0f172a; background: #fff; }
        .page { max-width: 800px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 3px solid #16a34a; }
        .shop-name { font-size: 22px; font-weight: 700; color: #14532d; }
        .shop-sub { font-size: 11px; color: #64748b; margin-top: 3px; }
        .po-title { text-align: right; }
        .po-title h1 { font-size: 18px; font-weight: 700; color: #16a34a; }
        .po-title .ref { font-family: monospace; font-size: 13px; color: #0f172a; margin-top: 2px; }
        .po-title .status { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-pending  { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-received { background: #dcfce7; color: #14532d; }
        .status-partial  { background: #ede9fe; color: #5b21b6; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .info-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .info-box h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 8px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .info-label { color: #64748b; font-size: 11px; }
        .info-value { font-size: 11px; font-weight: 500; color: #0f172a; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead tr { background: #14532d; color: #fff; }
        thead th { padding: 10px 12px; text-align: left; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
        thead th:last-child { text-align: right; }
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 10px 12px; font-size: 11px; vertical-align: middle; }
        .product-name { font-weight: 600; color: #0f172a; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .badge-pending  { background: #fef3c7; color: #92400e; }
        .badge-received { background: #dcfce7; color: #14532d; }
        .badge-partial  { background: #fef3c7; color: #d97706; }
        .badge-missing  { background: #fee2e2; color: #991b1b; }
        tfoot td { padding: 10px 12px; font-size: 12px; border-top: 2px solid #e2e8f0; }
        .total-row { font-weight: 700; color: #14532d; font-size: 14px; }
        .notes-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 20px; background: #f8fafc; }
        .notes-box h3 { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 6px; }
        .signature-row { display: flex; justify-content: space-between; margin-top: 32px; }
        .sig-block { text-align: center; width: 200px; }
        .sig-line { border-top: 1px solid #94a3b8; margin-bottom: 6px; }
        .sig-label { font-size: 10px; color: #64748b; }
        .footer { text-align: center; margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="shop-name">{{ $purchaseOrder->branch->shop->name }}</div>
            <div class="shop-sub">{{ $purchaseOrder->branch->name }}</div>
            @if($purchaseOrder->branch->shop->address)
            <div class="shop-sub">{{ $purchaseOrder->branch->shop->address }}</div>
            @endif
            @if($purchaseOrder->branch->shop->phone)
            <div class="shop-sub">Tel: {{ $purchaseOrder->branch->shop->phone }}</div>
            @endif
        </div>
        <div class="po-title">
            <h1>PURCHASE ORDER</h1>
            <div class="ref">{{ $purchaseOrder->reference }}</div>
            <div>
                <span class="status status-{{ $purchaseOrder->status }}">{{ ucfirst($purchaseOrder->status) }}</span>
            </div>
        </div>
    </div>

    {{-- Info grid --}}
    <div class="info-grid">
        <div class="info-box">
            <h3>Order Details</h3>
            <div class="info-row"><span class="info-label">Date Requested</span><span class="info-value">{{ $purchaseOrder->created_at->format('d/m/Y') }}</span></div>
            <div class="info-row"><span class="info-label">Expected Delivery</span><span class="info-value">{{ $purchaseOrder->expected_at?->format('d/m/Y') ?? '—' }}</span></div>
            <div class="info-row"><span class="info-label">Requested By</span><span class="info-value">{{ $purchaseOrder->creator->name }}</span></div>
            @if($purchaseOrder->approver)
            <div class="info-row"><span class="info-label">Approved By</span><span class="info-value">{{ $purchaseOrder->approver->name }}</span></div>
            <div class="info-row"><span class="info-label">Approved On</span><span class="info-value">{{ $purchaseOrder->approved_at?->format('d/m/Y') }}</span></div>
            @endif
        </div>
        <div class="info-box">
            <h3>Supplier Information</h3>
            <div class="info-row"><span class="info-label">Supplier</span><span class="info-value">{{ $purchaseOrder->supplier_name ?: 'Not specified' }}</span></div>
            <div class="info-row"><span class="info-label">Phone</span><span class="info-value">{{ $purchaseOrder->supplier_phone ?: '—' }}</span></div>
            <div class="info-row"><span class="info-label">Branch</span><span class="info-value">{{ $purchaseOrder->branch->name }}</span></div>
            <div class="info-row"><span class="info-label">Total Items</span><span class="info-value">{{ $purchaseOrder->items->count() }} products</span></div>
        </div>
    </div>

    {{-- Items table --}}
    <table>
        <thead>
            <tr>
                <th style="width:30px">#</th>
                <th>Product Name</th>
                <th class="text-center">Qty Ordered</th>
                <th class="text-center">Qty Received</th>
                <th class="text-right">Unit Cost ({{ $purchaseOrder->branch->shop->currency_symbol }})</th>
                <th class="text-right">Total ({{ $purchaseOrder->branch->shop->currency_symbol }})</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $i => $item)
            <tr>
                <td style="color:#94a3b8">{{ $i + 1 }}</td>
                <td><span class="product-name">{{ $item->product_name }}</span></td>
                <td class="text-center" style="font-weight:600">{{ number_format($item->quantity_requested, 0) }}</td>
                <td class="text-center" style="color:{{ $item->quantity_received > 0 ? '#16a34a' : '#94a3b8' }};font-weight:600">
                    {{ $item->quantity_received > 0 ? number_format($item->quantity_received, 0) : '—' }}
                </td>
                <td class="text-right">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="text-right" style="font-weight:600">{{ number_format($item->lineTotal(), 2) }}</td>
                <td class="text-center">
                    <span class="status-badge badge-{{ $item->status }}">{{ ucfirst($item->status) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;font-weight:600;color:#64748b;">Estimated Total:</td>
                <td class="text-right total-row">{{ $purchaseOrder->branch->shop->currency_symbol }}{{ number_format($purchaseOrder->totalCost(), 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Notes --}}
    @if($purchaseOrder->notes)
    <div class="notes-box">
        <h3>Notes</h3>
        <p style="font-size:11px;color:#475569;">{{ $purchaseOrder->notes }}</p>
    </div>
    @endif

    {{-- Signature lines --}}
    <div class="signature-row">
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">Requested By: {{ $purchaseOrder->creator->name }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">Approved By: {{ $purchaseOrder->approver?->name ?? '____________________' }}</div>
        </div>
        <div class="sig-block">
            <div class="sig-line"></div>
            <div class="sig-label">Received By: ____________________</div>
        </div>
    </div>

    <div class="footer">
        Generated by OmniPOS &bull; {{ $purchaseOrder->branch->shop->name }} &bull; {{ now()->format('d/m/Y H:i') }}
    </div>

    {{-- Print button --}}
    <div class="no-print" style="text-align:center;margin-top:24px">
        <button onclick="window.print()"
                style="padding:10px 32px;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600">
            🖨️ Print
        </button>
        <button onclick="window.close()"
                style="padding:10px 24px;background:#334155;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;margin-left:8px">
            Close
        </button>
    </div>

    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    </script>
</div>
</body>
</html>