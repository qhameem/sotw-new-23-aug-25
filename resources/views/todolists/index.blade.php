@extends('layouts.todolist')

@section('title', $meta_title)
@section('meta_description', $meta_description)

@section('content')
<!-- Include Todo List Section -->
@include('todolists.partials.todo-section')

<!-- Include Content Section -->
@include('todolists.partials.content-section')
@endsection

@push('scripts')
<style>
    .priority-tag {
        display: inline-flex;
        align-items: center;
        padding: 2px 12px;
        border-radius: 16px;
        font-size: 0.75rem; /* text-xs */
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background-color: #f3f4f6; /* gray-10 */
        color: #4b5563; /* gray-600 */
    }
    .priority-tag.active {
        background-color: #e0f2fe; /* sky-10 */
        color: #075985; /* sky-800 */
        border: 1px solid #bae6fd; /* sky-200 */
        font-weight: 500;
    }
    .priority-tag .close-icon {
        margin-left: 8px;
        width: 16px;
        height: 16px;
        stroke: #9ca3af; /* gray-40 */
    }
    .priority-count {
        margin-left: 8px;
        min-width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #e5e7eb; /* gray-200 */
        color: #4b5563; /* gray-600 */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem; /* text-xs */
        font-weight: 500;
    }
    .priority-tag.active .priority-count {
        background-color: #bae6fd; /* sky-200 */
        color: #0c4a6e; /* sky-900 */
    }
    /* Priority-specific colors */
    .priority-tag[data-priority="rose"] {
        background-color: #fecaca; /* red-200 */
        color: #dc2626; /* red-60 */
    }
    .priority-tag[data-priority="rose"].active {
        background-color: #f87171; /* red-40 */
        color: #b91c1c; /* red-700 */
        border: 1px solid #ef4444; /* red-50 */
    }
    .priority-tag[data-priority="orange"] {
        background-color: #ffedd5; /* orange-100 */
        color: #c2410c; /* orange-700 */
    }
    .priority-tag[data-priority="orange"].active {
        background-color: #fed1aa; /* orange-200 */
        color: #9a3412; /* orange-800 */
        border: 1px solid #fed1aa; /* orange-200 */
    }
    .priority-tag[data-priority="yellow"] {
        background-color: #fef9c3; /* yellow-100 */
        color: #854d0e; /* yellow-700 */
    }
    .priority-tag[data-priority="yellow"].active {
        background-color: #fde047; /* yellow-20 */
        color: #713f12; /* yellow-800 */
        border: 1px solid #fde047; /* yellow-20 */
    }
    .priority-tag[data-priority="green"] {
        background-color: #d1fae5; /* green-10 */
        color: #166534; /* green-70 */
    }
    .priority-tag[data-priority="green"].active {
        background-color: #a7f3d0; /* green-20 */
        color: #14532d; /* green-80 */
        border: 1px solid #a7f3d0; /* green-200 */
    }
    .priority-tag[data-priority="gray"] {
        background-color: #f3f4f6; /* gray-10 */
        color: #4b5563; /* gray-600 */
    }
    .priority-tag[data-priority="gray"].active {
        background-color: #e5e7eb; /* gray-20 */
        color: #374151; /* gray-700 */
        border: 1px solid #d1d5db; /* gray-300 */
    }
</style>
@endpush