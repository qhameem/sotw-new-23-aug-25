@extends('layouts.app')

@section('header-title')
    <div class="flex gap-4 justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-700 py-[1px]">
            {{ isset($product) ? 'Edit Product: ' . ($displayData['name'] ?? $product->name) : 'Add Your Product' }}
        </h1>
        <button data-tooltip-target="tooltip-clear-form" onclick="clearForm()" type="button" class="bg-white border border-gray-300 hover:bg-gray-100 text-xs font-semibold py-1 px-3 rounded-lg transition-all duration-200 ease-in-out">
            Clear Form
        </button>
        <div id="tooltip-clear-form" role="tooltip" class="absolute z-10 invisible inline-block px-3
