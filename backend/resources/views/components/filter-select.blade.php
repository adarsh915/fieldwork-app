@props([
    'name' => null,
    'id' => null,
    'label' => null,
    'placeholder' => 'All',
    'options' => [],
    'selected' => '',
    'model' => null,
    'change' => null,
    'width' => 'w-full',
])

@php
    $normalizedOptions = [];
    foreach ($options as $k => $v) {
        if (is_object($v)) {
            $key = (string)($v->id ?? $k);
            $val = (string)($v->name_with_role ?? ($v->name ?? ($v->title ?? ($v->label ?? $k))));
            $normalizedOptions[$key] = $val;
        } else {
            $normalizedOptions[(string)$k] = (string)$v;
        }
    }
    $initialSelected = (string)($selected ?? '');
@endphp

<div x-data="{
        open: false,
        selected: '{{ addslashes($initialSelected) }}',
        optionsMap: {{ Js::from($normalizedOptions) }},
        get currentLabel() {
            const val = {{ $model ? 'this.' . $model : 'this.selected' }};
            return (val !== '' && val !== null && this.optionsMap[val] !== undefined) ? this.optionsMap[val] : '{{ addslashes($placeholder) }}';
        },
        choose(val) {
            @if($model)
                this.{{ $model }} = val;
            @else
                this.selected = val;
            @endif
            this.open = false;
            @if($name || $id)
                if (this.$refs.hiddenInput) {
                    this.$refs.hiddenInput.value = val;
                    this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            @endif
            @if($change)
                this.$nextTick(() => { {{ $change }} });
            @endif
        }
     }"
     @click.outside="open = false"
     class="relative {{ $width }}">

    @if($label)
        <label class="form-label">
            {{ $label }}
        </label>
    @endif

    <button type="button"
            @click="open = !open"
            class="form-input text-xs h-[38px] w-full flex items-center justify-between text-left bg-white cursor-pointer select-none">
        <span x-text="currentLabel" class="truncate font-medium text-ink-900"></span>
        <svg class="w-3.5 h-3.5 text-ink-400 shrink-0 ml-1.5 transition-transform duration-200"
             :class="open ? 'rotate-180 text-brand-600' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    @if($name || $id)
        <input type="hidden" @if($name) name="{{ $name }}" @endif @if($id) id="{{ $id }}" @endif x-ref="hiddenInput" :value="{{ $model ? $model : 'selected' }}">
    @endif

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute z-50 left-0 min-w-full w-max max-w-md top-full mt-1 max-h-60 overflow-y-auto bg-white rounded-lg border border-surface-border shadow-xl py-1 text-xs"
         style="display: none;">

        {{-- All / Placeholder Option --}}
        <div @click="choose('')"
             class="px-3 py-1.5 cursor-pointer hover:bg-surface-muted transition flex items-center justify-between gap-2"
             :class="({{ $model ? "$model === ''" : "selected === ''" }}) ? 'font-bold text-brand-600 bg-brand-50' : 'text-ink-800'">
            <span class="whitespace-nowrap">{{ $placeholder }}</span>
            <svg x-show="{{ $model ? "$model === ''" : "selected === ''" }}" class="w-3.5 h-3.5 text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Dynamic Options --}}
        @foreach($normalizedOptions as $val => $lbl)
            <div @click="choose('{{ addslashes($val) }}')"
                 class="px-3 py-1.5 cursor-pointer hover:bg-surface-muted transition flex items-center justify-between gap-2"
                 :class="({{ $model ? "$model == '" . addslashes($val) . "'" : "selected == '" . addslashes($val) . "'" }}) ? 'font-bold text-brand-600 bg-brand-50' : 'text-ink-800'">
                <span class="whitespace-nowrap">{{ $lbl }}</span>
                <svg x-show="{{ $model ? "$model == '" . addslashes($val) . "'" : "selected == '" . addslashes($val) . "'" }}" class="w-3.5 h-3.5 text-brand-600 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        @endforeach
    </div>
</div>
