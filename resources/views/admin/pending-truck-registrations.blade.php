<x-app-layout>
    <x-slot name="header"></x-slot>

    <!-- Page Header Area -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between animate-fade-in-up">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">
                Pending Food Truck Registrations
            </h1>
            <p class="text-gray-500 mt-1 font-medium">
                Review new food truck applications for approval.
            </p>
        </div>

        <div class="mt-4 sm:mt-0">
            <a
                href="{{ route('admin.dashboard') }}"
                class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 transition flex items-center"
            >
                <i class="fas fa-arrow-left mr-2 text-blue-500"></i>
                Back to Overview
            </a>
        </div>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            {{ session('success') }}
        </div>
    @endif

    <!-- Rejected Alert -->
    @if(session('rejected'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
            <i class="fas fa-times-circle mr-3"></i>
            {{ session('rejected') }}
        </div>
    @endif

    <!-- Table Content -->
    <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-100 animate-fade-in">
        <div class="overflow-x-auto">
            <table class="min-w-full leading-normal table-fixed">
                <colgroup>
                    <col class="w-[8%]">
                    <col class="w-[20%]">
                    <col class="w-[18%]">
                    <col class="w-[26%]">
                    <col class="w-[14%]">
                    <col class="w-[14%]">
                </colgroup>
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Truck Name</th>
                        <th class="px-6 py-4">License No.</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">Applied On</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingRegistrations as $truck)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-xs font-bold text-gray-400">
                                #{{ $truck->id }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-800">
                                        {{ $truck->foodtruck_name }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-600 font-mono">
                                {{ $truck->business_license_no }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="max-w-xs">
                                    <p
                                        class="text-xs text-gray-600 line-clamp-2 leading-relaxed"
                                        title="{{ $truck->foodtruck_desc }}"
                                    >
                                        {{ $truck->foodtruck_desc ?? 'No description provided.' }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-xs text-gray-500 font-medium">
                                {{ $truck->created_at->format('M d, Y') }}
                                <span class="block text-[10px] text-gray-400 uppercase tracking-tighter">
                                    {{ $truck->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">

                                    <!-- Approve Button -->
                                    <form
                                        action="{{ route('admin.approve-truck', $truck->id) }}"
                                        method="POST"
                                        class="inline-flex"
                                        data-action-type="approve"
                                        data-truck-name="{{ $truck->foodtruck_name }}"
                                    >
                                        @csrf
                                        <button
                                            type="button"
                                            onclick="openActionConfirmModal(this.closest('form'))"
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                        >
                                            Approve
                                        </button>
                                    </form>

                                    <!-- Reject Button -->
                                    <form
                                        action="{{ route('admin.reject-truck', $truck->id) }}"
                                        method="POST"
                                        class="inline-flex"
                                        data-action-type="reject"
                                        data-truck-name="{{ $truck->foodtruck_name }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            onclick="openActionConfirmModal(this.closest('form'))"
                                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-1.5 px-4 rounded-lg transition duration-200 shadow-sm text-[11px]"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-20 text-center text-gray-400 bg-white">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4 text-gray-200">
                                        <i class="fas fa-clipboard-check text-3xl"></i>
                                    </div>
                                    <p class="text-lg font-bold text-gray-500">
                                        No Pending Applications
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        Everything is caught up!
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($pendingRegistrations->hasPages())
        <div class="mt-8">
            {{ $pendingRegistrations->links() }}
        </div>
    @endif

    <!-- Confirm Action Modal -->
    <div
        id="actionConfirmModal"
        style="display:none;"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-[80] flex items-center justify-center p-4"
        onclick="handleActionModalBackdropClick(event)"
    >
        <div id="actionConfirmPanel" class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <h3 id="actionConfirmTitle" class="text-base font-black text-gray-900">Confirm Action</h3>
                    <p id="actionConfirmSubtitle" class="text-xs text-gray-400 font-medium mt-0.5">Please review before proceeding.</p>
                </div>
                <button
                    type="button"
                    onclick="closeActionConfirmModal()"
                    class="w-9 h-9 flex items-center justify-center rounded-xl hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-all"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="px-6 py-6">
                <div id="actionConfirmIconWrap" class="w-12 h-12 rounded-2xl flex items-center justify-center mb-4 bg-indigo-100 text-indigo-600">
                    <i id="actionConfirmIcon" class="fas fa-check-circle text-lg"></i>
                </div>
                <p id="actionConfirmMessage" class="text-sm text-gray-600 leading-relaxed"></p>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-2">
                <button
                    type="button"
                    onclick="closeActionConfirmModal()"
                    class="px-5 py-2.5 border-2 border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-100 hover:border-gray-300 transition-all"
                >
                    Cancel
                </button>

                <button
                    id="actionConfirmSubmitBtn"
                    type="button"
                    onclick="submitActionConfirmForm()"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-md transition-all flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700"
                >
                    <i id="actionConfirmSubmitIcon" class="fas fa-check"></i>
                    <span id="actionConfirmSubmitText">Yes, Approve</span>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let actionConfirmTargetForm = null;

            function openActionConfirmModal(formElement) {
                if (!formElement) {
                    return false;
                }

                actionConfirmTargetForm = formElement;

                const actionType = formElement.dataset.actionType || 'approve';
                const truckName = formElement.dataset.truckName || 'this registration';
                const isReject = actionType === 'reject';

                const modal = document.getElementById('actionConfirmModal');
                const title = document.getElementById('actionConfirmTitle');
                const subtitle = document.getElementById('actionConfirmSubtitle');
                const message = document.getElementById('actionConfirmMessage');
                const iconWrap = document.getElementById('actionConfirmIconWrap');
                const icon = document.getElementById('actionConfirmIcon');
                const submitBtn = document.getElementById('actionConfirmSubmitBtn');
                const submitIcon = document.getElementById('actionConfirmSubmitIcon');
                const submitText = document.getElementById('actionConfirmSubmitText');

                if (isReject) {
                    title.textContent = 'Reject Registration?';
                    subtitle.textContent = 'This action cannot be undone.';
                    message.textContent = `You are about to reject "${truckName}". The truck record will be removed and the owner account status will be set to rejected.`;
                    iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center mb-4 bg-red-100 text-red-600';
                    icon.className = 'fas fa-trash-alt text-lg';
                    submitBtn.className = 'px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-md transition-all flex items-center gap-2 bg-red-600 hover:bg-red-700';
                    submitIcon.className = 'fas fa-trash-alt';
                    submitText.textContent = 'Yes, Reject';
                } else {
                    title.textContent = 'Approve Registration?';
                    subtitle.textContent = 'This will activate the food truck account.';
                    message.textContent = `Approve "${truckName}" now? The registration will move to Approved Trucks and the owner can access full features.`;
                    iconWrap.className = 'w-12 h-12 rounded-2xl flex items-center justify-center mb-4 bg-indigo-100 text-indigo-600';
                    icon.className = 'fas fa-check-circle text-lg';
                    submitBtn.className = 'px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-md transition-all flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700';
                    submitIcon.className = 'fas fa-check';
                    submitText.textContent = 'Yes, Approve';
                }

                modal.style.display = 'flex';
                document.body.classList.add('overflow-hidden');
                return false;
            }

            function closeActionConfirmModal() {
                const modal = document.getElementById('actionConfirmModal');
                modal.style.display = 'none';
                actionConfirmTargetForm = null;
                document.body.classList.remove('overflow-hidden');
            }

            function submitActionConfirmForm() {
                if (actionConfirmTargetForm) {
                    actionConfirmTargetForm.submit();
                }
            }

            function handleActionModalBackdropClick(event) {
                if (event.target.id === 'actionConfirmModal') {
                    closeActionConfirmModal();
                }
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    const modal = document.getElementById('actionConfirmModal');
                    if (modal && modal.style.display === 'flex') {
                        closeActionConfirmModal();
                    }
                }
            });
        </script>
    @endpush
</x-app-layout>
