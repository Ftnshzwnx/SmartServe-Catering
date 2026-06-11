@extends('emails.layout')

@section('title', 'Custom Menu Proposal Ready')

@section('content')
    <h2 style="color: #4A6B5D; margin-top: 0;">Custom Menu Proposal Ready!</h2>
    <p>Dear <strong>{{ $order->user->name }}</strong>,</p>
    <p>We have built a custom menu proposal for your event. Our team has carefully curated a menu selection based on your requirements.</p>
    
    <table class="info-table">
        <tr>
            <td class="label">Event Date</td>
            <td class="value">{{ $order->delivery_date }}</td>
        </tr>
        <tr>
            <td class="label">Proposed Cost</td>
            <td class="value" style="font-weight: bold; color: #4A6B5D;">RM {{ number_format($order->total_price, 2) }}</td>
        </tr>
    </table>
    
    @if($order->admin_note)
        <div class="highlight-box">
            <strong>Note from Catering Manager:</strong><br>
            {{ $order->admin_note }}
        </div>
    @endif

    <p>Please log in to your dashboard to review the proposed dishes, make any adjustments, and approve the proposal to lock in your date.</p>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/orders/' . $order->id) }}" class="btn">View & Approve Proposal</a>
    </div>
@endsection
