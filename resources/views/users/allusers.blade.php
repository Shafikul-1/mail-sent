@extends('header')

@section('othersContent')


<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="p-4">
                    ID
                </th>
                <th scope="col" class="px-6 py-3">
                    Product name
                </th>
                <th scope="col" class="px-6 py-3">
                    Color
                </th>
                <th scope="col" class="px-6 py-3">
                    Category
                </th>
                <th scope="col" class="px-6 py-3">
                    Accessories
                </th>
                <th scope="col" class="px-6 py-3">
                    Available
                </th>
                <th scope="col" class="px-6 py-3">
                    Price
                </th>
                <th scope="col" class="px-6 py-3">
                    Weight
                </th>
                @if (Auth::check())
                    <th scope="col" class="px-6 py-3">
                        Action
                    </th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <td scope="row" class="px-6 py-4 font-bold text-gray-900 whitespace-nowrap dark:text-white">
                    {{ $user['id']}}
                </td>
                <th class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                    {{ $user['name']}}
                </th>
                <td class="px-6 py-4">
                    {{ $user['phonenumber']}}
                </td>
                <td class="px-6 py-4">
                    {{ $user['email']}}
                </td>
                <td class="px-6 py-4">
                    Yes
                </td>
                <td class="px-6 py-4">
                    Yes
                </td>
                <td class="px-6 py-4">
                    $2999
                </td>
                <td class="px-6 py-4">
                    3.0 lb.
                </td>
                @if (Auth::check())
                    <td class="flex items-center px-6 py-4">
                        <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                        <a href="#" class="font-medium text-red-600 dark:text-red-500 hover:underline ms-3">Remove</a>
                    </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
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