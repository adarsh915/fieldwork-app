@props([
    'actions' => [],  
    {{--
      Each action is an array with:
        type:    'link' | 'form'
        label:   string
        href:    string (for links)
        action:  string (form action URL)
        method:  'DELETE'|'PATCH'|etc (for forms)
        confirm: string|null (confirm message for forms)
        icon:    string (lucide icon name, e.g. 'pencil', 'trash-2')
        variant: 'default' | 'danger'
    --}}
])
<div x-data="{ open: false }" class="relative flex justify-end">
    <button
        @click="open = !open"
        @click.stop
        class="p-1.5 rounded-lg hover:bg-surface-muted transition-colors"
        aria-label="Row actions"
    >
        <x-lucide-more-vertical class="w-4 h-4 text-ink-600" />
    </button>

    <div
        x-show="open"
        @click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-1 w-44 bg-white border border-surface-border rounded-xl shadow-lg z-20 py-1 top-full"
        style="display: none;"
    >
        @foreach ($actions as $action)
            @if (isset($action['divider']) && $action['divider'])
                <div class="my-1 border-t border-surface-border"></div>
            @elseif ($action['type'] === 'link')
                <a
                    href="{{ $action['href'] }}"
                    class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-surface-muted transition-colors {{ ($action['variant'] ?? 'default') === 'danger' ? 'text-danger-text hover:bg-danger-bg' : 'text-ink-900' }}"
                >
                    @if (!empty($action['icon']))
                        @switch($action['icon'])
                            @case('pencil') <x-lucide-pencil class="w-4 h-4 shrink-0" /> @break
                            @case('eye') <x-lucide-eye class="w-4 h-4 shrink-0" /> @break
                            @case('trash-2') <x-lucide-trash-2 class="w-4 h-4 shrink-0" /> @break
                            @case('power') <x-lucide-power class="w-4 h-4 shrink-0" /> @break
                            @case('users') <x-lucide-users class="w-4 h-4 shrink-0" /> @break
                            @case('shield-check') <x-lucide-shield-check class="w-4 h-4 shrink-0" /> @break
                            @case('building') <x-lucide-building class="w-4 h-4 shrink-0" /> @break
                            @default <x-lucide-circle class="w-4 h-4 shrink-0" />
                        @endswitch
                    @endif
                    {{ $action['label'] }}
                </a>
            @elseif ($action['type'] === 'form')
                <form
                    method="POST"
                    action="{{ $action['action'] }}"
                    @if (!empty($action['confirm']))
                        onsubmit="return confirm('{{ addslashes($action['confirm']) }}')"
                    @endif
                >
                    @csrf
                    @if (!empty($action['method']))
                        @method($action['method'])
                    @endif
                    <button
                        type="submit"
                        class="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-surface-muted transition-colors {{ ($action['variant'] ?? 'default') === 'danger' ? 'text-danger-text hover:bg-danger-bg' : 'text-ink-900' }}"
                    >
                        @if (!empty($action['icon']))
                            @switch($action['icon'])
                                @case('trash-2') <x-lucide-trash-2 class="w-4 h-4 shrink-0" /> @break
                                @case('power') <x-lucide-power class="w-4 h-4 shrink-0" /> @break
                                @case('pencil') <x-lucide-pencil class="w-4 h-4 shrink-0" /> @break
                                @case('eye') <x-lucide-eye class="w-4 h-4 shrink-0" /> @break
                                @default <x-lucide-circle class="w-4 h-4 shrink-0" />
                            @endswitch
                        @endif
                        {{ $action['label'] }}
                    </button>
                </form>
            @endif
        @endforeach
    </div>
</div>
