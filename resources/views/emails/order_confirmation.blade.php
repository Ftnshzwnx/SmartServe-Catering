@extends('emails.layout')

@section('title', 'Order Confirmation')

@section('content')
    <h2 style="color: #4A6B5D; margin-top: 0;">Order Confirmation</h2>
    <p>Dear <strong>{{ $customerName }}</strong>,</p>
    <p>Thank you for choosing SmartServe Catering. We have received your order details and your deposit receipt (30%) has been uploaded. Our admin team will verify it shortly.</p>
    
    <table class="info-table">
        <tr>
            <td class="label">Order ID</td>
            <td class="value">#{{ $order->id }}</td>
        </tr>
        <tr>
            <td class="label">Package Name</td>
            <td class="value">{{ $order->package_name }}</td>
        </tr>
        <tr>
            <td class="label">Delivery Date</td>
            <td class="value">{{ $order->delivery_date }}</td>
        </tr>
        <tr>
            <td class="label">Delivery Time</td>
            <td class="value">{{ $order->delivery_time }}</td>
        </tr>
        <tr>
            <td class="label">Delivery Zone</td>
            <td class="value">
                @if($order->delivery_fee > 0)
                    {{ $order->delivery_zone }} (RM {{ number_format($order->delivery_fee, 2) }})
                @else
                    Self-Pickup (RM 0.00)
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Total Cost</td>
            <td class="value" style="font-weight: bold;">RM {{ number_format($order->total_price, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Deposit Paid (30%)</td>
            <td class="value" style="color: #4A6B5D; font-weight: bold;">RM {{ number_format($deposit, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Balance Remaining (70%)</td>
            <td class="value" style="color: #8C3A3A; font-weight: bold;">RM {{ number_format($balance, 2) }}</td>
        </tr>
    </table>
    
    <p>You can track your order status and manage your booking at any time via your customer portal dashboard.</p>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
    </div>
@endsection
