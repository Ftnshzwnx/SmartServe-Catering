@extends('emails.layout')

@section('title', 'New Customer Inquiry')

@section('content')
    <h2 style="color: #4A6B5D; margin-top: 0;">New Customer Inquiry 📩</h2>
    <p>You have received a new catering inquiry from the SmartServe website contact form.</p>

    <table class="info-table">
        <tr>
            <td class="label">Name</td>
            <td class="value"><strong>{{ $data['name'] }}</strong></td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value"><a href="mailto:{{ $data['email'] }}" style="color: #4A6B5D;">{{ $data['email'] }}</a></td>
        </tr>
        <tr>
            <td class="label">Phone</td>
            <td class="value"><a href="tel:{{ $data['phone'] }}" style="color: #4A6B5D;">{{ $data['phone'] }}</a></td>
        </tr>
        <tr>
            <td class="label">Event Type</td>
            <td class="value">{{ $data['type'] }}</td>
        </tr>
        <tr>
            <td class="label">Event Date</td>
            <td class="value">{{ $data['date'] }}</td>
        </tr>
        <tr>
            <td class="label">No. of Guests</td>
            <td class="value"><strong>{{ $data['pax'] }} pax</strong></td>
        </tr>
    </table>

    <div class="highlight-box">
        <strong>Message from customer:</strong><br><br>
        {{ $data['message'] }}
    </div>

    <div style="text-align: center; margin-top: 30px;">
        <a href="mailto:{{ $data['email'] }}?subject=Re: Your Catering Inquiry — SmartServe Catering" class="btn">
            Reply to Customer
        </a>
    </div>
@endsection
