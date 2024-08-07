@extends('header')
@section('othersContent')
    <form method="POST" action="" class="max-w-sm mx-auto " enctype="multipart/form-data">
        @csrf
        <div class="mb-5">
            <label for="mail_all" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your All Email ~
                TO</label>
            <input value="{{ old('mail_all') }}" type="text" id="mail_all" name="mail_all"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Enter Your Send All Email" />
            @error('mail_all')
                <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label for="mail_subject" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mail
                Subject</label>
            <input value="{{ old('mail_subject') }}" type="text" id="mail_subject" name="mail_subject"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Enter Your Email Subject" />
            @error('mail_subject')
                <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label for="mail_body" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Mail Body</label>
            <input value="{{ old('mail_body') }}" type="text" id="mail_body" name="mail_body"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                placeholder="Enter Your Mail Body" />
            @error('mail_body')
                <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white" for="mail_files">Upload file</label>
            <input
                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"
                name="mail_files[]" id="mail_files" type="file" multiple>
            @error('mail_files')
                <p class="text-red-500">{{ $message }}</p>
            @enderror
        </div>
        <div class="mb-5">
            <input type="text" name="mail_previse_file" class="mail_previse_file">
            <button type="button" onclick="showImageList()"
                class="text-white bg-gradient-to-br from-green-400 to-blue-600 hover:bg-gradient-to-bl focus:ring-4 focus:outline-none focus:ring-green-200 dark:focus:ring-green-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center me-2 mb-2">Your
                Image</button>
        </div>
        <button type="submit"
            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
    </form>


    <div class="allImage absolute top-0 left-0 z-9 px-[3rem] hidden">
        <button type="button" onclick="showImageList()"
            class="absolute top-3 right-5 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Close</button>
        {{-- <div class="flex items-center justify-center py-4 md:py-8 flex-wrap">
            <button type="button"
                class="text-blue-700 hover:text-white border border-blue-600 bg-white hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 rounded-full text-base font-medium px-5 py-2.5 text-center me-3 mb-3 dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:hover:bg-blue-500 dark:bg-gray-900 dark:focus:ring-blue-800">All
                categories</button>
            <button type="button"
                class="text-gray-900 border border-white hover:border-gray-200 dark:border-gray-900 dark:bg-gray-900 dark:hover:border-gray-700 bg-white focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-full text-base font-medium px-5 py-2.5 text-center me-3 mb-3 dark:text-white dark:focus:ring-gray-800">Shoes</button>
            <button type="button"
                class="text-gray-900 border border-white hover:border-gray-200 dark:border-gray-900 dark:bg-gray-900 dark:hover:border-gray-700 bg-white focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-full text-base font-medium px-5 py-2.5 text-center me-3 mb-3 dark:text-white dark:focus:ring-gray-800">Bags</button>
            <button type="button"
                class="text-gray-900 border border-white hover:border-gray-200 dark:border-gray-900 dark:bg-gray-900 dark:hover:border-gray-700 bg-white focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-full text-base font-medium px-5 py-2.5 text-center me-3 mb-3 dark:text-white dark:focus:ring-gray-800">Electronics</button>
            <button type="button"
                class="text-gray-900 border border-white hover:border-gray-200 dark:border-gray-900 dark:bg-gray-900 dark:hover:border-gray-700 bg-white focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-full text-base font-medium px-5 py-2.5 text-center me-3 mb-3 dark:text-white dark:focus:ring-gray-800">Gaming</button>
        </div> --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 bg-gray-300 shadow-lg shadow-blue-500/50 p-9 rounded-xl">
            @for ($i = 0; $i <= 10; $i++)
                <div class="selectImage cursor-pointer">
                    <img onclick="selectImage(event)" class="h-auto max-w-full rounded-lg" name="imag{{ $i }}"
                        src="https://flowbite.s3.amazonaws.com/docs/gallery/square/image.jpg" alt="">
                </div>
            @endfor
        </div>
        <button type="button" onclick="showImageList()"
            class="mb-[3rem] text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Added</button>
    </div>
    {{-- --}}

    <script>
        function showImageList() {
            const allImage = document.querySelector('.allImage');
            allImage.classList.toggle('hidden')
        }

        function selectImage(event) {
        const mail_previse_file = document.querySelector('.mail_previse_file');
        const imageName = event.target.name;
        const selectImage = event.currentTarget;

        // Check if the image is already selected
        const isSelected = selectImage.classList.contains('p-1') &&
                           selectImage.classList.contains('rounded') &&
                           selectImage.classList.contains('border-4') &&
                           selectImage.classList.contains('border-sky-500');

        if (isSelected) {
            // If the image is selected, remove its name from the input value
            const selectedImages = mail_previse_file.value.split(',').filter(name => name !== imageName);
            mail_previse_file.value = selectedImages.join(',');
            selectImage.classList.remove('p-1', 'rounded', 'border-4', 'border-sky-500');
        } else {
            // If the image is not selected, add its name to the input value
            const selectedImages = mail_previse_file.value ? mail_previse_file.value.split(',') : [];
            selectedImages.push(imageName);
            mail_previse_file.value = selectedImages.join(',');
            selectImage.classList.add('p-1', 'rounded', 'border-4', 'border-sky-500');
        }

        console.log(mail_previse_file.value);
    }
    </script>
@endsection
