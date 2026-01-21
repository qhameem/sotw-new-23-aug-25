@extends('layouts.submission')

@section('header-title')
    <div class="flex gap-4 justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
            Edit Product: {{ $product->name }}
        </h1>
    </div>
@endsection


@section('content')
    <div class="relative flex-1 flex flex-col h-full">
        <div class="w-full flex-1 flex flex-col h-full">
            @if(isset($product) && $product->approved && $product->has_pending_edits)
                <div class="mb-4 p-3 rounded-md bg-yellow-50 border border-yellow-400 text-yellow-800 text-sm">
                    <strong>Pending Review:</strong> You have submitted edits for this product that are currently awaiting
                    administrator approval. The changes you make below will update your pending proposal. The live product
                    will not change until an admin approves your edits.
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 text-red-800 p-2 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Simplified Container: Direct flex child, full height/width -->
            <div id="product-submit-app" class="w-full flex-1 flex flex-col h-full" x-ignore
                data-display-data="{{ json_encode($displayData ?? []) }}"
                data-regular-categories="{{ json_encode($regularCategories ?? []) }}"
                data-best-for-categories="{{ json_encode($bestForCategories ?? []) }}"
                data-pricing-categories="{{ json_encode($pricingCategories ?? []) }}"
                data-all-tech-stacks-data="{{ json_encode($allTechStacksData ?? []) }}"
                data-product="{{ json_encode($product ?? null) }}"
                data-all-categories="{{ json_encode($allCategories ?? []) }}"
                data-types="{{ json_encode($types ?? []) }}"
                data-selected-best-for-categories="{{ json_encode($selectedBestForCategories ?? []) }}"
                data-is-admin="true">
            </div>
        </div>
    </div>
@endsection
