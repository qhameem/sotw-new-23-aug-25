@extends('layouts.admin')

@section('title', 'Custom Category Submissions')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Custom Category Submissions</h1>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if($submissions->isEmpty())
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">No pending custom category submissions.</p>
        </div>
    @else
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($submissions as $submission)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <a href="{{ route('products.show', $submission->product->slug) }}" 
                                   class="hover:text-blue-600 hover:underline" 
                                   target="_blank">
                                    {{ $submission->product->name }}
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                         {{ $submission->type === 'category' ? 'bg-blue-100 text-blue-800' : 
                                            ($submission->type === 'best_for' ? 'bg-green-100 text-green-800' : 
                                            'bg-purple-100 text-purple-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $submission->type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $submission->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $submission->product->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $submission->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button 
                                onclick="openApprovalModal({{ $submission->id }}, '{{ addslashes($submission->name) }}', '{{ $submission->type }}')" 
                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                            >
                                Review
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Approval Modal -->
<div id="approval-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Review Custom Category</h3>
                <button onclick="closeApprovalModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form id="approval-form" method="POST">
                @csrf
                @method('POST')
                
                <input type="hidden" id="submission-id" name="submission_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" id="category-name" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <input type="text" id="category-type" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" id="slug" name="slug" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                </div>
                
                <div class="mb-6">
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" required rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeApprovalModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button type="submit" formaction="" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        Approve
                    </button>
                    <button type="button" onclick="rejectSubmission()" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        Reject
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentSubmissionId = null;

function openApprovalModal(submissionId, name, type) {
    currentSubmissionId = submissionId;
    document.getElementById('submission-id').value = submissionId;
    document.getElementById('category-name').value = name;
    document.getElementById('category-type').value = type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ');
    
    // Generate slug from name
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
    
    // Set form action to the approve endpoint
    document.getElementById('approval-form').action = `/admin/custom-category-submissions/${submissionId}/approve`;
    
    document.getElementById('approval-modal').classList.remove('hidden');
}

function closeApprovalModal() {
    document.getElementById('approval-modal').classList.add('hidden');
    currentSubmissionId = null;
}

function rejectSubmission() {
    if (confirm('Are you sure you want to reject this custom category submission?')) {
        fetch(`/admin/custom-category-submissions/${currentSubmissionId}/reject`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeApprovalModal();
                location.reload(); // Reload the page to update the list
            } else {
                alert('Error rejecting submission');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error rejecting submission');
        });
    }
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('approval-modal');
    if (event.target === modal) {
        closeApprovalModal();
    }
}
</script>
@endsection