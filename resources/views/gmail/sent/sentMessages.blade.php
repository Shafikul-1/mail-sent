@extends('header')

@section('othersContent')
    <h2 class="text-3xl text-center font-bold py-3">My Sent Message</h2>
    <form action="{{ route('multiWork') }}" id="replyForm" method="post">
        @csrf
        {{-- Dail log box work --}}

        <div class="flex gap-5 my-6">
            {{-- Dailog Box reply --}}
            <div
                class="hidden replyFormShow absolute top-5 left-[35%] z-40 rounded-md shadow-lg shadow-blue-500/50 p-2 bg-blue-500 translate-y-[20rem] transition-all duration-1000 ease-in ">
                <div class="mx-auto max-w-screen-sm px-4 relative">
                    {{-- Dilog Box Open close  --}}
                    <button type="button" onclick="replyFunction()"
                        class="absolute top-0 right-1 cursor-pointer text-white bg-red-800 capitalize px-3 py-2 rounded-md ">
                        close</button>
                    <h1 class="mt-6 text-xl font-bold sm:mb-6 sm:text-3xl">Write your Reply</h1>
                    <div class="w-full space-y-3 text-gray-700">
                        <div class="">
                            {{-- reply text input box --}}
                            <div class="w-[20rem] overflow-auto h-[12rem] border-4 border-red-500 bg-gray-200 rounded">
                                <div id="replyContent" class=" min-h-[12rem] p-2" contenteditable="true"></div>
                                <input type="hidden" name="reply" id="reply">
                            </div>
                            {{-- Select Time When Work start --}}
                            <div class="mb-5">
                                <label for="send_times" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Sending Time</label>
                                <input value="{{ old('send_times') }}" type="datetime-local" name="send_times" id="send_times"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"/>
                                @error('send_times')
                                    <p class="text-red-800">{{ $message }}</p>
                                @enderror
                            </div>
                            {{-- set schedule time --}}
                            <div class="flex flex-col">
                                <label for="sendingTime" class="font-bold dark:text-white my-2">Insert Sending time Min only
                                    number
                                    ...</label>
                                <input value="{{ old('sendingTime') }}" type="number" name="sendingTime" id="sendingTime"
                                    class="rounded-md">
                                @error('sendingTime')
                                    <p class="text-red-800">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="float-right">
                            <input type="submit" name="action" value="reply"
                                class="capitalize relative inline-flex h-10 w-auto max-w-full cursor-pointer items-center justify-center overflow-hidden whitespace-pre rounded-md bg-blue-700 px-5 py-2 text-center text-sm font-medium normal-case text-white opacity-100 outline-none focus:ring" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dilog Box Open Close  --}}
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
            <button onclick="allPageShow()" class=" mr-[1.3rem] bg-green-600 px-5 py-2 rounded-md font-bold text-md"
                type="button">Page</button>
            <div
                class=" AllPage hidden z-50 absolute top-[-3rem] right-[7rem] bg-blue-500 shadow-lg shadow-blue-500/50 p-2 rounded-md ">
                <div class="flex flex-col w-[7rem] h-[13rem] overflow-x-auto">
                    @foreach ($pageTokens as $pageToken)
                        <a href="{{ route('sentAllMessage', $pageToken) }}"
                            class="{{ $paramId == $pageToken ? 'bg-cyan-500 shadow-lg shadow-cyan-500/50' : 'bg-indigo-500 shadow-lg shadow-indigo-500/50' }} font-bold text-md px-4 capitalize my-2 py-1 rounded-md">page
                            {{ $pageToken + 1 }} </a>
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
                                        class=" {{ $errors->has('messageId') ? 'border-red-600 dark:border-red-600' : 'border-gray-600 dark:border-gray-600' }} bg-gray-500 dark:bg-gray-500 w-4 h-4 text-blue-600 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:focus:ring-offset-gray-800 focus:ring-2">
                                    <label for="messageId" class="sr-only">checkbox</label>
                                </div>
                                {{-- @error('messageId') <p class="text-red-800">{{$message}}</p> @enderror --}}
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
                                    {{ $sent['messageContent'] }}
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

@error('messageId')
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
                __!__ Error __!__ 
            </h3>
            <p class="text-sm text-gray-700">
                {{ $message }}
            </p>
        </div>
    </div>
</div>
</div>
@enderror

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


    <script>
        document.getElementById('replyForm').addEventListener('submit', function() {
            document.getElementById('reply').value = document.getElementById('replyContent').innerHTML;
        });
    
        function allPageShow() {
            const AllPage = document.querySelector('.AllPage');
            AllPage.classList.toggle('hidden');
        }
        function replyFunction() {
          const replyFormShow = document.querySelector('.replyFormShow');
          replyFormShow.classList.remove('hidden');
          if (replyFormShow.classList.contains("translate-y-[20rem]")) {
              replyFormShow.classList.replace("translate-y-[20rem]", "translate-y-[0rem]");
          } else if (replyFormShow.classList.contains("translate-y-[0rem]")) {
              replyFormShow.classList.replace("translate-y-[0rem]", "translate-y-[20rem]");
              replyFormShow.classList.add('hidden');
          }
      }

    </script>
@endsection
