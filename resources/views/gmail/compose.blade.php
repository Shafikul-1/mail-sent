@extends('header')
<style>
    .ql-toolbar button:hover {
        background-color: #b6d7f8 !important;
        /* Background color on hover */
    }

    .ql-picker-label:hover {
        color: #b6d7f8 !important;
    }

    .ql-picker-label .ql-active:hover {
        color: #b6d7f8 !important;
    }

    .ql-picker-label .ql-active:active {
        color: #b6d7f8 !important;
    }

    .ql-toolbar .ql-picker-label.ql-active {
        color: #b6d7f8 !important;
    }

    /* .ql-editor {
            box-sizing: border-box;
            overflow: auto;
        }
        .ql-container {
            box-sizing: border-box;
        }
        .ql-custom-button {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin: 0 4px;
            padding: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ql-custom-button i {
            font-size: 16px;
        }
        .ql-custom-button:hover {
            background-color: #e0e0e0;
        }
        #editor {
            overflow: auto;
        } */
</style>
@section('othersContent')
    <form action="{{ route('composeSent') }}" method="post" enctype="multipart/form-data" id="composeSubmit"
        class="max-w-sm mx-auto">
        @csrf
        <div class="mb-5">
            <label for="to" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sent Other
                Mails</label>
            <input value="{{ old('to') }}" type="text" name="to" id="to"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="name@flowbite.com" />
            @error('to')
                <p class="text-red-800">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label for="subject" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your subject</label>
            <input type="text" value="{{ old('subject') }}" name="subject" id="subject"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Subject Here ..." />
            @error('subject')
                <p class="text-red-800">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="attachments">Upload
                file</label>
            <input
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                type="file" name="attachments[]" id="attachments" multiple>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="attachments">Upload AttachMent Files</div>
            @error('attachments')
                <p class="text-red-400">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            @error('message')
                <p class="text-red-400">{{ $message }}</p>
            @enderror
            {{-- <div value="{{old('message')}}" id="htmlContent" contenteditable="true" class="w-[10rem] h-[20rem] block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Leave a comment..."></div> --}}
            <input type="hidden" name="message" id="message">
        </div>
        <div class="mb-5">
            <label for="sendingTime" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sending Time
                Number Min</label>
            <input type="text" value="{{ old('sendingTime') }}" name="sendingTime" id="sendingTime"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Sending Time here ..." />
            @error('sendingTime')
                <p class="text-red-800">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
    </form>
    <div class="p-4">
        <div class="bg-gray-400 min-h-[8rem]" id="editor"></div>
    </div>

    <!-- Include the Quill library -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <!-- Include Quill Emoji JS -->
    <script src="https://cdn.jsdelivr.net/npm/quill-emoji/dist/quill-emoji.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-emoji/dist/quill-emoji-toolbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-emoji/dist/quill-emoji-textarea.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-emoji/dist/quill-emoji-shortname.js"></script>

    <script>
        const options = {
            // debug: 'info',
            placeholder: 'Compose an epic...',
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        ['emoji'],
                        [{
                            'font': []
                        }, {
                            'size': []
                        }],
                        [{
                            'header': [1, 2, 3, 4, 5, 6, false]
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{
                            'color': []
                        }, {
                            'background': []
                        }],
                        [{
                            'script': 'sub'
                        }, {
                            'script': 'super'
                        }],
                        ['blockquote', 'code-block'],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        [{
                            'indent': '-1'
                        }, {
                            'indent': '+1'
                        }],
                        [{
                            'direction': 'rtl'
                        }],
                        [{
                            'align': []
                        }],
                        ['link', 'image', ],
                        // ['link', 'image', 'video', 'formula'],
                        ['clean'],
                        // Placeholder for custom buttons
                        // ['margin-top-inc', 'margin-top-dec', 'margin-left-inc', 'margin-left-dec', 'padding-top-inc', 'padding-top-dec', 'padding-left-inc', 'padding-left-dec']
                    ],
                    // handlers: {
                    //     'margin-top-inc': function() { adjustMargin('top', 10); },
                    //     'margin-top-dec': function() { adjustMargin('top', -10); },
                    //     'margin-left-inc': function() { adjustMargin('left', 10); },
                    //     'margin-left-dec': function() { adjustMargin('left', -10); },
                    //     'padding-top-inc': function() { adjustPadding('top', 10); },
                    //     'padding-top-dec': function() { adjustPadding('top', -10); },
                    //     'padding-left-inc': function() { adjustPadding('left', 10); },
                    //     'padding-left-dec': function() { adjustPadding('left', -10); }
                    // }
                },
                'emoji-toolbar': true,
                'emoji-textarea': false,
                'emoji-shortname': true
            },

        };
        const quill = new Quill('#editor', options);
        // Function to adjust padding
        // function adjustPadding(position, increment) {
        //     const editor = document.querySelector('.ql-editor');
        //     const style = window.getComputedStyle(editor);
        //     let currentPadding = parseInt(style.getPropertyValue(`padding-${position}`)) || 0;
        //     editor.style.padding = `${currentPadding + increment}px`;
        // }

        // // Function to adjust margin
        // function adjustMargin(position, increment) {
        //     const editor = document.querySelector('.ql-editor');
        //     const style = window.getComputedStyle(editor);
        //     let currentMargin = parseInt(style.getPropertyValue(`margin-${position}`)) || 0;
        //     editor.style.margin = `${currentMargin + increment}px`;
        // }

        // // Add custom buttons to toolbar
        // function addCustomButtons() {
        //     const toolbar = document.querySelector('.ql-toolbar');
        //     const buttonConfigs = [
        //         { name: 'margin-top-inc', icon: 'fa-arrow-up', title: 'Margin Top +', handler: () => adjustMargin('top', 10) },
        //         { name: 'margin-top-dec', icon: 'fa-arrow-down', title: 'Margin Top -', handler: () => adjustMargin('top', -10) },
        //         { name: 'margin-left-inc', icon: 'fa-arrow-left', title: 'Margin Left +', handler: () => adjustMargin('left', 10) },
        //         { name: 'margin-left-dec', icon: 'fa-arrow-right', title: 'Margin Left -', handler: () => adjustMargin('left', -10) },
        //         { name: 'padding-top-inc', icon: 'fa-arrow-up', title: 'Padding Top +', handler: () => adjustPadding('top', 10) },
        //         { name: 'padding-top-dec', icon: 'fa-arrow-down', title: 'Padding Top -', handler: () => adjustPadding('top', -10) },
        //         { name: 'padding-left-inc', icon: 'fa-arrow-left', title: 'Padding Left +', handler: () => adjustPadding('left', 10) },
        //         { name: 'padding-left-dec', icon: 'fa-arrow-right', title: 'Padding Left -', handler: () => adjustPadding('left', -10) }
        //     ];

        //     buttonConfigs.forEach(config => {
        //         const button = document.createElement('button');
        //         button.className = 'ql-custom-button';
        //         button.setAttribute('title', config.title);
        //         button.setAttribute('data-name', config.name);
        //         button.innerHTML = `<i class="fa ${config.icon}"></i>`;
        //         button.addEventListener('click', config.handler);
        //         toolbar.appendChild(button);
        //     });
        // }

        // // Call the function to add custom buttons
        // addCustomButtons();

        document.getElementById('composeSubmit').addEventListener('submit', function() {
            // let htmlContent = quill.root.innerHTML;
            document.getElementById('message').value = quill.root.innerHTML;
            // document.getElementById('message').value = document.getElementById('htmlContent').innerHTML;

        });
    </script>
@endsection
