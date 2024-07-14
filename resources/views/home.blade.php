@extends('header')
@section('othersContent')
    <h1 class="font-bold text-4xl text-center">Home page</h1>


    @if(session('msg'))
    <p class="text-center font-bold text-red-800"> {{session('msg')}} </p>
    @endif
    
    <pre>
    @php
        $checking = Session()->all();
        print_r($checking);
    @endphp
    </pre>
@endsection