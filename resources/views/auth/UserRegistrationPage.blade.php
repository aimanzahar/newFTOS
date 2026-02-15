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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-indigo-600 p-8 text-white text-center">
            <h1 class="text-3xl font-bold">Create Your Account</h1>
            <p class="mt-2 opacity-80">Join our marketplace as a shopper or a business partner</p>
        </div>

        <!-- Display Validation Errors -->
        @if ($errors->any())
            <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 mx-8 mt-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <!-- Role Selection -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold text-gray-700">Select Account Type</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Customer (Role 1) -->
                    <label class="relative block">
                        <input type="radio" name="role" value="1" class="role-radio hidden" {{ old('role', '1') == '1' ? 'checked' : '' }} onclick="updateFormFields('1')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200">
                            <div class="text-3xl mb-2">🛍️</div>
                            <div class="font-bold text-gray-800">Customer</div>
                            <div class="text-xs text-gray-500 italic">For shoppers</div>
                        </div>
                    </label>

                    <!-- Vendor Staff (Role 3) -->
                    <label class="relative block">
                        <input type="radio" name="role" value="3" class="role-radio hidden" {{ old('role') == '3' ? 'checked' : '' }} onclick="updateFormFields('3')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200">
                            <div class="text-3xl mb-2">👨‍🍳</div>
                            <div class="font-bold text-gray-800">Food Truck Worker</div>
                            <div class="text-xs text-gray-500 italic">Work at a truck</div>
                        </div>
                    </label>

                    <!-- Vendor Admin (Role 2) -->
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
                    <!-- Updated name attribute from 'name' to 'full_name' -->
                    <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <!-- Role-Specific Field: Vendor Admin (Role 2) -->
            <div id="vendor-admin-fields" class="hidden space-y-4 pt-4 border-t border-gray-100">
                <h3 class="font-semibold text-indigo-700">Food Truck Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Food Truck Name</label>
                        <input type="text" id="foodtruck_name" name="foodtruck_name" value="{{ old('foodtruck_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Business License No.</label>
                        <input type="text" id="business_license_no" name="business_license_no" value="{{ old('business_license_no') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Food Truck Description</label>
                    <textarea id="foodtruck_desc" name="foodtruck_desc" rows="3" placeholder="Tell us about your cuisine..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('foodtruck_desc') }}</textarea>
                </div>
            </div>

            <!-- Role-Specific Field: Vendor Staff (Role 3) -->
            <div id="vendor-staff-fields" class="hidden space-y-1 pt-4 border-t border-gray-100">
                <label class="text-sm font-medium text-gray-700">Register As Staff For:</label>
                <select name="foodtruck_id" id="foodtruck_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="" disabled selected>-- Select A Food Truck --</option>
                    @if(isset($foodTrucks))
                        @foreach($foodTrucks as $truck)
                            <option value="{{ $truck->id }}" {{ old('foodtruck_id') == $truck->id ? 'selected' : '' }}>
                                {{ $truck->foodtruck_name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Common Fields -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone_no" value="{{ old('phone_no') }}" required oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="e.g. 0123456789" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Password</label>
                    <input type="password" 
                           id="password"
                           name="password" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    <p class="text-[10px] text-gray-400 mt-1">Min. 8 Characters, 1 Uppercase, 1 Number, 1 Symbol</p>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" 
                           id="password_confirmation"
                           name="password_confirmation" 
                           required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition duration-300 shadow-lg mt-4">
                Register Account
            </button>
        </form>
    </div>

    <script>
        function updateFormFields(role) {
            const adminFields = document.getElementById('vendor-admin-fields');
            const staffFields = document.getElementById('vendor-staff-fields');
            
            const adminInputs = adminFields.querySelectorAll('input, textarea');
            const staffSelect = staffFields.querySelector('select');

            adminFields.classList.toggle('hidden', role !== '2');
            staffFields.classList.toggle('hidden', role !== '3');
            
            adminInputs.forEach(input => {
                role === '2' ? input.setAttribute('required', 'true') : input.removeAttribute('required');
            });

            if (staffSelect) {
                role === '3' ? staffSelect.setAttribute('required', 'true') : staffSelect.removeAttribute('required');
            }
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

        window.addEventListener('load', function() {
            const selectedRole = document.querySelector('input[name="role"]:checked');
            if (selectedRole) updateFormFields(selectedRole.value);
        });
    </script>
</body>
</html>