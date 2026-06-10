<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sebut Harga / Quotation {{ $quoteNumber }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2D3330;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .quotation-box {
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
            font-size: 22px;
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-logo span {
            color: #C5A880;
        }
        .header-title {
            text-align: right;
            font-size: 16px;
            color: #8C8275;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
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
            font-size: 9px;
            color: #8C8275;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #4A6B5D;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #4A6B5D;
        }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #E6E1DA;
            border-left: 1px solid #E6E1DA;
            border-right: 1px solid #E6E1DA;
            vertical-align: top;
        }
        .items-table tr.total-row td {
            border: none;
            padding-top: 10px;
            font-weight: bold;
        }
        .text-right {
            text-align: right !important;
        }
        .section-title {
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .policy-box {
            background-color: #FAF7F2;
            border: 1px solid #E6E1DA;
            border-radius: 8px;
            padding: 12px 15px;
            margin-top: 25px;
            font-size: 9px;
            color: #5C6460;
        }
        .policy-title {
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-size: 9px;
        }
        .footer-text {
            text-align: center;
            font-size: 8px;
            color: #8C8275;
            margin-top: 30px;
            border-top: 1px solid #E6E1DA;
            padding-top: 10px;
        }
        .dish-tag {
            display: inline-block;
            background-color: #FAF7F2;
            border: 1px solid #E6E1DA;
            color: #2D3330;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            margin-right: 4px;
            margin-bottom: 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="quotation-box">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td class="header-logo" style="vertical-align: middle;">
                    <img src="{{ public_path('img/logo.png') }}" style="height: 50px; width: auto; display: inline-block; vertical-align: middle;" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    Sebut Harga / Quotation
                </td>
            </tr>
        </table>

        <!-- Details -->
        <table class="details-table">
            <tr>
                <!-- Company Details -->
                <td class="details-col">
                    <div class="details-title">Syarikat / Caterer</div>
                    <div style="font-weight: bold;">{{ $settings['business_name'] ?? 'SmartServe Catering' }}</div>
                    <div>Tel: {{ $settings['contact_phone'] ?? '012-3456789' }}</div>
                    <div>Email: {{ $settings['contact_email'] ?? 'hello@smartservecatering.com' }}</div>
                    <div>Alamat: {{ $settings['business_address'] ?? 'Kuala Lumpur, Malaysia' }}</div>
                </td>
                <!-- Quotation Details -->
                <td class="details-col" style="text-align: right;">
                    <div class="details-title">Butiran Sebut Harga</div>
                    <div><strong>Rujukan:</strong> {{ $quoteNumber }}</div>
                    <div><strong>Tarikh:</strong> {{ $dateGenerated }}</div>
                    <div><strong>Sah Sehingga:</strong> {{ $dateExpiry }} (30 Hari)</div>
                    <div style="margin-top: 8px;">
                        <div class="details-title" style="text-align: right;">Pelanggan / Client</div>
                        <div style="font-weight: bold;">{{ $user->full_name || $user->name }}</div>
                        <div>Email: {{ $user->email }}</div>
                        <div>Tel: {{ $user->phone ?? 'N/A' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Package & Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Penerangan Pakej & Hidangan</th>
                    <th style="width: 15%; text-align: right;">Harga Per Pax</th>
                    <th style="width: 15%; text-align: center;">Jumlah Pax</th>
                    <th style="width: 20%; text-align: right;">Jumlah (RM)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Package Row -->
                <tr>
                    <td>
                        <strong style="font-size: 12px; color: #4A6B5D; text-transform: uppercase;">{{ $package->package_name }}</strong>
                        <div style="margin-top: 5px; color: #8C8275; font-size: 9px; line-height: 1.3;">
                            {{ $package->description }}
                        </div>
                        
                        <!-- Selected Dishes Breakdown -->
                        @if($dishes->count() > 0)
                            <div style="margin-top: 10px;">
                                <strong style="font-size: 9px; color: #4A6B5D; text-transform: uppercase; display: block; margin-bottom: 4px;">Pilihan Menu Utama:</strong>
                                @foreach($dishes->groupBy('category') as $category => $categoryDishes)
                                    <div style="margin-bottom: 3px;">
                                        <span style="font-weight: bold; color: #8C8275; font-size: 9px;">{{ $category }}:</span>
                                        <span style="font-weight: 600;">{{ $categoryDishes->pluck('name')->join(', ') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td class="text-right" style="vertical-align: middle;">RM {{ number_format($packagePrice, 2) }}</td>
                    <td style="text-align: center; vertical-align: middle;">{{ $quantity }} pax</td>
                    <td class="text-right" style="vertical-align: middle; font-weight: bold;">RM {{ number_format($packagePrice * $quantity, 2) }}</td>
                </tr>

                <!-- Addons Rows -->
                @if($addons->count() > 0)
                    @foreach($addons as $addon)
                        <tr>
                            <td style="padding-left: 20px;">
                                <strong><i class="fas fa-plus" style="color: #4A6B5D; font-size: 8px; margin-right: 4px;"></i> Tambahan / Add-on: {{ $addon->addon_name }}</strong>
                            </td>
                            <td class="text-right">RM {{ number_format((float)$addon->price_per_pax, 2) }}</td>
                            <td style="text-align: center;">{{ $quantity }} pax</td>
                            <td class="text-right" style="font-weight: bold;">RM {{ number_format((float)$addon->price_per_pax * $quantity, 2) }}</td>
                        </tr>
                    @endforeach
                @endif

                <!-- Total Summary Rows -->
                <tr class="total-row">
                    <td colspan="2" style="border: none;"></td>
                    <td style="text-align: right; font-size: 10px; color: #8C8275; text-transform: uppercase;">Jumlah Keseluruhan:</td>
                    <td class="text-right" style="font-size: 12px; color: #2D3330; border-top: 1px solid #2D3330;">RM {{ number_format($grandTotal, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="border: none;"></td>
                    <td style="text-align: right; font-size: 10px; color: #8C8275; text-transform: uppercase;">Deposit Dituntut ({{ $depositPercentage }}%):</td>
                    <td class="text-right" style="font-size: 12px; color: #4A6B5D;">RM {{ number_format($depositAmount, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="2" style="border: none;"></td>
                    <td style="text-align: right; font-size: 10px; color: #8C8275; text-transform: uppercase;">Baki Perlu Dibayar ({{ 100 - $depositPercentage }}%):</td>
                    <td class="text-right" style="font-size: 12px; color: #8C8275;">RM {{ number_format($balanceAmount, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Terms & Conditions / Policy Box -->
        <div class="policy-box">
            <div class="policy-title">Terma & Syarat Sebut Harga</div>
            <ul style="margin: 0; padding-left: 15px; line-height: 1.6;">
                <li>Sebut harga ini adalah sah untuk tempoh <strong>30 hari</strong> dari tarikh dikeluarkan.</li>
                <li>Tempahan tarikh hanya akan disahkan secara rasmi selepas pembayaran deposit sebanyak <strong>{{ $depositPercentage }}%</strong> disahkan oleh pihak pengurusan kami.</li>
                <li>Baki bayaran sebanyak <strong>{{ 100 - $depositPercentage }}%</strong> hendaklah diselesaikan selewat-lewatnya <strong>3 hari</strong> sebelum tarikh acara berlangsung.</li>
                <li>Sebarang pembatalan tempahan hendaklah dimaklumkan mengikut polisi pembatalan syarikat yang ditetapkan di portal.</li>
                <li>Pembayaran boleh dilakukan secara pindahan bank manual ke akaun yang tertera di dalam portal pelanggan semasa checkout.</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="footer-text">
            Terima kasih kerana memilih {{ $settings['business_name'] ?? 'SmartServe Catering' }}. Kami komited memberikan perkhidmatan terbaik untuk majlis anda.
        </div>
    </div>
</body>
</html>
