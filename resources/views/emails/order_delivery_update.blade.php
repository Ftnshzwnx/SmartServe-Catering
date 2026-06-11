@extends('emails.layout')

@section('title', 'Status Penghantaran / Delivery Update')

@section('content')
    <h2 style="color: #4A6B5D; margin-top: 0;">Pesanan Anda Sedang Dihantar! 🚚</h2>
    <p>Pelanggan yang dihormati <strong>{{ $order->user->full_name }}</strong>,</p>
    <p>Kami ingin memaklumkan bahawa tempahan katering anda untuk majlis pada <strong>{{ $order->delivery_date }}</strong> kini sedang <strong>dalam proses penghantaran / pemasangan</strong> ke lokasi anda:</p>
    
    <div class="highlight-box">
        <strong>Alamat Acara / Event Address:</strong><br>
        {!! nl2br(e($order->delivery_address)) !!}
    </div>

    <p>Pasukan kami sedang dalam perjalanan untuk memastikan semuanya berjalan dengan lancar untuk majlis anda.</p>
    
    <hr style="border: 0; border-top: 1px solid #E6E1DA; margin: 25px 0;" />
    
    <h3 style="color: #8C3A3A; margin-top: 0;">Peringatan Pembayaran Baki 70% 💳</h3>
    <p>Memandangkan katering telah dihantar, anda kini perlu menjelaskan baki pembayaran sebanyak 70% berjumlah:</p>
    
    <div style="background-color: #FFF5F5; border: 1px solid #FADCDD; padding: 20px; text-align: center; border-radius: 12px; margin: 20px 0;">
        <span style="font-size: 12px; color: #8C8275; display: block; text-transform: uppercase; font-weight: bold; letter-spacing: 0.5px;">Jumlah Baki Perlu Dibayar</span>
        <strong style="font-size: 24px; color: #8C3A3A; display: block; margin-top: 5px;">RM {{ number_format($balance, 2) }}</strong>
    </div>

    <p>Sila buat bayaran dan <strong>muat naik resit baki bayaran</strong> anda melalui portal pelanggan untuk pengesahan akhir pemilik.</p>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/dashboard') }}" class="btn">Muat Naik Resit Sekarang</a>
    </div>
@endsection
