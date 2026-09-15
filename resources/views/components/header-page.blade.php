@props([
    'title' => null,
])
<div class="">
    <flux:heading
        size="xl"
        level="1"
    >
        {{ $title ?? 'Good afternoon, Olivia' }}
    </flux:heading>
    <flux:separator
        variant="subtle"
        class="my-6"
    />
</div>
