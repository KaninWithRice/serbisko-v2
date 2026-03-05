@extends('admin.layout')

@section('title', 'Manual Verification | Admin')

@section('content')
<div class="px-6 py-8 w-full max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manual Verification Queue</h1>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" id="success-message">
        {{ session('success') }}
    </div>
    @endif

    <div id="verification-table-container" class="bg-white shadow-md rounded-lg overflow-hidden border border-gray-200">
        @include('admin.partials.verification-table')
    </div>
</div>

<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Document Preview</h3>
                        <div class="mt-4 flex justify-center bg-gray-100 rounded p-2">
                            <img id="modalImage" src="" alt="Document Scan" class="max-h-[70vh] object-contain rounded border border-gray-300">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Close Preview
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(imageUrl) {
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('imageModal').classList.remove('hidden');
    }
    function closeModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.getElementById('modalImage').src = '';
    }

    // REAL-TIME REFRESH LOGIC
    function refreshVerificationTable() {
        // Only refresh if the modal is hidden (don't interrupt the admin viewing an image)
        const modal = document.getElementById('imageModal');
        if (modal && modal.classList.contains('hidden')) {
            fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const container = document.getElementById('verification-table-container');
                if (container) {
                    container.innerHTML = html;
                }
            })
            .catch(error => console.error('Error refreshing table:', error));
        }
    }

    // Poll every 3 seconds
    setInterval(refreshVerificationTable, 3000);

    // Auto-hide success message after 5 seconds
    setTimeout(() => {
        const successMsg = document.getElementById('success-message');
        if (successMsg) {
            successMsg.style.display = 'none';
        }
    }, 5000);
</script>
@endsection
