<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Pending Registrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">

    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar -->
        <div class="w-full md:w-64 bg-slate-800 text-white flex-shrink-0">
            <div class="p-6 text-2xl font-bold border-b border-slate-700">
                Admin Panel
            </div>
            <nav class="mt-4 px-4">
                <a href="#" class="block py-3 px-4 rounded text-gray-400 hover:text-white hover:bg-slate-700 transition">
                    <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                </a>
                <a href="{{ route('admin.pending.trucks') }}" class="block py-3 px-4 mt-2 rounded bg-blue-600 text-white">
                    <i class="fas fa-truck mr-3"></i> Truck Admin Pending
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-10">
                    @csrf
                    <button type="submit" class="w-full text-left py-3 px-4 rounded text-gray-400 hover:text-white hover:bg-red-600 transition">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </button>
                </form>
            </nav>
        </div>

        <!-- Content -->
        <div class="flex-1">
            <header class="bg-white shadow px-6 py-4">
                <h2 class="text-xl font-semibold text-gray-800">Registration Management</h2>
            </header>

            <main class="p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Truck Admin Pending Registration</h1>
                    <p class="text-gray-600">Total Pending: {{ $pendingAdmins->count() }}</p>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">User Info</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Registered At</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($pendingAdmins as $user)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <form action="{{ route('admin.approve.user', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition shadow-sm">
                                            Approve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                    No pending registrations found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

</body>
</html>