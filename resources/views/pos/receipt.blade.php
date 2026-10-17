<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $sale->invoice_number }} - Thermal Receipt</title>
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
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-top: 1px double #000; margin: 6px 0; }

        .header-title {
            font-size: 14px;
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

        .reprint-badge {
            border: 2px solid #000;
            padding: 2px 6px;
            font-weight: 900;
            display: inline-block;
            margin-bottom: 6px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .actions-bar { display: none !important; }
            .receipt-container {
                width: 80mm;
                box-shadow: none;
                padding: 0;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

<div>
    <!-- Interactive Screen Navigation -->
    <div class="actions-bar">
        <button onclick="window.print()" class="btn">🖨️ Print 80mm Receipt</button>
        <a href="{{ route('pos.index') }}" class="btn btn-secondary">← Back to POS Station</a>
    </div>

    <!-- Thermal Paper Canvas (80mm) -->
    <div class="receipt-container">

        @if(!empty($isReprint))
            <div class="text-center">
                <span class="reprint-badge">** DUPLICATE REPRINT **</span>
            </div>
        @endif

        <div class="text-center">
            @if(\App\Models\AppSetting::getAppLogo())
                <div style="margin-bottom: 5px;">
                    <img src="{{ \App\Models\AppSetting::getAppLogo() }}" alt="Logo" style="max-height: 38px; max-width: 140px; margin: 0 auto; display: block; object-fit: contain;">
                </div>
            @endif
            <div class="header-title">{{ $sale->store->name ?? \App\Models\AppSetting::getAppName('CLOTHING STORE BD') }}</div>
            <div style="font-size: 10px;">{{ $sale->store->address ?? \App\Models\AppSetting::getCompanyAddress() }}</div>
            <div style="font-size: 10px;">Phone: {{ $sale->store->phone ?? \App\Models\AppSetting::getCompanyPhone() }}</div>
            @if($sale->store->bin_number)
                <div style="font-size: 10px;">Mushak BIN: {{ $sale->store->bin_number }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <div>
            <div><strong>Invoice:</strong> {{ $sale->invoice_number }}</div>
            <div><strong>Date:</strong> {{ $sale->created_at->format('d/m/Y h:i A') }}</div>
            <div><strong>Cashier:</strong> {{ $sale->cashier->name ?? 'Staff' }}</div>
            @if($sale->customer)
                <div><strong>Customer:</strong> {{ $sale->customer->name }} ({{ $sale->customer->phone }})</div>
            @endif
        </div>

        <div class="divider"></div>

        <!-- Line Items -->
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item & Variant</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Rate</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $it)
                    <tr>
                        <td colspan="4" class="bold">
                            {{ $it->productVariant->product->name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 9.5px; padding-left: 6px;">
                            {{ $it->productVariant->color ?? 'Standard' }}/{{ $it->productVariant->size ?? 'Free' }}
                        </td>
                        <td class="text-right">{{ $it->quantity }}</td>
                        <td class="text-right bold">{{ number_format($it->line_total ?? ($it->quantity * $it->unit_price - $it->discount_amount), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals Calculation -->
        <table style="width: 100%; font-size: 11px;">
            <tr>
                <td>Subtotal (Gross):</td>
                <td class="text-right">৳{{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->discount_amount > 0)
                <tr>
                    <td>Promotion / Discount:</td>
                    <td class="text-right">-৳{{ number_format($sale->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if($sale->points_discount > 0)
                <tr>
                    <td>Loyalty Points Redeemed:</td>
                    <td class="text-right">-৳{{ number_format($sale->points_discount, 2) }}</td>
                </tr>
            @endif
            @if($sale->tax_amount > 0)
                <tr>
                    <td>Tax / VAT:</td>
                    <td class="text-right">+৳{{ number_format($sale->tax_amount, 2) }}</td>
                </tr>
            @endif
            @if($sale->rounding_amount != 0)
                <tr>
                    <td>BDT Rounding:</td>
                    <td class="text-right">{{ $sale->rounding_amount > 0 ? '+৳' : '-৳' }}{{ number_format(abs($sale->rounding_amount), 2) }}</td>
                </tr>
            @endif
            <tr style="font-size: 13px; font-weight: 900;">
                <td style="padding-top: 4px;">NET PAYABLE:</td>
                <td class="text-right" style="padding-top: 4px;">৳{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        </table>

        <div class="double-divider"></div>

        <!-- Multi-Rail Payment Breakdown -->
        <div style="font-size: 10px;">
            <div class="bold" style="margin-bottom: 2px;">PAYMENT SETTLEMENT:</div>
            @foreach($sale->payments as $p)
                <div style="display: flex; justify-content: space-between;">
                    <span>• {{ strtoupper($p->payment_method) }}:</span>
                    <span class="bold">৳{{ number_format($p->amount, 2) }}</span>
                </div>
                @if($p->payment_method === 'cash' && $p->tendered_amount > 0)
                    <div style="display: flex; justify-content: space-between; color: #444; padding-left: 8px;">
                        <span>  Tendered: ৳{{ number_format($p->tendered_amount, 2) }}</span>
                        <span>  Change: ৳{{ number_format($p->change_amount, 2) }}</span>
                    </div>
                @endif
                @if($p->reference)
                    <div style="padding-left: 8px; font-size: 9px; color: #555;">
                        Ref: {{ $p->reference }}
                    </div>
                @endif
            @endforeach
        </div>

        @if($sale->customer && $sale->customer->loyalty_points > 0)
            <div class="divider"></div>
            <div style="font-size: 10px; text-align: center;">
                Available Loyalty Points: <strong>{{ $sale->customer->loyalty_points }} pts</strong> (৳{{ $sale->customer->loyalty_points }})
            </div>
        @endif

        <div class="divider"></div>

        <!-- QR Code representation -->
        <div class="text-center" style="margin: 8px 0;">
            <svg style="width: 50px; height: 50px; margin: 0 auto;" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5">
                <rect x="3" y="3" width="7" height="7" rx="1"/>
                <rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/>
                <rect x="6" y="6" width="1" height="1" fill="#000"/>
                <rect x="17" y="6" width="1" height="1" fill="#000"/>
                <rect x="6" y="17" width="1" height="1" fill="#000"/>
                <path d="M14 14h3v3h-3zM17 17h4v4h-4z"/>
            </svg>
            <div style="font-size: 8.5px; font-weight: bold;">{{ $sale->invoice_number }}</div>
        </div>

        <!-- 7-Day Return Policy & Footer Message -->
        <div class="text-center" style="font-size: 9px; line-height: 1.4;">
            <div><strong>7-DAY RETURN / EXCHANGE POLICY</strong></div>
            <div>ক্রয়ের ৭ দিনের মধ্যে ক্যাশ মেমো সহ পণ্য পরিবর্তন করা যাবে। (ট্যাগ এবং অব্যবহৃত থাকতে হবে)</div>
            <div style="margin-top: 4px;">{{ \App\Models\AppSetting::getReceiptFooter('Thank you for shopping with us! • www.fashionpos.bd') }}</div>
        </div>
    </div>
</div>

</body>
</html>
