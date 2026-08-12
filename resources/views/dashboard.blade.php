<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 tracking-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="glass-card p-8 text-center animate-fade-in-up">
        <p class="text-gray-600">{{ __("You're logged in!") }}</p>
    </div>
</x-app-layout>
