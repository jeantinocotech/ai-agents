<?php

namespace App\Support;

final class UiButton
{
    /**
     * @param  'primary'|'secondary'|'outline'|'ghost'|'danger'  $variant
     * @param  'xs'|'sm'|'md'|'field'  $size
     */
    public static function classes(string $variant = 'primary', string $size = 'sm'): string
    {
        $base = 'inline-flex items-center justify-center gap-2 font-semibold rounded-lg transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45 box-border';

        $variantClasses = match ($variant) {
            'primary' => 'bg-indigo-600 text-white border border-transparent shadow-sm hover:bg-indigo-700 hover:text-white',
            'secondary' => 'bg-white text-slate-700 border border-slate-300 shadow-sm hover:bg-slate-50 hover:text-slate-700',
            'outline' => 'bg-white text-indigo-800 border border-indigo-300 shadow-sm hover:bg-indigo-50 hover:text-indigo-800',
            'ghost' => 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:text-slate-700',
            'danger' => 'bg-white text-red-700 ring-1 ring-red-300 shadow-sm hover:bg-red-600 hover:text-white hover:ring-red-600',
            default => 'bg-indigo-600 text-white border border-transparent shadow-sm hover:bg-indigo-700 hover:text-white',
        };

        $sizeClasses = match ($size) {
            'field' => 'h-10 min-h-10 max-h-10 text-sm px-4 py-0',
            'xs' => 'text-xs px-3 py-1.5',
            'md' => 'text-sm px-5 py-2.5',
            default => 'text-sm px-4 py-2',
        };

        return trim($base.' '.$variantClasses.' '.$sizeClasses);
    }
}
