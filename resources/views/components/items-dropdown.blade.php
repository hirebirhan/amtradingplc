@props([
    'items' => [],
    'selected' => null,
    'name' => 'item_id',
    'placeholder' => 'Search for an item...',
    'noResultsText' => 'No items found',
    'wireModel' => null
])

<div
    x-data="{
        open: false,
        search: '',
        selected: @js($selected),
        selectedName: '{{ $selected ? collect($items)->firstWhere('id', $selected)['name'] ?? '' : '' }}',
        highlightedIndex: 0,
        items: @js($items),
        filteredItems() {
            if (!this.search) return this.items.slice(0, 10);

            return this.items
                .filter(item => item.name.toLowerCase().includes(this.search.toLowerCase()) ||
                                 item.sku.toLowerCase().includes(this.search.toLowerCase()))
                .slice(0, 10);
        },
        selectItem(item) {
            this.selected = item.id;
            this.selectedName = item.name;
            this.open = false;
            this.search = '';

            @if($wireModel)
            $wire.set('{{ $wireModel }}', item.id);
            @endif
        },
        onKeyDown(event) {
            const filteredItems = this.filteredItems();

            // Enter
            if (event.keyCode === 13 && filteredItems.length > 0) {
                this.selectItem(filteredItems[this.highlightedIndex]);
                event.preventDefault();
            }
            // Arrow Down
            else if (event.keyCode === 40) {
                this.highlightedIndex = this.highlightedIndex < filteredItems.length - 1 ? this.highlightedIndex + 1 : 0;
                event.preventDefault();
            }
            // Arrow Up
            else if (event.keyCode === 38) {
                this.highlightedIndex = this.highlightedIndex > 0 ? this.highlightedIndex - 1 : filteredItems.length - 1;
                event.preventDefault();
            }
            // Escape
            else if (event.keyCode === 27) {
                this.open = false;
            }
        }
    }"
    x-on:click.away="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="selected" />

    <div class="relative">
        <input
            type="text"
            x-model="search"
            x-on:focus="open = true"
            x-on:keydown="onKeyDown"
            x-bind:placeholder="selected ? selectedName : '{{ $placeholder }}'"
            class="form-control"
        />

        <button
            type="button"
            x-on:click="open = !open; $nextTick(() => $refs.searchInput?.focus())"
            class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 w-full mt-1 max-h-60 overflow-auto rounded-md bg-white border border-gray-300 shadow-lg"
    >
        <div x-show="filteredItems().length === 0" class="px-4 py-3 text-sm text-gray-500">
            {{ $noResultsText }}
        </div>

        <ul x-show="filteredItems().length > 0">
            <template x-for="(item, index) in filteredItems()" :key="item.id">
                <li
                    x-on:click="selectItem(item)"
                    x-on:mouseenter="highlightedIndex = index"
                    x-bind:class="{ 'bg-gray-100': highlightedIndex === index }"
                    class="px-4 py-2 text-sm cursor-pointer hover:bg-gray-100"
                >
                    <div class="font-medium" x-text="item.name"></div>
                    <div class="text-xs text-gray-500">
                        SKU: <span x-text="item.sku"></span>
                        <template x-if="item.quantity !== undefined">
                            - Stock: <span x-text="item.quantity"></span>
                        </template>
                    </div>
                </li>
            </template>
        </ul>
    </div>
</div>

@once
<style>
    [x-cloak] { display: none !important; }
</style>
@endonce