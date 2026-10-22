<?php

namespace App\Services;

use App\Models\Portal\CMSMenuItems;
use Illuminate\Support\Collection;

class MenuBuilder
{
    protected $items;

    public function __construct(string $menuName)
    {
        $this->items = CMSMenuItems::with('children')
            ->whereHas('menu', function ($query) use ($menuName) {
                $query->where('name', $menuName);
            })
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();
    }

    public function build(): Collection
    {
        return $this->items;
    }
}
