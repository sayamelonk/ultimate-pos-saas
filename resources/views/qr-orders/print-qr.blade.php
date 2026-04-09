<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Code - {{ $table->display_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .print-page {
            width: 80mm;
            margin: 0 auto;
            padding: 10mm;
            text-align: center;
        }
        .outlet-name {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .table-name {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .qr-container {
            display: inline-block;
            padding: 8px;
            border: 2px solid #000;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .qr-container svg {
            display: block;
        }
        .instruction {
            font-size: 12px;
            color: #555;
            margin-bottom: 4px;
        }
        .url {
            font-size: 9px;
            color: #888;
            word-break: break-all;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; padding: 16px;">
        <button onclick="window.print()" style="padding: 8px 24px; font-size: 14px; background: #4f46e5; color: white; border: none; border-radius: 8px; cursor: pointer;">
            Print QR Code
        </button>
    </div>

    <div class="print-page">
        <p class="outlet-name">{{ $table->outlet->name }}</p>
        <p class="table-name">{{ $table->display_name }}</p>

        <div class="qr-container">
            {!! $qrCode !!}
        </div>

        <p class="instruction">Scan QR code to order</p>
        <p class="instruction">Pindai QR untuk pesan</p>
        <p class="url">{{ $table->getQrMenuUrl() }}</p>
    </div>
</body>
</html>
