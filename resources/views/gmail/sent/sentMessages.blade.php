@extends('header')

@section('othersContent')
    <h2 class="text-3xl text-center font-bold py-3">My Sent Message</h2>
    <form action="{{route('multiWork')}}" method="post">
        @csrf
        <div class="flex gap-5 my-6">
            <input type="submit" name="action" id="reply" value="reply" class="font-bold text-xl border rounded-md shadow-lg shadow-blue-500/50 capitalize bg-blue-500 ml-3 px-4 cursor-pointer py-1">
            <input type="submit" name="action" id="delete" value="delete" class="font-bold text-xl border rounded-md shadow-lg shadow-blue-500/50 capitalize bg-blue-500 ml-3 px-4 cursor-pointer py-1">
            <input type="submit" name="action" id="archive" value="archive" class="font-bold text-xl border rounded-md shadow-lg shadow-blue-500/50 capitalize bg-blue-500 ml-3 px-4 cursor-pointer py-1">
        </div>
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
                    @foreach ($filterAllData as $sent)
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
                                Other
                            </th>
                            <td class="px-6 py-4">
                                {{ $sent['subject'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['reciverEmail'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['total_message'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $sent['sentDate'] }}
                            </td>
                            <td class="px-6 py-4">
                                <div class=""
                                    style=" width: 300px; white-space: nowrap;overflow: hidden; text-overflow: ellipsis;">
                                    {!! $sent['messageContent'] !!}
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
@endsection
