<section>
<header>
<h2 class="text-xl font-bold text-gray-900">
Update Password
</h2>
<p class="mt-1 text-sm text-gray-500 font-medium">
Ensure your account is using a long, random password to stay secure.
</p>
</header>

<form method="post" action="{{ route('password.update') }}" class="mt-8 space-y-6">
    @csrf
    @method('put')

    <!-- Current Password -->
    <div>
        <x-input-label for="current_password" value="Current Password" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" autocomplete="current-password" />
        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
    </div>

    <!-- New Password -->
    <div>
        <x-input-label for="password" value="New Password" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
    </div>

    <!-- Confirm Password -->
    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" class="font-bold text-gray-700 mb-1" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full border-gray-200 focus:border-blue-500 focus:ring-blue-500 rounded-xl" autocomplete="new-password" />
        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
    </div>

    <!-- Action Button -->
    <div class="flex items-center gap-4 pt-2">
        <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-blue-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 transition shadow-sm shadow-blue-200">
            Update Password
        </button>

        @if (session('status') === 'password-updated')
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)" class="flex items-center text-sm text-green-600 font-bold">
                <i class="fas fa-check-circle mr-2"></i>
                Password updated successfully.
            </div>
        @endif
    </div>
</form>


</section>