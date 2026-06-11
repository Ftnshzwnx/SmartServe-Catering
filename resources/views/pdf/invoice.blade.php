<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invois #{{ $order->id }}</title>
    <style>
        @page {
            margin: 25px 35px;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2D3330;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-logo {
            font-size: 20px;
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-title {
            text-align: right;
            font-size: 16px;
            color: #8C8275;
            text-transform: uppercase;
            font-weight: bold;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .details-col {
            width: 50%;
            vertical-align: top;
        }
        .details-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            color: #8C8275;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items-table th {
            background-color: #4A6B5D;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #4A6B5D;
        }
        .items-table td {
            padding: 8px;
            border-bottom: 1px solid #E6E1DA;
            border-left: 1px solid #E6E1DA;
            border-right: 1px solid #E6E1DA;
        }
        .items-table tr.total-row td {
            border: none;
            padding-top: 3px;
            padding-bottom: 3px;
            font-weight: bold;
        }
        .items-table tr.total-row.first-total td {
            padding-top: 8px;
        }
        .text-right {
            text-align: right !important;
        }
        .policy-box {
            background-color: #FAF7F2;
            border: 1px solid #E6E1DA;
            border-radius: 6px;
            padding: 10px 12px;
            margin-top: 15px;
            font-size: 9px;
            color: #5C6460;
        }
        .footer-text {
            text-align: center;
            font-size: 8px;
            color: #8C8275;
            margin-top: 20px;
            border-top: 1px solid #E6E1DA;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td class="header-logo" style="vertical-align: middle;">
                    <img src="{{ public_path('img/logo.png') }}" style="height: 50px; width: auto; display: inline-block; vertical-align: middle;" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    Invois Acara
                </td>
            </tr>
        </table>

        <table class="details-table">
            <tr>
                <td class="details-col">
                    <div class="details-title">Maklumat Syarikat:</div>
                    <strong>{{ $settings['business_name'] ?? 'SmartServe Catering' }}</strong><br>
                    {!! nl2br(e($settings['business_address'] ?? "Gong Badak, Kuala Terengganu,\nTerengganu, Malaysia")) !!}<br>
                    Telefon: {{ $settings['contact_phone'] ?? '019-2094670' }}<br>
                    E-mel: {{ $settings['contact_email'] ?? 'info@smartservecatering.com' }}
                </td>
                <td class="details-col" style="padding-left: 50px;">
                    <div class="details-title">Billed To:</div>
                    <strong>{{ $order->user->full_name }}</strong><br>
                    Telefon: {{ $order->user->phone }}<br>
                    Alamat Majlis: {{ $order->delivery_address }}
                </td>
            </tr>
        </table>

        <table class="details-table" style="background-color: #FAF7F2; padding: 10px; border: 1px solid #E6E1DA;">
            <tr>
                <td style="width: 33%;">
                    <strong>No. Invois:</strong> #INV-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                </td>
                <td style="width: 33%; text-align: center;">
                    <strong>Tarikh Majlis:</strong> {{ $order->delivery_date }}
                </td>
                <td style="width: 33%; text-align: right;">
                    <strong>Masa Majlis:</strong> {{ $order->delivery_time }}
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Pakej & Add-on</th>
                    <th class="text-right" style="width: 80px;">Kuantiti</th>
                    <th class="text-right" style="width: 100px;">Harga (RM)</th>
                    <th class="text-right" style="width: 120px;">Subjumlah (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->package ? $item->package->package_name : $order->package_name }}</strong>
                        <div style="font-size: 10px; color: #8C8275; margin-top: 3px;">
                            Pakej katering terpilih berserta pilihan penyesuaian menu
                        </div>
                        @if($item->selected_dishes && is_array($item->selected_dishes) && count($item->selected_dishes) > 0)
                            <div style="font-size: 9px; color: #2D3330; margin-top: 6px; line-height: 1.3;">
                                <strong>Lauk Pilihan:</strong> {{ implode(', ', array_map(function($d) { return is_array($d) ? ($d['name'] ?? '') : (is_object($d) ? ($d->name ?? '') : $d); }, $item->selected_dishes)) }}
                            </div>
                        @elseif($item->package)
                            @php
                                $defaultNames = [];
                                if (!empty($item->package->description)) {
                                    $defaultNames = array_filter(array_map('trim', explode("\n", $item->package->description)));
                                }
                                if (empty($defaultNames) && $item->package->dishes) {
                                    $defaultNames = $item->package->dishes->pluck('name')->toArray();
                                }
                            @endphp
                            @if(!empty($defaultNames))
                                <div style="font-size: 9px; color: #2D3330; margin-top: 6px; line-height: 1.3;">
                                    <strong>Lauk Pilihan (Lalai):</strong> {{ implode(', ', $defaultNames) }}
                                </div>
                            @endif
                        @endif

                        @if($item->selected_addons && is_array($item->selected_addons) && count($item->selected_addons) > 0)
                            <div style="font-size: 9px; color: #b89047; margin-top: 4px; line-height: 1.3;">
                                <strong>Pilihan Tambahan (Add-ons):</strong> {{ implode(', ', $item->selected_addons) }}
                            </div>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }} pax</td>
                    <td class="text-right">{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
                
                <!-- Financial summary block -->
                @php
                    $depositPercent = (float)(\App\Models\Setting::where('setting_key', 'deposit_percentage')->first()->setting_value ?? 30);
                    $depositAmt = $order->total_price * ($depositPercent / 100);
                    $balanceAmt = $order->total_price - $depositAmt;
                    $subtotal = $order->total_price - ($order->delivery_fee ?? 0) + $order->discount_amount;
                @endphp

                <tr class="total-row first-total">
                    <td colspan="2"></td>
                    <td class="text-right">Subjumlah:</td>
                    <td class="text-right">RM {{ number_format($subtotal, 2) }}</td>
                </tr>

                @if(($order->delivery_fee ?? 0) > 0)
                <tr class="total-row">
                    <td colspan="2"></td>
                    <td class="text-right">Caj Penghantaran ({{ $order->delivery_zone }}):</td>
                    <td class="text-right">RM {{ number_format($order->delivery_fee, 2) }}</td>
                </tr>
                @endif
                
                @if($order->discount_amount > 0)
                <tr class="total-row" style="color: #4A6B5D;">
                    <td colspan="2"></td>
                    <td class="text-right">Diskaun:</td>
                    <td class="text-right">-RM {{ number_format($order->discount_amount, 2) }}</td>
                </tr>
                @endif
                
                <tr class="total-row" style="font-size: 14px;">
                    <td colspan="2"></td>
                    <td class="text-right">Jumlah Bersih:</td>
                    <td class="text-right" style="color: #4A6B5D; border-top: 1px solid #E6E1DA;">RM {{ number_format($order->total_price, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="2"></td>
                    <td class="text-right" style="color: #8C3A3A;">Deposit {{ $depositPercent }}%:</td>
                    <td class="text-right" style="color: #8C3A3A;">RM {{ number_format($depositAmt, 2) }}</td>
                </tr>

                <tr class="total-row">
                    <td colspan="2"></td>
                    <td class="text-right" style="color: #8C8275;">Baki {{ 100 - $depositPercent }}%:</td>
                    <td class="text-right" style="color: #8C8275;">RM {{ number_format($balanceAmt, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="policy-box">
            <strong>Syarat & Polisi Katering:</strong>
            <ol style="margin: 5px 0 0 15px; padding: 0;">
                <li>Komitmen deposit sebanyak 30% daripada jumlah harga diperlukan untuk mengesahkan tarikh majlis.</li>
                <li>Baki pembayaran 70% mestilah diselesaikan selewat-lewatnya 7 hari sebelum tarikh penghantaran majlis dijalankan.</li>
                <li>Pembatalan majlis dalam tempoh kurang dari 7 hari akan menyebabkan deposit 30% hangus sepenuhnya.</li>
                <li>Segala pembayaran hendaklah disusuli dengan bukti resit transaksi yang dimuat naik ke portal pelanggan.</li>
            </ol>
        </div>

        <div class="footer-text">
            Terima kasih kerana menempah perkhidmatan dengan kami. Invois ini dijana secara automatik.
        </div>
    </div>
</body>
</html>
