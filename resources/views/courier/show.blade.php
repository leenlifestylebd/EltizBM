@extends('layouts.app')
@section('title', 'Shipment Details')

@section('content')

<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('courier.index') }}" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Shipment Details</h2>
    </div>