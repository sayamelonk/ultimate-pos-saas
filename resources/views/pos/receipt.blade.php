<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $transaction->transaction_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            background: #fff;
            padding: 10px;
            max-width: 300px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .store-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-info {
            font-size: 10px;
            color: #666;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 11px;
        }
        .items-table {
            width: 100%;
            margin: 10px 0;
        }
        .item-row {
            margin-bottom: 8px;
        }
        .item-name {
            font-weight: bold;
        }
        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding-left: 10px;
        }
        .totals {
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .grand-total {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin: 10px 0;
        }
        .payment-info {
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
        .points-info {
            background: #f0f0f0;
            padding: 5px;
            text-align: center;
            margin: 10px 0;
            font-size: 11px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="store-name">{{ $transaction->outlet->name }}</div>
        <div class="store-info">
            @if($transaction->outlet->address)
                {{ $transaction->outlet->address }}<br>
            @endif
            @if($transaction->outlet->phone)
                Tel: {{ $transaction->outlet->phone }}
            @endif
        </div>
    </div>

    <div class="divider"></div>

    <div class="info-row">
        <span>No:</span>
        <span>{{ $transaction->transaction_number }}</span>
    </div>
    <div class="info-row">
        <span>Date:</span>
        <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="info-row">
        <span>Cashier:</span>
        <span>{{ $transaction->user->name }}</span>
    </div>
    @if($transaction->customer)
        <div class="info-row">
            <span>Customer:</span>
            <span>{{ $transaction->customer->name }}</span>
        </div>
    @endif

    <div class="divider"></div>

    <div class="items-table">
        @foreach($transaction->items as $item)
            <div class="item-row">
                <div class="item-name">{{ $item->item_name }}</div>
                <div class="item-details">
                    <span>{{ number_format($item->quantity, 2) }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($item->discount_amount > 0)
                    <div class="item-details" style="color: #c00;">
                        <span>Discount</span>
                        <span>-Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="divider"></div>

    <div class="totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->discount_amount > 0)
            <div class="total-row" style="color: #c00;">
                <span>Discount</span>
                <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if($transaction->tax_amount > 0)
            <div class="total-row">
                <span>Tax ({{ $transaction->tax_percentage }}%)</span>
                <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if($transaction->service_charge_amount > 0)
            <div class="total-row">
                <span>Service ({{ $transaction->service_charge_percentage }}%)</span>
                <span>Rp {{ number_format($transaction->service_charge_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if($transaction->rounding != 0)
            <div class="total-row">
                <span>Rounding</span>
                <span>Rp {{ number_format($transaction->rounding, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    <div class="grand-total total-row">
        <span>TOTAL</span>
        <span>Rp {{ number_format($transaction->grand_total, 0, ',', '.') }}</span>
    </div>

    <div class="payment-info">
        @foreach($transaction->payments as $payment)
            <div class="total-row">
                <span>{{ $payment->paymentMethod->name }}</span>
                <span>Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
            </div>
        @endforeach
        @if($transaction->change_amount > 0)
            <div class="total-row" style="font-weight: bold;">
                <span>Change</span>
                <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    @if($transaction->customer && ($transaction->points_earned > 0 || $transaction->points_redeemed > 0))
        <div class="points-info">
            @if($transaction->points_earned > 0)
                <div>Points Earned: +{{ number_format($transaction->points_earned) }}</div>
            @endif
            @if($transaction->points_redeemed > 0)
                <div>Points Redeemed: -{{ number_format($transaction->points_redeemed) }}</div>
            @endif
            <div>Current Points: {{ number_format($transaction->customer->total_points) }}</div>
        </div>
    @endif

    <div class="divider"></div>

    <div class="footer">
        <p>Thank you for your purchase!</p>
        <p>Please keep this receipt for returns</p>
        <p style="margin-top: 10px;">{{ config('app.name') }}</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html>
