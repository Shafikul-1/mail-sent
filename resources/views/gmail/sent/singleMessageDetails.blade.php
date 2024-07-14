@extends('header')

@section('othersContent')
<div class="container">
    <h1>Sent Message Details</h1>
    <p>Date: {{ date('Y-m-d H:i:s', $messageDate / 1000) }}</p>
    <p>Subject: {{ $subject }}</p>

    @if (!empty($attachments))
        <h3>Attachments:</h3>
        <ul>
            @foreach ($attachments as $attachment)
                <li>
                    <a href="#">{{ $attachment['fileName'] }}</a>
                </li>
            @endforeach
        </ul>
    @else
        <p>No attachments found.</p>
    @endif

    <a href="{{route('sentMessageReply', $messageId)}}" class="font-bold text-xl text-blue-400">Message Reply</a>
    <h3>Message Body:</h3>
    <p>{!! $bodyData !!}</p>
</div>
@endsection
{{-- <p>{!! nl2br(e($bodyData)) !!}</p> --}}