<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FTOS - Customer Portal</title>
    
    <!-- Tailwind & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-white shadow-xl hidden md:block">
            @include('layouts.customer.customer-left-navbar')
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Top Header (Optional) -->
            <header class="bg-white border-b h-16 flex items-center px-8 justify-between">
                <div>
                    @yield('header')
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600 text-sm font-medium">{{ Auth::user()->name }}</span>
                    <div class="h-8 w-8 bg-indigo-500 rounded-full flex items-center justify-center text-white">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>