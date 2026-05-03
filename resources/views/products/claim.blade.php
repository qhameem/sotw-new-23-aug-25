@extends('layouts.app')

@section('title', 'Claim ' . $product->name . ' | Software on the Web')

@section('header-title')
    <h2 class="text-base font-semibold py-[3px] hidden md:block">Claim Product</h2>
@endsection

@section('content')
    <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
            <div class="flex items-center gap-4">
                <img src="{{ $product->logo_url }}" alt="{{ $product->name }}" class="size-16 rounded-2xl border border-gray-100 object-cover">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Product</p>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $product->tagline }}</p>
                    <a href="{{ route('products.show', $product) }}" class="text-sm text-primary-600 hover:underline mt-2 inline-block">
                        View product page
                    </a>
                </div>
            </div>
        </div>

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

        @if ($existingClaim && $existingClaim->status === \App\Models\ProductClaim::STATUS_PENDING)
            <div class="bg-white border border-amber-200 rounded-2xl shadow-sm p-6 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Pending claim</p>
                    <h2 class="text-lg font-semibold text-gray-900 mt-1">Your claim is waiting for admin review</h2>
                </div>

                <div class="space-y-2 text-sm text-gray-700">
                    <p><span class="font-semibold">Proof type:</span> {{ $existingClaim->proofTypeLabel() }}</p>
                    @if($existingClaim->proof_value)
                        <p><span class="font-semibold">Proof details:</span> {{ $existingClaim->proof_value }}</p>
                    @endif
                    @if($existingClaim->message_to_admin)
                        <p><span class="font-semibold">Message to admin:</span> {{ $existingClaim->message_to_admin }}</p>
                    @endif
                    <p>
                        <span class="font-semibold">Verified email domain match:</span>
                        {{ $existingClaim->auto_email_domain_match ? 'Yes' : 'No' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('products.claim.destroy', $product) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-black">
                        Cancel pending claim
                    </button>
                </form>
            </div>
        @else
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 space-y-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Ownership proof</p>
                    <h2 class="text-lg font-semibold text-gray-900 mt-1">Submit your claim</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Admin will review your claim before ownership is reassigned. Once approved, you will be able to edit the product.
                    </p>
                </div>

                <div class="rounded-xl border {{ $autoEmailDomainMatch ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50' }} px-4 py-3 text-sm">
                    <p class="font-semibold {{ $autoEmailDomainMatch ? 'text-green-800' : 'text-gray-800' }}">
                        Automatic proof signal: verified email domain check
                    </p>
                    <p class="mt-1 {{ $autoEmailDomainMatch ? 'text-green-700' : 'text-gray-600' }}">
                        Your verified email domain is <span class="font-medium">{{ $emailDomain ?? 'unknown' }}</span> and the product domain is
                        <span class="font-medium">{{ $productHost ?? 'unknown' }}</span>.
                        {{ $autoEmailDomainMatch ? 'They match, so this will be shown to admin as a positive ownership signal.' : 'They do not match, so you should add another proof detail below.' }}
                    </p>
                </div>

                <form method="POST" action="{{ route('products.claim.store', $product) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="proof_type" class="block text-sm font-semibold text-gray-700 mb-2">Proof type</label>
                        <select id="proof_type" name="proof_type" class="w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500" required>
                            <option value="">Select proof type</option>
                            @foreach($proofTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('proof_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('proof_type')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="proof_value" class="block text-sm font-semibold text-gray-700 mb-2">Proof details or link</label>
                        <textarea id="proof_value" name="proof_value" rows="4" class="w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500" placeholder="Example: DNS TXT record value, public verification URL, official social profile link, or dashboard details">{{ old('proof_value') }}</textarea>
                        @error('proof_value')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message_to_admin" class="block text-sm font-semibold text-gray-700 mb-2">Message to admin</label>
                        <textarea id="message_to_admin" name="message_to_admin" rows="5" class="w-full rounded-xl border-gray-300 focus:border-primary-500 focus:ring-primary-500" placeholder="Add any context that helps the admin verify you are the product owner.">{{ old('message_to_admin') }}</textarea>
                        @error('message_to_admin')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-black">
                            Submit claim
                        </button>
                        <a href="{{ route('products.show', $product) }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">
                            Back to product
                        </a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection
