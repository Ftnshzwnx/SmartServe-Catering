<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Jualan SmartServe Catering</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2D3330;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .report-box {
            padding: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-logo {
            font-size: 20px;
            font-weight: bold;
            color: #4A6B5D;
            text-transform: uppercase;
        }
        .header-title {
            text-align: right;
            font-size: 14px;
            color: #8C8275;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-box {
            background-color: #FAF7F2;
            border: 1px solid #E6E1DA;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th {
            background-color: #4A6B5D;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 6px;
            text-align: left;
            border: 1px solid #4A6B5D;
        }
        .items-table td {
            padding: 6px;
            border: 1px solid #E6E1DA;
            vertical-align: top;
        }
        .text-right {
            text-align: right !important;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #E6E1DA;
        }
        .footer-text {
            text-align: center;
            font-size: 8px;
            color: #8C8275;
            margin-top: 30px;
            border-top: 1px solid #E6E1DA;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="report-box">
        <table class="header-table">
            <tr>
                <td class="header-logo" style="vertical-align: middle;">
                    <img src="{{ public_path('img/logo.png') }}" style="height: 40px; width: auto; display: inline-block; vertical-align: middle;" alt="Logo">
                </td>
                <td class="header-title" style="vertical-align: middle;">
                    Laporan Ringkasan Tempahan
                </td>
            </tr>
        </table>

        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td><strong>Tarikh Dijana:</strong> {{ date('d-m-Y H:i:s') }}</td>
                    <td style="text-align: right;"><strong>Jumlah Tempahan:</strong> {{ $orders->count() }}</td>
                </tr>
                <tr>
                    <td>
                        <strong>Tapisan Status:</strong> {{ $status ?: 'Semua Status' }}<br>
                        <strong>Tempoh:</strong> 
                        @if(isset($viewMode) && $viewMode === 'daily')
                            {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }} (Harian)
                        @elseif(isset($viewMode) && $viewMode === 'monthly')
                            Tahun {{ $year }} (Bulanan)
                        @else
                            Semua Rekod
                        @endif
                    </td>
                    <td style="text-align: right; vertical-align: top;"><strong>Jumlah Hasil (Completed):</strong> RM {{ number_format($orders->where('status', 'Completed')->sum('total_price'), 2) }}</td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40px;">ID</th>
                    <th>Pelanggan</th>
                    <th>Pakej Tempahan</th>
                    <th class="text-right" style="width: 80px;">Harga (RM)</th>
                    <th style="width: 80px;">Tarikh Majlis</th>
                    <th style="width: 80px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>
                        <strong>{{ $order->user->full_name }}</strong><br>
                        {{ $order->user->email }}
                    </td>
                    <td>{{ $order->package_name }}</td>
                    <td class="text-right">{{ number_format($order->total_price, 2) }}</td>
                    <td>{{ $order->delivery_date }}</td>
                    <td>
                        <span class="badge">
                            {{ $order->status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #8C8275;">
                        Tiada rekod tempahan ditemui.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer-text">
            Laporan ini dijanakan secara automatik melalui sistem pentadbir SmartServe Catering.
        </div>
    </div>
</body>
</html>
