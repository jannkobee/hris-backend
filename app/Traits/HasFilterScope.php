<?php

namespace App\Traits;

trait HasFilterScope
{
    public function scopeFilter($query)
    {
        $search = request('search');

        $query->when($search, function ($query) use ($search) {
            $columns = property_exists($this, 'filterable') ? $this->filterable : [];

            if (empty($columns)) {
                return;
            }

            $query->where(function ($query) use ($search, $columns) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'LIKE', "%{$search}%");
                }
            });
        });
    }
}
