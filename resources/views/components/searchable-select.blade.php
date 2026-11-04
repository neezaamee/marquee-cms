@props([
    'id' => null,
    'model',
    'options' => [],
    'placeholder' => 'Search or select...',
    'required' => false,
    'disabled' => false,
    'size' => 'sm',
    'live' => false,
    'clearable' => true,
])

@php
    $inputUuid = $id ?? 'ss_' . str_replace(['.', '[', ']', '$'], '_', $model) . '_' . substr(md5($model), 0, 6);
    $errorKey = str_replace(['[', ']'], ['.', ''], $model);
    $optionsJson = is_string($options) ? $options : json_encode($options);
@endphp

<div class="position-relative searchable-select-container"
     wire:ignore.self
     x-data="{
        open: false,
        search: '',
        activeIndex: -1,
        @if(isset($__livewire))
        selectedId: @entangle($model){{ $live ? '.live' : '' }},
        @else
        selectedId: null,
        @endif
        options: {{ $optionsJson }},
        init() {
            this.updateLabel();
            this.$watch('selectedId', () => this.updateLabel());
            this.$watch('options', () => this.updateLabel());
        },
        updateLabel() {
            const found = this.options.find(o => String(o.id) === String(this.selectedId));
            if (found) {
                this.search = found.label || '';
            } else if (!this.selectedId) {
                this.search = '';
            }
        },
        get filteredList() {
            if (!this.search || this.isMatched()) {
                return this.options;
            }
            const q = this.search.toLowerCase().trim();
            return this.options.filter(o => {
                const labelMatch = o.label && o.label.toLowerCase().includes(q);
                const subtextMatch = o.subtext && String(o.subtext).toLowerCase().includes(q);
                const codeMatch = o.code && String(o.code).toLowerCase().includes(q);
                return labelMatch || subtextMatch || codeMatch;
            });
        },
        isMatched() {
            const found = this.options.find(o => String(o.id) === String(this.selectedId));
            return found && this.search === found.label;
        },
        selectOption(opt) {
            this.selectedId = opt.id;
            this.search = opt.label;
            this.open = false;
            this.activeIndex = -1;
            $dispatch('change', opt.id);
        },
        clearSelection() {
            this.selectedId = '';
            this.search = '';
            this.open = true;
            this.activeIndex = -1;
            $dispatch('change', '');
            this.$nextTick(() => this.$refs.searchInput.focus());
        },
        handleKeydown(e) {
            const list = this.filteredList;
            if (!this.open) {
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter') {
                    e.preventDefault();
                    this.open = true;
                    this.activeIndex = list.length > 0 ? 0 : -1;
                }
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (list.length > 0) {
                    this.activeIndex = (this.activeIndex + 1) % list.length;
                    this.scrollToActive();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (list.length > 0) {
                    this.activeIndex = (this.activeIndex - 1 + list.length) % list.length;
                    this.scrollToActive();
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.activeIndex >= 0 && this.activeIndex < list.length) {
                    this.selectOption(list[this.activeIndex]);
                }
            } else if (e.key === 'Escape') {
                this.open = false;
                this.updateLabel();
            }
        },
        scrollToActive() {
            this.$nextTick(() => {
                const listEl = this.$refs.optionsList;
                if (listEl && listEl.children[this.activeIndex]) {
                    listEl.children[this.activeIndex].scrollIntoView({ block: 'nearest' });
                }
            });
        }
     }"
     @click.outside="open = false; updateLabel()">

    <div class="input-group input-group-{{ $size }}">
        <input type="text"
               id="{{ $inputUuid }}"
               x-ref="searchInput"
               x-model="search"
               @focus="open = true"
               @input="open = true; activeIndex = 0"
               @keydown="handleKeydown($event)"
               class="form-control form-control-{{ $size }} @error($errorKey) is-invalid @enderror"
               placeholder="{{ $placeholder }}"
               @disabled($disabled)
               autocomplete="off" />

        @if($clearable)
            <button x-show="selectedId && !{{ $disabled ? 'true' : 'false' }}"
                    @click="clearSelection()"
                    type="button"
                    tabindex="-1"
                    class="btn btn-outline-secondary btn-{{ $size }}"
                    title="Clear selection">
                <i class="fas fa-times fs-11"></i>
            </button>
        @endif

        <button @click="if (!{{ $disabled ? 'true' : 'false' }}) open = !open"
                type="button"
                tabindex="-1"
                class="btn btn-outline-secondary btn-{{ $size }}">
            <i class="fas fa-chevron-down fs-11 transition-all" :class="{ 'fa-rotate-180': open }"></i>
        </button>
    </div>

    @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <!-- Dropdown Options List -->
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="position-absolute start-0 end-0 bg-white border border-200 rounded shadow-lg mt-1 py-1"
         style="max-height: 230px; overflow-y: auto; z-index: 1060; display: none;">

        <div x-ref="optionsList">
            <template x-for="(opt, index) in filteredList" :key="opt.id">
                <div @click="selectOption(opt)"
                     @mouseenter="activeIndex = index"
                     :class="{
                         'bg-primary text-white': activeIndex === index,
                         'bg-primary-subtle text-primary fw-bold': String(selectedId) === String(opt.id) && activeIndex !== index,
                         'text-dark': String(selectedId) !== String(opt.id) && activeIndex !== index
                     }"
                     class="px-3 py-1.5 d-flex justify-content-between align-items-center cursor-pointer border-bottom border-light fs-11"
                     style="user-select: none;">
                    <div class="d-flex align-items-center flex-grow-1 text-truncate pe-2">
                        <span x-text="opt.label" class="text-truncate"></span>
                    </div>
                    <template x-if="opt.subtext">
                        <small :class="{ 'text-white-50': activeIndex === index, 'text-muted': activeIndex !== index }"
                               class="fs-11 ms-2 font-monospace flex-shrink-0"
                               x-text="opt.subtext"></small>
                    </template>
                </div>
            </template>
        </div>

        <div x-show="filteredList.length === 0" class="px-3 py-2 text-center text-muted fs-11">
            <span class="fas fa-search me-1 text-400"></span>No matching results
        </div>
    </div>
</div>
