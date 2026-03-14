<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'FTOS') }}</title>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <style>
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fadeInUp .45s ease-out both; }
    .stagger-children > *:nth-child(1) { animation-delay: .04s; }
    .stagger-children > *:nth-child(2) { animation-delay: .08s; }
    .stagger-children > *:nth-child(3) { animation-delay: .12s; }
    .stagger-children > *:nth-child(4) { animation-delay: .16s; }
    .stagger-children > *:nth-child(5) { animation-delay: .20s; }
    .stagger-children > *:nth-child(6) { animation-delay: .24s; }
    .stagger-children > * { animation: fadeInUp .45s ease-out both; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans antialiased" x-data>

  <!-- Top Navbar -->
  <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
      <a href="/" class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-amber-400 flex items-center justify-center">
          <i class="fas fa-utensils text-amber-900 text-sm"></i>
        </div>
        <span class="text-lg font-black text-gray-900 tracking-tight">{{ config('app.name', 'FTOS') }}</span>
      </a>

      <div class="flex items-center gap-2">
        <a href="{{ route('login') }}"
           class="px-4 py-2 text-sm font-bold text-gray-600 hover:text-gray-900 transition-colors">
          Log in
        </a>
        <a href="{{ route('register') }}"
           class="px-4 py-2 text-sm font-black text-white bg-amber-400 hover:bg-amber-500 rounded-xl transition-colors shadow-sm">
          Register
        </a>
      </div>
    </div>
  </nav>

  <!-- Page Content -->
  <main>
    @yield('content')
  </main>

</body>
</html>
