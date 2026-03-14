<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration | Multi-Role</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .role-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }
        .role-radio:checked + .role-card {
            border-color: #4f46e5;
            background-color: #f5f3ff;
            box-shadow: 0 0 0 2px #4f46e5;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in-up { animation: fadeInUp 0.5s ease-out both; }
        .stagger-children > * { animation: fadeInUp 0.4s ease-out both; }
        .stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
        .stagger-children > *:nth-child(2) { animation-delay: 0.10s; }
        .stagger-children > *:nth-child(3) { animation-delay: 0.15s; }
        .stagger-children > *:nth-child(4) { animation-delay: 0.20s; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
        <div class="bg-indigo-600 p-8 text-white text-center">
            <h1 class="text-3xl font-bold">Create Your Account</h1>
            <p class="mt-2 opacity-80">Join our marketplace as a shopper or a business partner</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="p-8 space-y-6" onsubmit="return validateForm()">
            @csrf
            
            <!-- Role Selection -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold text-gray-700">Select Account Type</label>
                <!-- Changed to grid-cols-2 since we removed one role -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 stagger-children">
                    <label class="relative block">
                        <input type="radio" name="role" value="1" class="role-radio hidden" {{ old('role', '1') == '1' ? 'checked' : '' }} onclick="updateFormFields('1')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200">
                            <div class="text-3xl mb-2">🛍️</div>
                            <div class="font-bold text-gray-800">Customer</div>
                            <div class="text-xs text-gray-500 italic">For shoppers</div>
                        </div>
                    </label>

                    <label class="relative block">
                        <input type="radio" name="role" value="2" class="role-radio hidden" {{ old('role') == '2' ? 'checked' : '' }} onclick="updateFormFields('2')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200">
                            <div class="text-3xl mb-2">🚚</div>
                            <div class="font-bold text-gray-800">Food Truck Admin</div>
                            <div class="text-xs text-gray-500 italic">Own a food truck</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Basic Info Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="full_name" value="{{ old('full_name') }}" 
                           class="w-full px-4 py-2 border {{ $errors->has('full_name') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('full_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <span class="error-message text-red-500 text-xs" id="error-full_name"></span>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" 
                           class="w-full px-4 py-2 border {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <span class="error-message text-red-500 text-xs" id="error-email"></span>
                </div>
            </div>

            <!-- Role-Specific Field: Vendor Admin (Role 2) -->
            <div id="vendor-admin-fields" class="hidden space-y-4 pt-4 border-t border-gray-100">
                <h3 class="font-semibold text-indigo-700">Food Truck Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Food Truck Name <span class="text-red-500">*</span></label>
                        <input type="text" name="foodtruck_name" value="{{ old('foodtruck_name') }}" 
                               class="w-full px-4 py-2 border {{ $errors->has('foodtruck_name') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        @error('foodtruck_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <span class="error-message text-red-500 text-xs" id="error-foodtruck_name"></span>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Business License No. <span class="text-red-500">*</span></label>
                        <input type="text" name="business_license_no" value="{{ old('business_license_no') }}" 
                               class="w-full px-4 py-2 border {{ $errors->has('business_license_no') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                        @error('business_license_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <span class="error-message text-red-500 text-xs" id="error-business_license_no"></span>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Food Truck Description</label>
                    <textarea name="foodtruck_desc" rows="3" placeholder="Tell us about your cuisine..." 
                              class="w-full px-4 py-2 border {{ $errors->has('foodtruck_desc') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('foodtruck_desc') }}</textarea>
                    @error('foodtruck_desc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <!-- Common Fields -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone_no" value="{{ old('phone_no') }}" 
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="e.g. 0123456789" 
                       class="w-full px-4 py-2 border {{ $errors->has('phone_no') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                @error('phone_no') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <span class="error-message text-red-500 text-xs" id="error-phone_no"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" 
                           class="w-full px-4 py-2 border {{ $errors->has('password') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <span class="error-message text-red-500 text-xs" id="error-password"></span>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <span class="error-message text-red-500 text-xs" id="error-password_confirmation"></span>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition duration-300 shadow-lg mt-4">
                Register Account
            </button>

            <!-- Login Link -->
            <p class="text-center text-sm text-gray-500 mt-6">
                Already have an account?
                <a href="{{ route('login') }}" class="text-indigo-600 font-bold hover:underline">Login here</a>
            </p>

        </form>
    </div>

    <script>
        function updateFormFields(role) {
            const adminFields = document.getElementById('vendor-admin-fields');
            const foodtruckInputs = document.querySelectorAll('#vendor-admin-fields input, #vendor-admin-fields textarea');
            
            if (adminFields) {
                if (role === '2') {
                    adminFields.classList.remove('hidden');
                    // Set required attribute for food truck fields
                    foodtruckInputs.forEach(input => {
                        if (input.name === 'foodtruck_name' || input.name === 'business_license_no') {
                            input.setAttribute('required', 'required');
                        }
                    });
                } else {
                    adminFields.classList.add('hidden');
                    // Remove required attribute when hidden
                    foodtruckInputs.forEach(input => {
                        input.removeAttribute('required');
                    });
                }
            }
        }

        // Initialize on page load - properly handles validation errors
        window.addEventListener('DOMContentLoaded', function() {
            const selectedRole = document.querySelector('input[name="role"]:checked');
            if (selectedRole) {
                updateFormFields(selectedRole.value);
            } else {
                // Default to Customer (role 1) if nothing is selected
                updateFormFields('1');
            }
        });

        function validateForm() {
            let isValid = true;
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

            // Check full_name
            if (!document.querySelector('[name="full_name"]').value.trim()) {
                document.getElementById('error-full_name').textContent = 'Please insert your full name';
                isValid = false;
            }

            // Check email
            if (!document.querySelector('[name="email"]').value.trim()) {
                document.getElementById('error-email').textContent = 'Please insert your email address';
                isValid = false;
            }

            // Check phone_no
            if (!document.querySelector('[name="phone_no"]').value.trim()) {
                document.getElementById('error-phone_no').textContent = 'Please insert your phone number';
                isValid = false;
            }

            // Check password
            if (!document.querySelector('[name="password"]').value.trim()) {
                document.getElementById('error-password').textContent = 'Please insert your password';
                isValid = false;
            }

            // Check password_confirmation
            if (!document.querySelector('[name="password_confirmation"]').value.trim()) {
                document.getElementById('error-password_confirmation').textContent = 'Please confirm your password';
                isValid = false;
            }

            // Check role-specific fields
            const role = document.querySelector('input[name="role"]:checked').value;
            if (role == '2') {
                if (!document.querySelector('[name="foodtruck_name"]').value.trim()) {
                    document.getElementById('error-foodtruck_name').textContent = 'Please insert food truck name';
                    isValid = false;
                }
                if (!document.querySelector('[name="business_license_no"]').value.trim()) {
                    document.getElementById('error-business_license_no').textContent = 'Please insert business license number';
                    isValid = false;
                }
            }

            return isValid;
        }

        const password = document.getElementById("password");
        const confirm_password = document.getElementById("password_confirmation");

        function validatePassword(){
            if(password.value != confirm_password.value) {
                confirm_password.setCustomValidity("Passwords Don't Match");
            } else {
                confirm_password.setCustomValidity('');
            }
        }
        password.onchange = validatePassword;
        confirm_password.onkeyup = validatePassword;
    </script>
</body>
</html>