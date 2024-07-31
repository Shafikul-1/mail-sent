@extends('header')
@section('othersContent')
<form action="{{ route('composeSent') }}" method="post">
    @csrf
    <label for="to">To:</label>
    <input type="email" name="to" id="to" required><br><br>
    <label for="subject">Subject:</label>
    <input type="text" name="subject" id="subject" required><br><br>
    <label for="message">Message:</label><br>
    <textarea name="message" id="message" rows="10" required></textarea><br><br>
    <button type="submit">Send</button>
</form>
@endsection