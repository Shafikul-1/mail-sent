@extends('header')

@section('othersContent')

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            @if (count($sendAllMail) <= 0)
                <p class="font-bold text-center dark:text-white text-3xl">Upcomming Sent Mail</p>
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
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sendAllMail as $allMail)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td scope="row" class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $allMail['id']}}
                    </td>
                    <th class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{ $allMail['sender_mail']}}
                    </th>
                    <td class="px-6 py-4">
                        {!! $allMail['reciver_mail'] !!}
                    </td>
                    <td class="px-6 py-4">
                        {!! $allMail['mail_status'] !!}
                    </td>
                    <td class="px-6 py-4">
                        {!! $allMail['user_id'] !!}
                    </td>
                    <td class="px-6 py-4">
                        {!! Str::words($allMail['msg'], 5, '...') !!}
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                        <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline ms-3">Remove</a>
                    </td>
                </tr>
            @endforeach
            @endif
        </tbody>
    </table>
</div>

@endsection