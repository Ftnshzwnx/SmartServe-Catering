@extends('emails.layout')

@section('title', 'Order Cancellation Notice')

@section('content')
    <h2 style="color: #8C3A3A; margin-top: 0;">Order Cancellation Notice</h2>
    <p>Dear <strong>{{ $customerName }}</strong>,</p>
    <p>This email is to confirm that your order <strong>#{{ $order->id }}</strong> has been cancelled per your request.</p>
    
    <div class="danger-box">
        <h4 style="margin-top: 0; color: #8C3A3A;">Deposit Policy:</h4>
        <p style="margin-bottom: 0;">According to our terms, the <strong>30% deposit payment is non-refundable</strong> for customer cancellations.</p>
    </div>
    
    <p>If you did not request this cancellation or believe this is an error, please contact our support team immediately.</p>
@endsection
