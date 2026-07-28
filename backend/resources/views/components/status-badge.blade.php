@props([
    'status'  => '',
    'map'     => [],   // optional override: ['active' => 'success', ...]
])

@php
    $s = strtolower(trim($status));

    $variant = $map[$s] ?? match (true) {
        in_array($s, ['active', 'approved', 'completed', 'delivered', 'success']) => 'success',
        in_array($s, ['inactive', 'rejected', 'cancelled', 'failed'])             => 'danger',
        in_array($s, ['pending', 'submitted', 'open', 'flagged'])                 => 'warning',
        in_array($s, ['info', 'processing'])                                      => 'info',
        default                                                                   => 'neutral',
    };
@endphp

<span class="badge badge-{{ $variant }}">
    @if($variant === 'success')
        <x-lucide-check-circle class="w-3 h-3" />
    @elseif($variant === 'danger')
        <x-lucide-x-circle class="w-3 h-3" />
    @elseif($variant === 'warning')
        <x-lucide-clock class="w-3 h-3" />
    @elseif($variant === 'info')
        <x-lucide-info class="w-3 h-3" />
    @endif
    {{ ucfirst($status) }}
</span>
