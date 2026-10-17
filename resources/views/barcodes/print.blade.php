<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Barcode Labels — {{ config('app.name') }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #0f172a;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Top Controls Bar (Hidden during print) */
        .controls {
            position: sticky;
            top: 0;
            z-index: 50;
            padding: 12px 20px;
            background: #1e293b;
            color: #fff;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
        .controls-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
        }
        .controls-badge {
            background: #334155;
            color: #38bdf8;
            font-weight: 700;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .controls-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-toggle {
            background: #334155;
            color: #e2e8f0;
            border: 1px solid #475569;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-toggle.active {
            background: #0284c7;
            color: #fff;
            border-color: #0284c7;
        }
        .btn-print {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }
        .btn-print:hover {
            background: #059669;
        }

        /* Sheet container on screen */
        .preview-wrapper {
            padding: 24px;
            display: flex;
            justify-content: center;
            min-height: calc(100vh - 60px);
        }

        /* 1. Thermal Roll Mode (Default for sticker printers) */
        body.mode-thermal .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }

        /* 2. A4 Sheet Grid Mode (For standard office printers / PDF export) */
        body.mode-a4 .labels-container {
            width: 210mm;
            min-height: 297mm;
            background: #fff;
            padding: 10mm 8mm;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            display: grid;
            grid-template-columns: repeat(3, 62mm);
            grid-auto-rows: 32mm;
            gap: 3mm 4mm;
            justify-content: center;
            align-content: start;
        }

        /* Standard Barcode Label Component */
        .barcode-label {
            width: {{ $labelWidth }}mm;
            height: {{ $labelHeight }}mm;
            max-width: {{ $labelWidth }}mm;
            max-height: {{ $labelHeight }}mm;
            padding: 1.5mm 2.2mm 1.5mm 2.2mm;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
            overflow: hidden;
            line-height: 1.15;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
        }

        body.mode-a4 .barcode-label {
            width: 62mm;
            height: 32mm;
            max-width: 62mm;
            max-height: 32mm;
            padding: 2mm 3mm;
            border: 1px dashed #e2e8f0;
        }

        /* 1. BRAND NAME (Top Center) */
        .label-brand {
            text-align: center;
            font-size: 8pt;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.1;
            margin-bottom: 0.5mm;
        }

        body.mode-a4 .label-brand {
            font-size: 8.5pt;
        }

        /* 2. BARCODE & NUMBER (Middle Center) */
        .label-barcode-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: 0.2mm 0;
        }

        .label-barcode-svg {
            width: 100%;
            height: 9.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .label-barcode-svg svg {
            width: 96% !important;
            height: 100% !important;
            max-height: 9.5mm !important;
            display: block;
        }

        .label-barcode-number {
            text-align: center;
            font-family: "Courier New", Courier, monospace, sans-serif;
            font-size: 8pt;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #000000;
            line-height: 1;
            margin-top: 0.5mm;
        }

        /* 3. BOTTOM ROW: PRODUCT DETAILS (Left) & PRICE (Right) */
        .label-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            width: 100%;
            padding-top: 0.5mm;
        }

        .label-product-details {
            flex: 1;
            min-width: 0;
            text-align: left;
            padding-right: 2mm;
        }

        .detail-name {
            font-size: 6.8pt;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.15;
            margin-bottom: 0.2mm;
        }

        .detail-item {
            font-size: 5.8pt;
            font-weight: 600;
            color: #475569;
            line-height: 1.15;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .detail-item strong {
            color: #0f172a;
            font-weight: 700;
        }

        .detail-sku {
            color: #64748b;
            font-size: 5.2pt;
        }

        /* PRICE (Right, Big Bold) */
        .label-price-container {
            flex-shrink: 0;
            text-align: right;
            display: flex;
            align-items: baseline;
            justify-content: flex-end;
            color: #000000;
            line-height: 1;
        }

        .currency-symbol {
            font-size: 8.5pt;
            font-weight: 800;
            margin-right: 0.5px;
        }

        .price-amount {
            font-size: 11.5pt;
            font-weight: 900;
            letter-spacing: -0.3px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body.mode-a4 .price-amount {
            font-size: 12.5pt;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
            }
            .controls {
                display: none !important;
            }
            .preview-wrapper {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
            }

            /* Thermal Roll Print Rules */
            body.mode-thermal {
                width: {{ $labelWidth }}mm;
                margin: 0;
                padding: 0;
            }
            body.mode-thermal .labels-container {
                display: block !important;
                padding: 0 !important;
                margin: 0 !important;
                gap: 0 !important;
            }
            body.mode-thermal .barcode-label {
                border: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                page-break-after: always;
                break-after: page;
            }

            /* A4 Sheet Print Rules */
            body.mode-a4 .labels-container {
                box-shadow: none !important;
                padding: 8mm 6mm !important;
                margin: 0 auto !important;
                gap: 2.5mm 3.5mm !important;
            }
            body.mode-a4 .barcode-label {
                border: 0.5px dashed #cbd5e1 !important;
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>

    <style id="page-style">
        @page {
            size: {{ $labelWidth }}mm {{ $labelHeight }}mm;
            margin: 0;
        }
    </style>
</head>
<body class="mode-thermal">

    <!-- Print Control Bar -->
    <div class="controls">
        <div class="controls-info">
            <strong>Barcode Print Preview</strong>
            <span class="controls-badge">{{ count($labels) }} Labels</span>
            <span>Size: {{ $labelWidth }}mm × {{ $labelHeight }}mm</span>
        </div>

        <div class="controls-actions">
            <span style="font-size: 11px; color: #94a3b8; font-weight: 600;">Paper Type:</span>
            <button type="button" class="btn-toggle active" id="btn-thermal" onclick="setMode('thermal')">
                🏷️ Thermal Roll (50×30mm)
            </button>
            <button type="button" class="btn-toggle" id="btn-a4" onclick="setMode('a4')">
                📄 A4 Sheet Grid (3×8)
            </button>
            <button type="button" class="btn-print" onclick="window.print()">
                🖨️ Print Labels (Ctrl + P)
            </button>
        </div>
    </div>

    <!-- Labels Sheet Preview -->
    <div class="preview-wrapper">
        <div class="labels-container" id="labels-container">
            @foreach($labels as $label)
                <div class="barcode-label">
                    <!-- 1. BRAND NAME (Top Center) -->
                    @if($showStoreName)
                        <div class="label-brand">{{ !empty($label['brand_name']) ? $label['brand_name'] : \App\Models\AppSetting::getAppName('FashionPOS') }}</div>
                    @endif

                    <!-- 2. BARCODE & NUMBER (Middle Center) -->
                    <div class="label-barcode-wrapper">
                        <div class="label-barcode-svg">
                            {!! $label['barcode_svg'] !!}
                        </div>
                        <div class="label-barcode-number">
                            {{ $label['barcode'] }}
                        </div>
                    </div>

                    <!-- 3. BOTTOM ROW: PRODUCT DETAILS (Left) & PRICE (Right) -->
                    <div class="label-bottom-row">
                        <!-- Product Details: Name, Size, Color, SKU -->
                        <div class="label-product-details">
                            <div class="detail-name" title="{{ $label['product_name'] }}">{{ $label['product_name'] }}</div>
                            @if(!empty($label['size']))
                                <div class="detail-item">Size {{ $label['size'] }}</div>
                            @endif
                            @if(!empty($label['color']))
                                <div class="detail-item">Color: {{ $label['color'] }}</div>
                            @endif
                            @if(!empty($label['sku']))
                                <div class="detail-item detail-sku">SKU: {{ $label['sku'] }}</div>
                            @endif
                        </div>

                        <!-- Price (Large & Bold) -->
                        <div class="label-price-container">
                            <span class="currency-symbol">৳</span><span class="price-amount">{{ number_format($label['price'], 0) }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function setMode(mode) {
            const body = document.body;
            const btnThermal = document.getElementById('btn-thermal');
            const btnA4 = document.getElementById('btn-a4');
            const pageStyle = document.getElementById('page-style');

            if (mode === 'thermal') {
                body.className = 'mode-thermal';
                btnThermal.classList.add('active');
                btnA4.classList.remove('active');
                pageStyle.innerHTML = '@page { size: {{ $labelWidth }}mm {{ $labelHeight }}mm; margin: 0; }';
            } else {
                body.className = 'mode-a4';
                btnA4.classList.add('active');
                btnThermal.classList.remove('active');
                pageStyle.innerHTML = '@page { size: A4 portrait; margin: 6mm; }';
            }
        }
    </script>
</body>
</html>
