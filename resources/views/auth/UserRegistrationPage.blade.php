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
        }
        .role-radio:checked + .role-card {
            border-color: #4f46e5;
            background-color: #f5f3ff;
            box-shadow: 0 0 0 2px #4f46e5;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="bg-indigo-600 p-8 text-white text-center">
            <h1 class="text-3xl font-bold">Create Your Account</h1>
            <p class="mt-2 opacity-80">Join our marketplace as a shopper or a business partner</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <!-- Role Selection -->
            <div class="space-y-4">
                <label class="block text-sm font-semibold text-gray-700">Register As :</label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Customer -->
                    <label class="relative">
                        <input type="radio" name="role" value="customer" class="role-radio hidden" checked onclick="updateFormFields('customer')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200 h-full">
                            <div class="text-2xl mb-1">🛍️</div>
                            <div class="font-bold text-gray-800">Customer</div>
                            <div class="text-xs text-gray-500 italic">For shoppers</div>
                        </div>
                    </label>

                    <!-- Food Truck Staff -->
                    <label class="relative">
                        <input type="radio" name="role" value="vendor_staff" class="role-radio hidden" onclick="updateFormFields('vendor_staff')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200 h-full">
                            <div class="text-2xl mb-1">🛠️</div>
                            <div class="font-bold text-gray-800">Food Truck Worker</div>
                            <div class="text-xs text-gray-500 italic">Staff</div>
                        </div>
                    </label>

                    <!-- Food Truck Admin -->
                    <label class="relative">
                        <input type="radio" name="role" value="vendor_admin" class="role-radio hidden" onclick="updateFormFields('vendor_admin')">
                        <div class="role-card border-2 border-gray-100 rounded-xl p-4 text-center hover:border-indigo-200 h-full">
                            <div class="text-2xl mb-1">🏢</div>
                            <div class="font-bold text-gray-800">Food Truck Admin</div>
                            <div class="text-xs text-gray-500 italic">Owner</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <!-- Vendor Specific Requirements (Only shown for Admin) -->
            <div id="vendor-fields" class="hidden space-y-4 pt-4 border-t border-gray-100">
                <h3 class="font-semibold text-indigo-700">Business Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Food Truck Name</label>
                        <input type="text" id="shop_name" name="shop_name" value="{{ old('shop_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium text-gray-700">Business License No.</label>
                        <input type="text" id="registration_no" name="registration_no" value="{{ old('registration_no') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
                
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Truck Description</label>
                    <textarea id="truck_description" name="truck_description" rows="3" placeholder="Tell us about your food truck and what you serve..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none resize-none">{{ old('truck_description') }}</textarea>
                </div>
            </div>

            <!-- Phone Number Field (Numbers only) -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="e.g. 0123456789" oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <!-- Passwords -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition duration-300 shadow-lg">
                Register Account
            </button>

            <p class="text-center text-sm text-gray-600">
                Already have an account? <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:underline">Login here</a>
            </p>
        </form>
    </div>

    <script>
        function updateFormFields(role) {
            const vendorFields = document.getElementById('vendor-fields');
            const inputs = vendorFields.querySelectorAll('input, textarea');

            if (role === 'vendor_admin') {
                vendorFields.classList.remove('hidden');
                // Make fields required if admin
                inputs.forEach(input => input.setAttribute('required', 'true'));
            } else {
                vendorFields.classList.add('hidden');
                // Remove requirement if not admin so form can submit
                inputs.forEach(input => input.removeAttribute('required'));
            }
        }
    </script>
</body>
</html>