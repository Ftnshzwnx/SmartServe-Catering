@extends('emails.layout')

@section('title', $mailSubject ?? 'Catering Notification')

@section('content')
    <h2 style="color: #4A6B5D; margin-top: 0;">{{ $greeting }}</h2>
    
    @foreach ($lines as $line)
        <p>{{ $line }}</p>
    @endforeach

    @if ($actionText && $actionUrl)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $actionUrl }}" class="btn">{{ $actionText }}</a>
        </div>
    @endif
@endsection
