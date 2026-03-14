<x-app-layout>
<x-slot name="header"></x-slot>

<div class="p-6 lg:p-8 overflow-y-auto h-full">
    <div class="max-w-[1400px] mx-auto">

    <!-- Page Header -->
    <div class="mb-8 animate-fade-in-up">
        <h1 class="text-2xl font-black text-gray-900 tracking-tight">Profile Settings</h1>
        <p class="text-gray-500 mt-1 font-medium">Update your personal information and security preferences.</p>
    </div>

    <div class="space-y-6 pb-12">
        <!-- Side-by-Side Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">

            <!-- Profile Information (Left Side) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <div class="w-full">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Update Password (Right Side) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
                <div class="w-full">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>

        <!-- Delete Account -->
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6 md:w-1/2">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    </div>
</div>

    @push('css')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <style>
            /* Tighten up the margin inside the included partials if Breeze added any */
            section header { margin-bottom: 1.5rem !important; }
            form { margin-top: 1rem !important; }
        </style>
    @endpush

</x-app-layout>
