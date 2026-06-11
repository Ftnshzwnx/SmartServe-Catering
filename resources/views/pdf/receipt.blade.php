<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resit Rasmi #{{ $order->id }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2D3330;
            font-size: 12px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .receipt-box {
            max-width: 800px;
            margin: auto;
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .header-logo {
            font-size: 24px;
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-title {
            text-align: right;
            font-size: 18px;
            color: #4A6B5D;
            text-transform: uppercase;
            font-weight: bold;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-col {
            width: 50%;
            vertical-align: top;
        }
        .details-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            color: #8C8275;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .receipt-body {
            border: 1px solid #E6E1DA;
            background-color: #FAF9F6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .receipt-row {
            padding: 8px 0;
            border-bottom: 1px solid #E6E1DA;
        }
        .receipt-row:last-child {
            border-bottom: none;
        }
        .receipt-label {
            font-weight: bold;
            color: #8C8275;
            width: 150px;
            display: inline-block;
        }
        .receipt-value {
            display: inline-block;
        }
        .stamp {
            border: 2px solid #4A6B5D;
            color: #4A6B5D;
            text-transform: uppercase;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            padding: 5px 15px;
            margin-top: 15px;
            border-radius: 5px;
            letter-spacing: 1px;
        }
        .footer-text {
            text-align: center;
            font-size: 9px;
            color: #8C8275;
            margin-top: 50px;
            border-top: 1px solid #E6E1DA;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <table class="header-table">
            <tr>
                <td class="header-logo" style="vertical-align: middle;">
                    <img src="{{ public_path('img/logo.png') }}" style="height: 50px; width: auto; display: inline-block; vertical-align: middle;" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    Resit Pembayaran Rasmi
                </td>
            </tr>
        </table>

        <table class="details-table">
            <tr>
                <td class="details-col">
                    <div class="details-title">Daripada:</div>
                    <strong>{{ $settings['business_name'] ?? 'SmartServe Catering' }}</strong><br>
                    {!! nl2br(e($settings['business_address'] ?? "Gong Badak, Kuala Terengganu,\nTerengganu, Malaysia")) !!}<br>
                    Telefon: {{ $settings['contact_phone'] ?? '019-2094670' }}
                </td>
                <td class="details-col" style="padding-left: 50px;">
                    <div class="details-title">Diterima Oleh:</div>
                    <strong>{{ $order->user->full_name }}</strong><br>
                    No. Telefon: {{ $order->user->phone }}<br>
                    E-mel: {{ $order->user->email }}
                </td>
            </tr>
        </table>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="receipt-label">No. Resit:</span>
                <span class="receipt-value">#RCP-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}-{{ time() }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Rujukan Tempahan:</span>
                <span class="receipt-value">INV-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Deskripsi Acara:</span>
                <span class="receipt-value">{{ $order->package_name }} (Tarikh: {{ $order->delivery_date }})</span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Jenis Pembayaran:</span>
                <span class="receipt-value">
                    @if($order->status === 'Confirmed')
                        Pembayaran Deposit Katering (30%)
                    @elseif($order->status === 'Completed' || $order->status === 'Delivered')
                        Pembayaran Penuh Katering (100%)
                    @else
                        Pembayaran Katering
                    @endif
                </span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Jumlah Dibayar:</span>
                <span class="receipt-value" style="font-size: 14px; font-weight: bold; color: #4A6B5D;">
                    @if($order->status === 'Confirmed')
                        RM {{ number_format($order->total_price * 0.3, 2) }}
                    @elseif($order->status === 'Completed' || $order->status === 'Delivered')
                        RM {{ number_format($order->total_price, 2) }}
                    @else
                        RM {{ number_format($order->total_price, 2) }}
                    @endif
                </span>
            </div>
            <div class="receipt-row">
                <span class="receipt-label">Status Transaksi:</span>
                <span class="receipt-value">
                    <span class="stamp">DITERIMA & SAH</span>
                </span>
            </div>
        </div>

        <div class="footer-text">
            Terima kasih atas pembayaran anda. Resit ini dikeluarkan secara elektronik dan tidak memerlukan tandatangan fizikal.
        </div>
    </div>
</body>
</html>
