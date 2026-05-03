@extends('layouts.app')

@section('title', 'Product Claims')

@section('header-title')
    <h2 class="text-base font-semibold py-[3px] hidden md:block">Product Claims</h2>
@endsection

@section('content')
    <div class="px-4 py-8 space-y-8">
        @if (session('success'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <section class="space-y-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pending product claims</h1>
                <p class="text-sm text-gray-600 mt-1">Approve a claim to reassign product ownership. The product will then show that user as the submitter and they will be able to edit it.</p>
            </div>

            @forelse($pendingClaims as $claim)
                <article class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center gap-4">
                                <img src="{{ $claim->product->logo_url }}" alt="{{ $claim->product->name }}" class="size-14 rounded-2xl border border-gray-100 object-cover">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Product</p>
                                    <h2 class="text-xl font-semibold text-gray-900">{{ $claim->product->name }}</h2>
                                    <a href="{{ route('products.show', $claim->product) }}" class="text-sm text-primary-600 hover:underline">
                                        View product page
                                    </a>
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2 text-sm text-gray-700">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Claimant</p>
                                    <p class="font-medium text-gray-900">{{ $claim->user->name }}</p>
                                    <p>{{ $claim->user->email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-1">Current owner</p>
                                    <p class="font-medium text-gray-900">{{ $claim->product->user->name }}</p>
                                    <p>{{ $claim->product->user->email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="lg:text-right">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $claim->auto_email_domain_match ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $claim->auto_email_domain_match ? 'Verified email domain matches' : 'Email domain does not match' }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Proof type</p>
                            <p class="text-sm font-medium text-gray-900">{{ $claim->proofTypeLabel() }}</p>
                            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Proof details</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $claim->proof_value ?: 'No extra proof details provided.' }}</p>
                        </div>

                        <div class="rounded-xl bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-2">Message to admin</p>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $claim->message_to_admin ?: 'No message provided.' }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('admin.product-claims.approve', $claim) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                                Approve and assign
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.product-claims.reject', $claim) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                                Reject claim
                            </button>
                        </form>

                        <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Manage in products
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center text-sm text-gray-500">
                    No pending product claims right now.
                </div>
            @endforelse
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Recently reviewed claims</h2>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Product</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">User</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Reviewed by</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Reviewed at</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentlyReviewedClaims as $claim)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">{{ $claim->product->name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $claim->user->name }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                            {{ $claim->status === \App\Models\ProductClaim::STATUS_APPROVED ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $claim->status === \App\Models\ProductClaim::STATUS_REJECTED ? 'bg-red-100 text-red-700' : '' }}
                                            {{ $claim->status === \App\Models\ProductClaim::STATUS_CANCELLED ? 'bg-gray-100 text-gray-700' : '' }}">
                                            {{ ucfirst($claim->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">{{ $claim->reviewer?->name ?? 'System' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $claim->reviewed_at?->diffForHumans() ?? 'Not reviewed' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">No reviewed claims yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
