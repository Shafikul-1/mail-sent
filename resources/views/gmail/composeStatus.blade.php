@extends('header')
@section('othersContent')

<div class="relative overflow-x-auto shadow-md sm:rounded-lg">
    @if ($composeStatusData->isEmpty())
        <p class="font-bold text-center text-3xl">NO Data Found</p>
    @else
    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="p-4">
                    <div class="flex items-center">
                        <input id="checkbox-all-search" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <label for="checkbox-all-search" class="sr-only">checkbox</label>
                    </div>
                </th>
                <th scope="col" class="px-6 py-3">
                    Sender Email
                </th>
                <th scope="col" class="px-6 py-3">
                    User Email
                </th>
                <th scope="col" class="px-6 py-3">
                    Sending Time
                </th>
                <th scope="col" class="px-6 py-3">
                    Status
                </th>
                <th scope="col" class="px-6 py-3">
                    Subject
                </th>
                <th scope="col" class="px-6 py-3">
                    Email Content
                </th>
                <th scope="col" class="px-6 py-3">
                    Action
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($composeStatusData as $data)
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="w-4 p-4">
                        <div class="flex items-center">
                            <input id="checkbox-table-search-1" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="checkbox-table-search-1" class="sr-only">checkbox</label>
                        </div>
                    </td>
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                        {{$data['client_email']}}
                    </th>
                    <td class="px-6 py-4">
                        {{Auth::user()->email}}
                    </td>
                    <td class="px-6 py-4">
                        {{$data['sendingTime']}}
                    </td>
                    <td class="px-6 py-4">
                        @if ((($data['status'] == 1)))
                            Pending
                        @else
                            No Action
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="w-[300px] whitespace-nowrap overflow-hidden text-ellipsis">
                            {{$data['subject']}}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="w-[300px] whitespace-nowrap overflow-hidden text-ellipsis">
                            {{ $data['email_content'] }}
                        </div>
                    </td>
                    <td class="flex items-center px-6 py-4">
                        <a href="#" class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</a>
                        <a href="{{route('deleteComposeData', $data['id'])}}" class="font-medium text-red-600 dark:text-red-500 hover:underline ms-3">Remove</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    {{$composeStatusData->links()}}
    @endif
</div>

@if (session('msg'))
<div class="bg-red-50 border-s-4 border-red-500 p-4 fixed bottom-0 right-0 z-50" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <!-- Icon -->
            <span
                class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-[18px] shrink-0 fill-green-500 inline mr-3"
                    viewBox="0 0 512 512">
                    <ellipse cx="246" cy="246" data-original="#000" rx="246"
                        ry="246" />
                    <path class="fill-white"
                        d="m235.472 392.08-121.04-94.296 34.416-44.168 74.328 57.904 122.672-177.016 46.032 31.888z"
                        data-original="#000" />
                </svg>
            </span>
            <!-- End Icon -->
        </div>
        <div class="ms-3">
            <h3 class="text-gray-800 font-semibold">
                __!__
            </h3>
            <p class="text-sm text-gray-700">
                {{ session('msg') }}
            </p>
        </div>
    </div>
</div>
</div>
@endif

@endsection