<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Challan {{ $transfer->reference_number }} - {{ strtoupper($copy) }} COPY</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.4;
            padding: 24px;
        }

        /* Top Action Toolbar (Never Printed) */
        .no-print {
            max-width: 210mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(37,99,235,0.3);
        }
        .btn-primary:hover { background: #1d4ed8; }

        .btn-secondary {
            background: #f8fafc;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover { background: #e2e8f0; }

        .role-tabs {
            display: flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
        }

        .role-tab {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            transition: all 0.15s;
        }

        .role-tab.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-weight: 700;
        }

        /* Printable Sheet Layout */
        .sheet-wrapper {
            max-width: 210mm;
            margin: 0 auto;
        }

        .challan-sheet {
            background: #ffffff;
            width: 100%;
            padding: 16mm 14mm;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Official Watermark Badge */
        .copy-watermark {
            position: absolute;
            top: 24mm;
            right: 14mm;
            border: 2px solid #0f172a;
            padding: 4px 10px;
            text-align: center;
            font-weight: 900;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .super-admin-badge {
            border-color: #1e3a8a;
            color: #1e3a8a;
            background: #eff6ff;
        }

        .warehouse-badge {
            border-color: #7c2d12;
            color: #7c2d12;
            background: #fff7ed;
        }

        .store-badge {
            border-color: #064e3b;
            color: #064e3b;
            background: #f0fdf4;
        }

        /* Header Elements */
        .company-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .company-title {
            font-size: 18px;
            font-weight: 900;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .company-sub {
            font-size: 10px;
            color: #475569;
            font-weight: 600;
            margin-top: 2px;
        }

        .challan-name {
            font-size: 13px;
            font-weight: 800;
            margin-top: 4px;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            border-radius: 6px;
            background: #f8fafc;
        }

        .meta-block h4 {
            font-size: 9.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .meta-block .facility-name {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
        }

        .meta-block .facility-detail {
            font-size: 10px;
            color: #475569;
            margin-top: 1px;
        }

        .transit-banner {
            background: #ffffff;
            border: 1px dashed #94a3b8;
            padding: 6px 10px;
            margin-bottom: 12px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #334155;
        }

        /* Items Table */
        .challan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .challan-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #0f172a;
        }

        .challan-table td {
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            color: #1e293b;
        }

        .challan-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .font-bold { font-weight: 700; }

        .challan-table tfoot td {
            background: #e2e8f0 !important;
            font-weight: 800;
            border-top: 2px solid #0f172a;
            font-size: 10.5px;
        }

        /* Role Specific Instruction Box */
        .role-instructions {
            border: 1px solid #cbd5e1;
            border-left: 4px solid #0f172a;
            padding: 8px 10px;
            background: #f8fafc;
            margin-bottom: 20px;
            font-size: 9.5px;
            color: #334155;
        }

        /* Official Signatures Grid */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 28px;
            padding-top: 8px;
        }

        .signature-box {
            border-top: 1.5px solid #0f172a;
            padding-top: 4px;
            text-align: center;
        }

        .sig-officer-name {
            font-size: 10px;
            font-weight: 800;
            color: #0f172a;
            min-height: 14px;
        }

        .sig-officer-role {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .sig-date {
            font-size: 8px;
            color: #94a3b8;
            font-family: monospace;
            margin-top: 1px;
        }

        .seal-box {
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #cbd5e1;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .sheet-wrapper {
                max-width: 100%;
            }

            .challan-sheet {
                box-shadow: none;
                border: none;
                padding: 0;
                margin-bottom: 0;
                page-break-after: always;
            }

            .challan-sheet:last-child {
                page-break-after: auto;
            }

            .challan-table th {
                background: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .super-admin-badge, .warehouse-badge, .store-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- On-Screen Toolbar (Hidden on Print) -->
    <div class="no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-secondary">
                ← Back to Transfer
            </a>
            <span style="font-weight: 800; font-size: 13px; color: #0f172a;">
                Transfer Challan: <span class="font-mono">{{ $transfer->reference_number }}</span>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <div class="role-tabs">
                <a href="{{ route('transfers.print', [$transfer, 'copy' => 'super_admin']) }}" class="role-tab {{ $copy === 'super_admin' ? 'active' : '' }}">
                    🏢 Super Admin / Audit
                </a>
                <a href="{{ route('transfers.print', [$transfer, 'copy' => 'warehouse_staff']) }}" class="role-tab {{ $copy === 'warehouse_staff' ? 'active' : '' }}">
                    📦 Warehouse Gate Pass
                </a>
                <a href="{{ route('transfers.print', [$transfer, 'copy' => 'store_manager']) }}" class="role-tab {{ in_array($copy, ['store_manager', 'cashier']) ? 'active' : '' }}">
                    🏪 Store Inward Copy
                </a>
                <a href="{{ route('transfers.print', [$transfer, 'copy' => 'triplicate']) }}" class="role-tab {{ $copy === 'triplicate' ? 'active' : '' }}">
                    📑 Complete Triplicate (3 Copies)
                </a>
            </div>

            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print Challan Now
            </button>
        </div>
    </div>

    @php
        $copiesToRender = [];
        if ($copy === 'triplicate') {
            $copiesToRender = ['super_admin', 'warehouse_staff', 'store_manager'];
        } else {
            $copiesToRender = [in_array($copy, ['store_manager', 'cashier']) ? 'store_manager' : $copy];
        }
    @endphp

    <div class="sheet-wrapper">
        @foreach($copiesToRender as $currentCopy)
            <div class="challan-sheet">
                
                <!-- Watermark Stamp Badge -->
                @if($currentCopy === 'super_admin' || $currentCopy === 'accountant')
                    <div class="copy-watermark super-admin-badge">
                        HEAD OFFICE AUDIT COPY<br>
                        <span style="font-size: 8px; font-weight: 700;">প্রধান কার্যালয় নিরীক্ষা কপি</span>
                    </div>
                @elseif($currentCopy === 'warehouse_staff')
                    <div class="copy-watermark warehouse-badge">
                        WAREHOUSE DISPATCH & GATE PASS<br>
                        <span style="font-size: 8px; font-weight: 700;">গুদাম ছাড়পত্র ও গেট পাস</span>
                    </div>
                @else
                    <div class="copy-watermark store-badge">
                        STORE INWARD & RECEIVING COPY<br>
                        <span style="font-size: 8px; font-weight: 700;">শাখা স্টোর পণ্য গ্রহণ চালান</span>
                    </div>
                @endif

                <!-- Company Header -->
                <div class="company-header">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        @if(\App\Models\AppSetting::getAppLogo())
                            <img src="{{ \App\Models\AppSetting::getAppLogo() }}" alt="Logo" style="max-height: 42px; max-width: 130px; object-fit: contain;">
                        @endif
                        <div>
                            <div class="company-title">{{ \App\Models\AppSetting::getAppName('CLOTHING RETAIL NETWORK BD') }}</div>
                            <div class="company-sub">{{ \App\Models\AppSetting::getAppTagline('Central Supply Chain & Inventory Logistics Division') }} | {{ \App\Models\AppSetting::getCompanyAddress('Dhaka, Bangladesh') }}</div>
                            <div class="challan-name">Inter-Store Goods Movement Challan & Transit Note</div>
                        </div>
                    </div>
                    <div style="text-align: right; margin-right: 170px;">
                        <div style="font-size: 9px; color: #64748b; font-weight: 700; text-transform: uppercase;">Challan No / চালান নং:</div>
                        <div class="font-mono" style="font-size: 13px; font-weight: 900; color: #0f172a;">{{ $transfer->reference_number }}</div>
                        <div style="font-size: 9px; color: #64748b; margin-top: 2px;">
                            Date: <strong class="font-mono text-black">{{ $transfer->created_at->format('d-M-Y') }}</strong> | 
                            Time: <strong class="font-mono text-black">{{ $transfer->created_at->format('h:i A') }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Origin & Destination Facility Grid -->
                <div class="meta-grid">
                    <div class="meta-block">
                        <h4>1. Origin / Dispatch Facility (প্রেরক ওয়্যারহাউস / শপ):</h4>
                        <div class="facility-name">{{ $transfer->sourceStore->name }}</div>
                        <div class="facility-detail">Store / Warehouse Code: <strong class="font-mono">{{ $transfer->sourceStore->code }}</strong></div>
                        <div class="facility-detail">Address: {{ $transfer->sourceStore->address ?? 'Central Logistics Warehouse' }}</div>
                        <div class="facility-detail">Contact: {{ $transfer->sourceStore->phone ?? 'N/A' }} | Dispatch Officer: {{ $transfer->dispatcher->name ?? 'Pending' }}</div>
                    </div>
                    <div class="meta-block">
                        <h4>2. Destination / Receiving Store (প্রাপক আউটলেট / শপ):</h4>
                        <div class="facility-name">{{ $transfer->destinationStore->name }}</div>
                        <div class="facility-detail">Store / Outlet Code: <strong class="font-mono">{{ $transfer->destinationStore->code }}</strong></div>
                        <div class="facility-detail">Address: {{ $transfer->destinationStore->address ?? 'Retail Outlet Branch' }}</div>
                        <div class="facility-detail">Contact: {{ $transfer->destinationStore->phone ?? 'N/A' }} | Receiving Officer: {{ $transfer->receiver->name ?? 'Store Staff' }}</div>
                    </div>
                </div>

                <!-- Transit & Logistics Info Bar -->
                <div class="transit-banner">
                    <div><strong>Transfer Status:</strong> {{ strtoupper($transfer->status) }}</div>
                    <div><strong>Mode of Transport:</strong> Company Covered Van / Logistics Carrier</div>
                    <div><strong>Requisition Note:</strong> {{ $transfer->notes ?? 'Standard Store Replenishment' }}</div>
                    <div><strong>Total Items:</strong> {{ $transfer->items->count() }} Line Items</div>
                </div>

                <!-- Role Specific Itemized Table -->
                <table class="challan-table">
                    <thead>
                        <tr>
                            <th style="width: 25px;" class="text-center">#</th>
                            <th>Garment / Product Description</th>
                            <th class="font-mono" style="width: 100px;">SKU / Barcode</th>
                            <th style="width: 70px;" class="text-center">Variant</th>
                            <th style="width: 55px;" class="text-right">Req.</th>
                            <th style="width: 55px;" class="text-right">Disp.</th>
                            <th style="width: 55px;" class="text-right">Rec.</th>

                            @if($currentCopy === 'super_admin' || $currentCopy === 'accountant')
                                <th style="width: 65px;" class="text-right">Unit Price</th>
                                <th style="width: 75px;" class="text-right">Total (BDT)</th>
                            @elseif($currentCopy === 'warehouse_staff')
                                <th style="width: 80px;" class="text-center">Carton / Pack</th>
                                <th style="width: 65px;" class="text-center">Seal Check</th>
                            @else
                                <th style="width: 65px;" class="text-right">Discrepancy</th>
                                <th style="width: 80px;" class="text-center">Condition</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php 
                            $totalValuation = 0; 
                        @endphp
                        @foreach($transfer->items as $idx => $item)
                            @php
                                $unitPrice = $item->productVariant?->price ?? 0;
                                $dispQty = $item->quantity_dispatched > 0 ? $item->quantity_dispatched : $item->quantity_requested;
                                $lineTotal = $unitPrice * $dispQty;
                                $totalValuation += $lineTotal;
                            @endphp
                            <tr>
                                <td class="text-center font-mono">{{ $idx + 1 }}</td>
                                <td>
                                    <strong style="font-size: 10.5px;">{{ $item->productVariant?->product?->name ?? 'Garment Item' }}</strong>
                                </td>
                                <td class="font-mono text-center" style="font-size: 9px;">
                                    <div>{{ $item->productVariant?->sku ?? 'N/A' }}</div>
                                    @if($item->productVariant?->barcode)
                                        <div style="color: #64748b;">{{ $item->productVariant->barcode }}</div>
                                    @endif
                                </td>
                                <td class="text-center" style="font-size: 9px;">
                                    {{ $item->productVariant?->color ?? '-' }} / {{ $item->productVariant?->size ?? '-' }}
                                </td>
                                <td class="text-right font-mono font-bold">{{ $item->quantity_requested }}</td>
                                <td class="text-right font-mono font-bold">{{ $item->quantity_dispatched }}</td>
                                <td class="text-right font-mono font-bold">{{ $item->quantity_received }}</td>

                                @if($currentCopy === 'super_admin' || $currentCopy === 'accountant')
                                    <td class="text-right font-mono">৳{{ number_format($unitPrice, 2) }}</td>
                                    <td class="text-right font-mono font-bold">৳{{ number_format($lineTotal, 2) }}</td>
                                @elseif($currentCopy === 'warehouse_staff')
                                    <td class="text-center font-mono" style="font-size: 9px;">Box #{{ ceil(($idx + 1) / 5) }}</td>
                                    <td class="text-center" style="font-size: 9px;">[✓] Intact</td>
                                @else
                                    <td class="text-right font-mono font-bold" style="color: {{ ($item->quantity_discrepancy ?? 0) != 0 ? '#b91c1c' : '#64748b' }};">
                                        {{ ($item->quantity_discrepancy ?? 0) != 0 ? $item->quantity_discrepancy : '-' }}
                                    </td>
                                    <td class="text-center" style="font-size: 9px;">
                                        {{ ($item->quantity_discrepancy ?? 0) != 0 ? 'Shortage / Damaged' : 'Verified Good' }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right" style="letter-spacing: 0.5px; text-transform: uppercase;">
                                Total Sum:
                            </td>
                            <td class="text-right font-mono">{{ $transfer->items->sum('quantity_requested') }} pcs</td>
                            <td class="text-right font-mono">{{ $transfer->items->sum('quantity_dispatched') }} pcs</td>
                            <td class="text-right font-mono">{{ $transfer->items->sum('quantity_received') }} pcs</td>

                            @if($currentCopy === 'super_admin' || $currentCopy === 'accountant')
                                <td class="text-right font-mono">Total Value:</td>
                                <td class="text-right font-mono">৳{{ number_format($totalValuation, 2) }}</td>
                            @elseif($currentCopy === 'warehouse_staff')
                                <td colspan="2" class="text-center" style="font-size: 9px;">All Cartons Sealed</td>
                            @else
                                <td class="text-right font-mono" style="color: {{ $transfer->items->sum('quantity_discrepancy') != 0 ? '#b91c1c' : '#0f172a' }};">
                                    {{ $transfer->items->sum('quantity_discrepancy') }} pcs
                                </td>
                                <td class="text-center" style="font-size: 9px;">Confirmed Inward</td>
                            @endif
                        </tr>
                    </tfoot>
                </table>

                <!-- Role Specific Terms & Compliance Notice -->
                <div class="role-instructions">
                    @if($currentCopy === 'super_admin' || $currentCopy === 'accountant')
                        <strong>HEAD OFFICE & AUDIT NOTICE:</strong> This document certifies the inter-store inventory transfer authorization under Super Admin governance. All valuation totals are synchronized with the enterprise inventory asset ledger. Any shortage or loss in transit must be reported to the Finance Department immediately.
                    @elseif($currentCopy === 'warehouse_staff')
                        <strong>WAREHOUSE & GATE PASS CLEARANCE:</strong> Goods listed above have been picked, verified, and sealed for transit from {{ $transfer->sourceStore->name }}. Security personnel at the factory/warehouse gate must verify carton count and seals prior to allowing transport vehicle dispatch.
                    @else
                        <strong>DESTINATION STORE INWARD MANDATE:</strong> Upon arrival, the store manager or cashier must immediately inspect all cartons against this challan. Confirm receipt in FashionPOS. Discrepancies or damaged garments must be noted above and reported within 24 hours of receipt.
                    @endif
                </div>

                <!-- 4 Executive Signatures & Official Seals Grid -->
                <div class="signatures-grid">
                    <!-- 1. Requester -->
                    <div class="signature-box">
                        <div class="seal-box">
                            <span style="border: 1px dashed #cbd5e1; padding: 2px 8px; border-radius: 4px;">REQUESTED</span>
                        </div>
                        <div class="sig-officer-name">{{ $transfer->creator->name ?? 'Requester' }}</div>
                        <div class="sig-officer-role">{{ $transfer->creator?->role_name ?? 'Requisition Prepared' }}</div>
                        <div class="sig-date">{{ $transfer->created_at->format('d-M-Y H:i') }}</div>
                    </div>

                    <!-- 2. Super Admin Authorization -->
                    <div class="signature-box" style="border-top-color: #1e3a8a;">
                        <div class="seal-box">
                            @if($transfer->approver)
                                <span style="border: 1.5px solid #1e3a8a; color: #1e3a8a; padding: 2px 6px; border-radius: 4px; font-weight: 800;">AUTHORIZED</span>
                            @else
                                <span style="color: #cbd5e1;">AWAITING</span>
                            @endif
                        </div>
                        <div class="sig-officer-name" style="color: #1e3a8a;">
                            {{ $transfer->approver->name ?? 'Super Administrator' }}
                        </div>
                        <div class="sig-officer-role">Super Admin Approval</div>
                        <div class="sig-date">
                            {{ $transfer->approved_at ? $transfer->approved_at->format('d-M-Y H:i') : 'Pending Authorization' }}
                        </div>
                    </div>

                    <!-- 3. Warehouse Dispatcher & Security -->
                    <div class="signature-box" style="border-top-color: #7c2d12;">
                        <div class="seal-box">
                            @if($transfer->dispatcher)
                                <span style="border: 1.5px solid #7c2d12; color: #7c2d12; padding: 2px 6px; border-radius: 4px; font-weight: 800;">DISPATCHED</span>
                            @else
                                <span style="color: #cbd5e1;">GATE PASS</span>
                            @endif
                        </div>
                        <div class="sig-officer-name">
                            {{ $transfer->dispatcher->name ?? 'Warehouse Officer' }}
                        </div>
                        <div class="sig-officer-role">Dispatcher & Gate Pass</div>
                        <div class="sig-date">
                            {{ $transfer->dispatched_at ? $transfer->dispatched_at->format('d-M-Y H:i') : 'Pending Dispatch' }}
                        </div>
                    </div>

                    <!-- 4. Destination Verified Receiver -->
                    <div class="signature-box" style="border-top-color: #064e3b;">
                        <div class="seal-box">
                            @if($transfer->receiver)
                                <span style="border: 1.5px solid #064e3b; color: #064e3b; padding: 2px 6px; border-radius: 4px; font-weight: 800;">INWARD SEAL</span>
                            @else
                                <span style="color: #cbd5e1;">STORE SEAL</span>
                            @endif
                        </div>
                        <div class="sig-officer-name" style="color: #064e3b;">
                            {{ $transfer->receiver->name ?? 'Receiving Staff' }}
                        </div>
                        <div class="sig-officer-role">{{ $transfer->destinationStore->name }} Receiver</div>
                        <div class="sig-date">
                            {{ $transfer->received_at ? $transfer->received_at->format('d-M-Y H:i') : 'Awaiting Physical Arrival' }}
                        </div>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

</body>
</html>
