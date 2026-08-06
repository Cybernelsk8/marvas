<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="dark"
>

<head>
    @include('partials.head')
</head>

<body class="bg-[#F7F1E8]">
    <div class="container max-w-7xl mx-auto">
        @include('landing-page.partials.header')
        {{ $slot }}
        @include('landing-page.partials.foot')
    </div>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
