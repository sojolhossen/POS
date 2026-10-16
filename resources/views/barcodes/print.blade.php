<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Print Barcode Labels — {{ config('app.name') }}</title>
    <style>
        @page {
            size: {{ $labelWidth }}mm {{ $labelHeight }}mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #000;
        }
        .controls {
            padding: 15px;
            background: #1e293b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-print {
            background: #4f46e5;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 20px;
            justify-content: center;
        }
        .barcode-label {
            width: {{ $labelWidth }}mm;
            height: {{ $labelHeight }}mm;
            padding: 2mm 3mm;
            background: #fff;
            border: 1px dashed #cbd5e1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            page-break-after: always;
            overflow: hidden;
        }
        .label-store {
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }
        .label-product {
            font-size: 7.5pt;
            font-weight: bold;
            line-height: 1.1;
            max-height: 16pt;
            overflow: hidden;
        }
        .label-specs {
            font-size: 6.5pt;
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-weight: 600;
        }
        .label-barcode {
            width: 95%;
            margin: 1mm 0;
            display: flex;
            justify-content: center;
        }
        .label-barcode svg {
            width: 100%;
            height: 12mm;
        }
        .label-number {
            font-family: monospace;
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .label-price {
            font-size: 8.5pt;
            font-weight: 900;
        }

        @media print {
            body {
                background: #fff;
            }
            .controls {
                display: none !important;
            }
            .labels-container {
                padding: 0;
                gap: 0;
            }
            .barcode-label {
                border: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="controls">
        <div>
            <strong>Barcode Label Print Preview</strong> (Total: {{ count($labels) }} labels | Dimension: {{ $labelWidth }}mm × {{ $labelHeight }}mm)
        </div>
        <button class="btn-print" onclick="window.print()">Print Labels (Ctrl + P)</button>
    </div>

    <div class="labels-container">
        @foreach($labels as $label)
            <div class="barcode-label">
                @if($showStoreName && !empty($label['store_name']))
                    <div class="label-store">{{ $label['store_name'] }}</div>
                @endif

                <div class="label-product">{{ $label['product_name'] }}</div>

                <div class="label-specs">
                    <span>SKU: {{ $label['sku'] }}</span>
                    <span>{{ $label['color'] }} | <strong>{{ $label['size'] }}</strong></span>
                </div>

                <div class="label-barcode">
                    {!! $label['barcode_svg'] !!}
                </div>

                <div class="label-number">{{ $label['barcode'] }}</div>

                <div class="label-price">MRP: ৳ {{ number_format($label['price'], 2) }}</div>
            </div>
        @endforeach
    </div>

</body>
</html>
