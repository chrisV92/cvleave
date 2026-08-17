@php($current = app()->getLocale())
<x-filament::dropdown placement="bottom-end">
    <x-slot name="trigger">
        <button
            type="button"
            class="fi-icon-btn relative flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold text-gray-500 outline-none transition duration-75 hover:bg-gray-500/5 focus-visible:bg-gray-500/5 dark:text-gray-400 dark:hover:bg-gray-300/5 dark:focus-visible:bg-gray-300/5"
        >
            {{ strtoupper($current) }}
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item
            :href="route('locale.switch', 'el')"
            :color="$current === 'el' ? 'primary' : 'gray'"
            tag="a"
        >
            Ελληνικά
        </x-filament::dropdown.list.item>

        <x-filament::dropdown.list.item
            :href="route('locale.switch', 'en')"
            :color="$current === 'en' ? 'primary' : 'gray'"
            tag="a"
        >
            English
        </x-filament::dropdown.list.item>
    </x-filament::dropdown.list>
</x-filament::dropdown>
