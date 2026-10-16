<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Voucher {{ $posReturn->return_number }} - Credit Memo</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, 'Bangla', sans-serif;
        }

        body {
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .receipt-container {
            width: 80mm;
            background: #fff;
            padding: 15px 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            font-size: 11px;
            color: #000;
            line-height: 1.3;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-top: 1px double #000; margin: 6px 0; }

        .header-title {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
            margin: 4px 0;
        }

        .receipt-table th {
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            text-align: left;
        }

        .receipt-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .actions-bar {
            margin-bottom: 15px;
            text-align: center;
        }

        .btn {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 4px;
        }

        .btn-secondary {
            background: #64748b;
        }

        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .actions-bar { display: none !important; }
            .receipt-container { width: 80mm; box-shadow: none; padding: 0; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>

<div>
    <div class="actions-bar">
        <button onclick="window.print()" class="btn">🖨️ Print Return Voucher</button>
        <a href="{{ route('returns.index') }}" class="btn btn-secondary">← Back to Returns</a>
    </div>

    <div class="receipt-container">
        <div class="text-center">
            <div class="header-title">{{ $posReturn->store->name ?? 'CLOTHING STORE BD' }}</div>
            <div style="font-size: 10px;">CREDIT MEMO / RETURN VOUCHER</div>
            <div style="font-size: 10px;">Phone: {{ $posReturn->store->phone ?? '01700-000000' }}</div>
        </div>

        <div class="divider"></div>

        <div>
            <div><strong>Return Voucher:</strong> {{ $posReturn->return_number }}</div>
            <div><strong>Original Invoice:</strong> {{ $posReturn->originalSale->invoice_number }}</div>
            <div><strong>Date:</strong> {{ $posReturn->created_at->format('d/m/Y h:i A') }}</div>
            <div><strong>Processed By:</strong> {{ $posReturn->processedBy->name ?? 'Staff' }}</div>
            @if($posReturn->approvedBy)
                <div><strong>Approved By:</strong> {{ $posReturn->approvedBy->name }} (Manager)</div>
            @endif
            @if($posReturn->customer)
                <div><strong>Customer:</strong> {{ $posReturn->customer->name }} ({{ $posReturn->customer->phone }})</div>
            @endif
        </div>

        <div class="divider"></div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Returned Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Refund (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($posReturn->items as $it)
                    <tr>
                        <td colspan="3" class="bold">
                            {{ $it->productVariant->product->name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 9.5px; padding-left: 6px;">
                            {{ $it->productVariant->color ?? 'Standard' }}/{{ $it->productVariant->size ?? 'Free' }} [{{ strtoupper($it->condition) }}]
                        </td>
                        <td class="text-right">{{ $it->quantity }}</td>
                        <td class="text-right bold">{{ number_format($it->refund_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="double-divider"></div>

        <table style="width: 100%; font-size: 11px;">
            <tr style="font-size: 12px; font-weight: 900;">
                <td>TOTAL SETTLED:</td>
                <td class="text-right">৳{{ number_format($posReturn->total_refund_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Settlement Rail:</td>
                <td class="text-right uppercase bold">{{ $posReturn->settlement_method }}</td>
            </tr>
            @if($posReturn->reason)
                <tr>
                    <td colspan="2" style="font-size: 10px; padding-top: 4px; color: #444;">
                        Note: {{ $posReturn->reason }}
                    </td>
                </tr>
            @endif
        </table>

        <div class="divider"></div>

        <div class="text-center" style="margin: 8px 0;">
            <svg style="width: 45px; height: 45px; margin: 0 auto;" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="6" y="6" width="1" height="1" fill="#000"/>
                <rect x="17" y="6" width="1" height="1" fill="#000"/>
                <rect x="6" y="17" width="1" height="1" fill="#000"/>
                <path d="M14 14h3v3h-3zM17 17h4v4h-4z"/>
            </svg>
            <div style="font-size: 8.5px; font-weight: bold;">{{ $posReturn->return_number }}</div>
        </div>

        <div class="text-center" style="font-size: 9px; margin-top: 6px;">
            <div>Retail ERP Audit & Financial Settlement Voucher</div>
        </div>
    </div>
</div>

</body>
</html>
