<section
    id="servicios"
    class="flex flex-col gap-12 px-5 py-7 bg-[#F7F1E8] sm:px-10 md:px-16 md:py-10 lg:grid lg:grid-cols-[1fr_3fr] lg:gap-16"
>
    <div class="services__model">
        <span class="flex items-center justify-center size-48 rounded-full bg-cyan-950 text-[#C08A2E] shrink-0">
            <flux:icon
                name="{{ $icon }}"
                class="size-36"
            />
        </span>
    </div>

    <div>
        <div class="services__heading flex flex-col items-center text-center justify-center">
            <span class="services__eyebrow">
                <span class="services__eyebrow-line"></span>
                objetivo
                <span class="services__eyebrow-line"></span>
            </span>
            <p class="text-lg font-medium italic text-cyan-950 mt-2">
                {{ $text }}
            </p>
        </div>
    </div>
</section>
