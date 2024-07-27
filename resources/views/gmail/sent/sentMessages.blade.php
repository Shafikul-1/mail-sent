@extends('header')

<script>
    function allPageShow(){
        const AllPage = document.querySelector('.AllPage');
        AllPage.classList.toggle('hidden');
    }
</script>

@section('othersContent')
    <h2 class="text-3xl text-center font-bold py-3">My Sent Message</h2>
    <form action="{{ route('multiWork') }}" method="post">
        @csrf
        <div class="flex gap-5 my-6">

            {{-- Dailog Box reply --}}
            <div
                class="hidden replyFormShow absolute top-5 left-[35%] z-40 rounded-md shadow-lg shadow-blue-500/50 p-2 bg-blue-500 translate-y-[20rem] transition-all duration-1000 ease-in ">
                <div class="mx-auto max-w-screen-sm px-4 relative">
                    {{-- Dilog Box close  --}}
                    <p onclick="replyFunction()"
                        class="absolute top-0 right-1 cursor-pointer text-white bg-red-800 capitalize px-3 py-2 rounded-md ">
                        close</p>
                    <h1 class="mt-6 text-xl font-bold sm:mb-6 sm:text-3xl">Write your Reply</h1>
                    <div class="w-full space-y-3 text-gray-700">
                        <div class="">
                            <textarea name="reply" id="" placeholder="Write your comment here" cols="50" rows="6"
                                class="h-40 w-full min-w-full max-w-full overflow-auto whitespace-pre-wrap rounded-md border bg-white p-5 text-sm font-normal normal-case text-gray-600 opacity-100 outline-none focus:text-gray-600 focus:opacity-100 focus:ring"></textarea>
                            <div class="flex flex-col">
                                <label for="sendingTime" class="font-bold dark:text-white my-2">Insert Sending time Min
                                    ...</label>
                                <input type="number" name="sendingTime" id="sendingTime" class="rounded-md">
                            </div>
                        </div>
                        <div class="float-right">
                            <input type="submit" name="action" id="reply" value="reply"
                                class="capitalize relative inline-flex h-10 w-auto max-w-full cursor-pointer items-center justify-center overflow-hidden whitespace-pre rounded-md bg-blue-700 px-5 py-2 text-center text-sm font-medium normal-case text-white opacity-100 outline-none focus:ring" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dilog Box Opem  --}}
            <button onclick="replyFunction()" type="button"
                class="font-bold text-xl border rounded-md shadow-lg shadow-indigo-500/50 capitalize bg-indigo-500 ml-3 px-4 cursor-pointer py-1">reply</button>
            <input type="submit" name="action" id="delete" value="delete"
                class="font-bold text-xl border rounded-md shadow-lg shadow-blue-500/50 capitalize bg-blue-500 ml-3 px-4 cursor-pointer py-1">
            <input type="submit" name="action" id="archive" value="archive"
                class="font-bold text-xl border rounded-md shadow-lg shadow-blue-500/50 capitalize bg-blue-500 ml-3 px-4 cursor-pointer py-1">
        </div>

        {{-- All Page Show --}}
        @php
            $paramId = request()->route('pageId');
        @endphp
        <div class="text-end relative">
            <button onclick="allPageShow()" class=" mr-[1.3rem] bg-green-600 px-5 py-2 rounded-md font-bold text-md" type="button">Page</button>
            <div class=" AllPage hidden z-50 absolute top-[-3rem] right-[7rem] bg-blue-500 shadow-lg shadow-blue-500/50 p-2 rounded-md ">
                <div class="flex flex-col w-[7rem] h-[13rem] overflow-x-auto">
                    <a href="{{ route('sentAllMessage') }}"
                        class="{{($paramId == '') ? 'bg-cyan-500 shadow-lg shadow-cyan-500/50' : 'bg-indigo-500 shadow-lg shadow-indigo-500/50'}} font-bold text-md px-4 capitalize my-2 py-1 rounded-md">Page 0</a>
                    @foreach ($pageTokens as $pageNumber => $pageToken)
                        <a href="{{ route('sentAllMessage', $pageToken) }}"
                            class="{{($paramId == $pageToken) ? 'bg-cyan-500 shadow-lg shadow-cyan-500/50' : 'bg-indigo-500 shadow-lg shadow-indigo-500/50'}} font-bold text-md px-4 capitalize my-2 py-1 rounded-md">page
                            {{ $pageNumber + 1 }} </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="p-4">
                            <div class="flex items-center">
                                <input id="allSelected" type="checkbox"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <label for="allSelected" class="sr-only">checkbox</label>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Other
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Subject
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Total Message
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Sent Date
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Message Content
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {{-- <p>threadId ID => {{ $sent['threadId'] }}</p>  --}}
                    @foreach ($filterAllData as $key => $sent)
                        <tr
                            class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="w-4 p-4">
                                <div class="flex items-center">
                                    <input name="messageId[]" value="{{ $sent['id'] }}" id="messageId" type="checkbox"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                    <label for="messageId" class="sr-only">checkbox</label>
                                </div>
                            </td>
                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $key + 1 }}
                            </th>
                            <td class="px-6 py-4">
                                {{ $sent['subject'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['reciverEmail'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['totalMessage'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['sentDate'] }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="w-[300px] whitespace-nowrap overflow-hidden text-ellipsis">
                                    {{$sent['messageContent']}}
                                </div>
                            </td>
                            <td class="flex items-center px-6 py-4">
                                <a href="{{ route('singleSentMessage', $sent['id']) }}"
                                    class="font-medium text-blue-600 dark:text-blue-500 hover:underline">View</a>
                                <a href="#"
                                    class="font-medium text-red-600 dark:text-red-500 hover:underline ms-3">Remove</a>
                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </form>


    @if (session('msg'))
        <div class="bg-red-50 border-s-4 border-red-500 p-4" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <!-- Icon -->
                    <span
                        class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800">
                        <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24"
                            height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </span>
                    <!-- End Icon -->
                </div>
                <div class="ms-3">
                    <h3 class="text-gray-800 font-semibold">
                        Error!
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
