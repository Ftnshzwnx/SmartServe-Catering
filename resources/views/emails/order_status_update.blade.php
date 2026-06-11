@extends('emails.layout')

@section('title', $title)

@section('content')
    <h2 style="color: {{ $action === 'approve' ? '#4A6B5D' : '#8C3A3A' }}; margin-top: 0;">{{ $title }}</h2>
    <p>Dear Customer,</p>
    
    @if($action === 'approve')
        <div class="success-box">
            <p style="margin: 0; line-height: 1.6;">{!! $desc !!}</p>
        </div>
    @else
        <div class="danger-box">
            <p style="margin: 0; line-height: 1.6;">{!! $desc !!}</p>
        </div>
    @endif
    
    <p>If you have any questions, reply to this email or contact customer service.</p>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ url('/dashboard') }}" class="btn">Go to Dashboard</a>
    </div>
@endsection
