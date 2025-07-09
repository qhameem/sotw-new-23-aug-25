@extends('layouts.app')

@section('title')
<h2 class="text-base font-semibold py-[3px] hidden md:block">Promote your software</h2>
@endsection

@section('actions')
    <div class="md:flex items-center space-x-2">
        @if(!isset($isCategoryPage) || !$isCategoryPage)
        <a href="{{ route('categories.index') }}" class="bg-white border border-gray-300 hover:bg-gray-100 text-sm font-semibold py-1 px-3 rounded-lg">
            Categories
        </a>
        @endif
       <x-add-product-button />
    </div>
@endsection
   
@section('content')
<div class="p-4">
<h1 class="text-2xl font-bold text-gray-700">Advertising on Software on the web</h1>
<div class="h-1"></div>
<div>
    <p class="text-gray-700 text-lg">Boost your visibility and get in front of tens of thousands of potential customers 🚀</p>
    <p class="text-gray-600 text-sm mt-4 mb-2">Submitting your product is free, but you'll need to wait in line. <a href="" class="underline underline-offset-2">Click here</a> to learn how it works.</p>
</div>
<div class="h-3"></div>
<div class="mx-auto space-y-4">

  <x-promote.fast-track-card />

  <!-- 3-column layout for remaining cards -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    
    <!-- Sponsorship -->
    <!-- <div class="bg-white border rounded-lg p-4 shadow text-center">
      <h4 class="text-lg font-semibold mb-2">Sponsorship</h4>
      <div class="text-xl font-bold mb-1">$97</div>
      <ul class="text-sm text-gray-600 text-left mt-4 space-y-1">
        <li>✔ Featured in our weekly newsletter</li>
        <li>✔ Seen by 9,100 subscribers</li>
        <li>✔ Renewable anytime</li>
      </ul>
    </div> -->

    <x-promote.premium-spot-card :spotsAvailable="$spotsAvailable" />


    <x-promote.product-review-card />



  </div>
</div>

</div>

@endsection
