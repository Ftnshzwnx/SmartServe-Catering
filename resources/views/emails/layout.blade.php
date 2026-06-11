<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $subject ?? '')</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #FAF9F6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border: 1px solid #E6E1DA;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }
        .header {
            background-color: #4A6B5D;
            padding: 32px 24px;
            text-align: center;
            color: #ffffff;
        }
        .header-logo {
            margin-bottom: 12px;
            text-align: center;
        }
        .header-logo img {
            max-height: 60px;
            width: auto;
            display: inline-block;
        }
        .header-title {
            margin: 0;
            font-family: Georgia, serif;
            font-size: 24px;
            font-weight: normal;
            letter-spacing: 1px;
            color: #FAF7F2;
        }
        .content {
            padding: 40px 30px;
            color: #2D3330;
            line-height: 1.6;
            font-size: 15px;
        }
        .footer {
            background-color: #FAF7F2;
            padding: 32px 24px;
            text-align: center;
            font-size: 12px;
            color: #8C8275;
            border-top: 1px solid #E6E1DA;
        }
        .social-link {
            text-decoration: none;
            font-weight: bold;
            margin: 0 8px;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            background-color: #4A6B5D;
            color: #FAF7F2 !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            margin-top: 20px;
            text-align: center;
        }
        .btn:hover {
            background-color: #3b5549;
        }
        .info-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 25px 0;
            border-radius: 12px;
            border: 1px solid #E6E1DA;
            overflow: hidden;
        }
        .info-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #E6E1DA;
            font-size: 14px;
        }
        .info-table tr:last-child td {
            border-bottom: none;
        }
        .info-table td.label {
            font-weight: bold;
            color: #4A6B5D;
            width: 40%;
            background-color: #FAF7F2;
        }
        .info-table td.value {
            color: #2D3330;
        }
        .highlight-box {
            background-color: #FAF7F2;
            border-left: 4px solid #4A6B5D;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .danger-box {
            background-color: #FFF5F5;
            border-left: 4px solid #8C3A3A;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .success-box {
            background-color: #F4FAF7;
            border-left: 4px solid #4A6B5D;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php
                $businessName = \App\Models\Setting::getVal('business_name', 'SmartServe Catering');
                $businessAddress = \App\Models\Setting::getVal('business_address', 'SmartServe Catering Gong Badak, Kuala Terengganu, Terengganu');
                $contactPhone = \App\Models\Setting::getVal('contact_phone', '019-2094670');
                $contactEmail = \App\Models\Setting::getVal('contact_email', 'fshazwina223@gmail.com');
                $cleanPhone = preg_replace('/[^0-9]/', '', $contactPhone);
                if (strpos($cleanPhone, '60') !== 0) {
                    if (strpos($cleanPhone, '0') === 0) {
                        $cleanPhone = '60' . substr($cleanPhone, 1);
                    } else {
                        $cleanPhone = '60' . $cleanPhone;
                    }
                }
            @endphp
            @if(isset($message) && file_exists(public_path('img/logo.png')))
                <div class="header-logo">
                    <img src="{{ $message->embed(public_path('img/logo.png')) }}" alt="{{ $businessName }}">
                </div>
            @endif
            <h1 class="header-title">{{ $businessName }}</h1>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p style="margin: 0 0 10px 0; font-weight: bold; font-size: 13px; color: #4A6B5D;">{{ $businessName }}</p>
            <p style="margin: 0 0 15px 0; line-height: 1.5;">
                {{ $businessAddress }}
            </p>
            <p style="margin: 0 0 20px 0;">
                <strong>Phone:</strong> {{ $contactPhone }} | <strong>Email:</strong> {{ $contactEmail }}
            </p>
            <div style="margin: 20px 0 10px 0; border-top: 1px solid #E6E1DA; padding-top: 20px;">
                <a href="https://www.facebook.com/people/Azilina-Katering/100063705123584/" class="social-link" style="color: #1877F2;" target="_blank">Facebook</a>
                <a href="https://www.instagram.com/azilina_restaurant/" class="social-link" style="color: #ee2a7b;" target="_blank">Instagram</a>
                <a href="https://www.tiktok.com/@azilinamustapha" class="social-link" style="color: #000000;" target="_blank">TikTok</a>
                <a href="https://wa.me/{{ $cleanPhone }}" class="social-link" style="color: #25D366;" target="_blank">WhatsApp</a>
            </div>
            <p style="margin: 15px 0 0 0; font-size: 11px; color: #A0978C;">
                &copy; {{ date('Y') }} {{ $businessName }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
