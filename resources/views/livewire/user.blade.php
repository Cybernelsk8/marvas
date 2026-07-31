<div class="p-8">
    <x-select
        wire:model.live="selected"
        searchable
        multiple
        :$options
    />


    <x-data-table
        :$headers
        :rows="$this->rows"
    >
    </x-data-table>

    <flux:button wire:click="getToast">
        Toast
    </flux:button>
</div>
