@extends('header')

@section('othersContent')

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            @if (count($sendAllMail) <= 0)
                <p class="font-bold text-center dark:text-white text-3xl">Wait to see the sent mail</p>
            @else
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="p-4">
                    ID
                </th>
                <th scope="col" class="px-6 py-3">
                    sender_mail
                </th>
                <th scope="col" class="px-6 py-3">
                    reciver_mail
                </th>
                <th scope="col" class="px-6 py-3">
                    mail_status
                </th>
                <th scope="col" class="px-6 py-3">
                    user_id
                </th>
                <th scope="col" class="px-6 py-3">
                    msg
                </th>
                <th scope="col" class="px-6 py-3">
                    Time
                </th>
                <th scope="col" class="px-6 py-3">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sendAllMail as $allMail)
                <tr class=" {{($allMail['mail_status'] == 0) ? 'bg-red-800 dark:bg-red-500 dark:hover:bg-red-400' : 'bg-white dark:bg-gray-800 dark:hover:bg-gray-600'}} border-b dark:border-gray-700 hover:bg-gray-50 ">
                    <td scope="row" class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $allMail['id']}}
                    </td>
                    <th class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $allMail['sender_mail']}}
                    </th>
                    <td class="px-6 py-4">
                        {{$allMail['reciver_mail']}}
                    </td>
                    <td class="px-6 py-4">
                        {{$allMail['mail_status']}}
                    </td>
                    <td class="px-6 py-4">
                        {{$allMail['user_id']}}
                    </td>
                    <td class="px-6 py-4">
                        {!! Str::words($allMail['msg'], 5, '...') !!}
                    </td>
                    <td class="px-6 py-4">
                        {{$allMail['created_at']}}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <form action="{{route("mail-message.destroy", $allMail['id'])}}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"><i class="fa-solid fa-trash text-white hover:text-red-600 p-5"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
            @endif
        </tbody>
    </table>
    {{ $sendAllMail->links() }}
</div>


@if (session('msg'))
    <div class="space-y-5">
        <div class="bg-teal-50 border-t-2 border-teal-500 rounded-lg p-4" role="alert">
        <div class="flex">
            <div class="flex-shrink-0">
            <!-- Icon -->
            <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-teal-100 bg-teal-200 text-teal-800">
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                <path d="m9 12 2 2 4-4"></path>
                </svg>
            </span>
            <!-- End Icon -->
            </div>
            <div class="ms-3">
            <h3 class="text-gray-800 font-semibold">
                Successfully 
            </h3>
            <p class="text-sm text-gray-700">
                {{session('msg')}}
            </p>
            </div>
        </div>
    </div>
@endif

@endsection