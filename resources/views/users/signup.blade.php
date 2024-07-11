@extends('header')

@section('othersContent')
    <form method="POST" action="{{route('user.store')}}" class="max-w-sm mx-auto ">
        @csrf
            <div class="mb-5"> 
                <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter Your Name</label>
                <input value="{{old('name')}}" type="text" id="name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"  placeholder="Enter Your Name"/>
                @error('name') <p class="text-red-500">{{$message}}</p> @enderror
            </div>
            <div class="mb-5">
                <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter Your Email</label>
                <input value="{{old('email')}}" type="text" id="email" name="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Enter Your Email"/>
                @error('email') <p class="text-red-500">{{$message}}</p> @enderror
            </div>
            <div class="mb-5">
                <label for="phonenumber" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter Your Phone Number</label>
                <input value="{{old('phonenumber')}}" type="number" id="phonenumber" name="phonenumber" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Enter Your Phone Number"/>
                @error('phonenumber') <p class="text-red-500">{{$message}}</p> @enderror
            </div> 
            <div class="mb-5 "> 
                <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter Your Password</label>
                <input value="{{old('password')}}" type="text" id="password" name="password" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"  placeholder="Enter Your Password"/>
                @error('password') <p class="text-red-500">{{$message}}</p> @enderror
            </div>
            <div class="mb-5 "> 
                <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Enter Your Confirm Password</label>
                <input value="{{old('password_confirmation')}}" type="text" id="password" name="password_confirmation" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"  placeholder="Enter Your Confirm Password"/>
                @error('password_confirmation') <p class="text-red-500">{{$message}}</p> @enderror
            </div>
            @can('isAdmin')
            <div class="mb-5 "> 
                <label for="role" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select an Permition</label>
                <select name="role" id="role" class="capitalize bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                  <option selected value="visitor">visitor</option>
                  <option value="admin">admin</option>
                  <option value="editor">editor</option>
                </select>
            </div>
            @endcan
        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Submit</button>
    </form>

    @if (session('msg'))
        <div class="bg-red-50 border-s-4 border-red-500 p-4" role="alert">
          <div class="flex">
            <div class="flex-shrink-0">
              <!-- Icon -->
              <span class="inline-flex justify-center items-center size-8 rounded-full border-4 border-red-100 bg-red-200 text-red-800">
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                {{session('msg')}}
              </p>
            </div>
          </div>
        </div>
      </div>
    @endif

@endsection
