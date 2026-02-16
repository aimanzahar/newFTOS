<section>
<header>
<h2 class="text-xl font-bold text-gray-900">
Profile Information
</h2>

    <p class="mt-1 text-sm text-gray-500 font-medium">
        Update your account's identity details and contact information.
    </p>
</header>

{{-- Form for sending email verification --}}
<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="mt-8 space-y-6">
    @csrf
    @method('patch')

    <!-- Full Name -->
    <div>
        <x-input-label for="full_name" value="Full Name" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" :value="old('full_name', $user->full_name)" required autofocus autocomplete="name" />
        <x-input-error class="mt-2" :messages="$errors->get('full_name')" />
    </div>

    <!-- Email Address -->
    <div>
        <x-input-label for="email" value="Email Address" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" :value="old('email', $user->email)" required autocomplete="username" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" />

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-3 p-3 bg-amber-50 rounded-lg border border-amber-100">
                <p class="text-sm text-amber-800">
                    Your email address is unverified.
                    <button form="send-verification" class="ml-1 underline font-bold text-amber-900 hover:text-amber-700 transition">
                        Click here to re-send verification.
                    </button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-bold text-sm text-green-600">
                        A new verification link has been sent.
                    </p>
                @endif
            </div>
        @endif
    </div>

    <!-- Phone Number -->
    <div>
        <x-input-label for="phone_no" value="Phone Number" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="phone_no" name="phone_no" type="text" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" :value="old('phone_no', $user->phone_no)" placeholder="e.g. +60123456789" autocomplete="tel" />
        <x-input-error class="mt-2" :messages="$errors->get('phone_no')" />
    </div>

    <!-- Save Button -->
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-blue-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 transition shadow-sm shadow-blue-200">
            Save Changes
        </button>

        @if (session('status') === 'profile-updated')
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="flex items-center text-sm text-green-600 font-bold">
                <i class="fas fa-check-circle mr-2"></i>
                Settings saved successfully.
            </div>
        @endif
    </div>
</form>


</section>