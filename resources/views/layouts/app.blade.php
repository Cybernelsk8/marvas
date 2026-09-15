<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        <x-header-page :title="$title ?? null" />

        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
