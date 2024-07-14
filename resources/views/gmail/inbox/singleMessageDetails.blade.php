@extends('header')
@section('othersContent')
    <div class="dark:text-white py-5 px-3">
        <p>historyId Id => {{ $messageData->historyId }}</p>
        <p> id => {{ $messageData->id }}</p>
        <p> internalDate => {{ $messageData->internalDate }}</p>

        <p class="text-center text-xl dark:text-white">All Label</p>
        <div class="flex gap-3 border-2 p-3 my-3">
            @foreach ($messageData->labelIds as $labelId)
                <pre>{{ $labelId }}</pre>,
            @endforeach
        </div>

        <p> sizeEstimate => {{ $messageData->sizeEstimate }}</p>
        <p> snippet => {{ $messageData->snippet }}</p>
        <p> threadId => {{ $messageData->threadId }}</p>

        <p class="text-center text-xl dark:text-white">PayLoad </p>
        <div class=" border-2 p-3 my-3">
            <p> filename => {{ $messageData->payload->filename }}</p>
            <p> mimeType => {{ $messageData->payload->mimeType }}</p>
            <p> partId => {{ $messageData->payload->partId }}</p>

            <p class="text-center text-xl dark:text-white">PayLoad -> body -> headers</p>
            <div class=" border-2 p-3 my-3">
                @foreach ($messageData->payload->headers as $headerData)
                    <p> name => {{ $headerData->name }}</p>
                    <p> size => {{ html_entity_decode($headerData->value) }}</p>
                    {{-- <p> value not work, => {{ json_encode(json_decode($headerData->value, true), JSON_PRETTY_PRINT) }}</p> --}}
                @endforeach
            </div>

            <p class="text-center text-xl dark:text-white">PayLoad -> body</p>
            <div class=" border-2 p-3 my-3">
                <p> size => {{ $messageData->payload->body->size }}</p>
            </div>

            <p class="text-center text-xl dark:text-white">PayLoad -> body -> parts</p>
            <div class=" border-2 p-3 my-3">
                @foreach ($messageData->payload->parts as $part)
                    <div class=" border-2 p-3 my-3">
                        <p> filename => {{ $part->filename }}</p>
                        <p> mimeType => {{ $part->mimeType }}</p>
                        <p> partId => {{ $part->partId }}</p>

                        <div class=" border-2 p-3 my-3">
                            <p class="text-center text-xl dark:text-white">PayLoad -> body -> parts -> headers</p>
                            @foreach ($part->headers as $partHeader)
                                <p> name => {{ $partHeader->name }}</p>
                                <p> value => {{ $partHeader->value }}</p>
                            @endforeach
                        </div>

                        <div class=" border-2 p-3 my-3">
                            <p class="text-center text-xl dark:text-white my-3">PayLoad -> body -> parts -> body</p>
                            {{-- <p> data => {{ $part->body->data }}</p> --}}
                            <p> size => {{ $part->body->size }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endsection
