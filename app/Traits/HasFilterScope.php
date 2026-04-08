<?php

namespace App\Traits;

trait HasFilterScope
{
    public function scopeFilter($query)
    {
        $search = trim((string) request('search', ''));

        if ($search === '') {
            return $query;
        }

        $columns = property_exists($this, 'filterable') ? $this->filterable : [];
        if (empty($columns)) {
            return $query;
        }

        return $query->where(function ($q) use ($search, $columns) {
            foreach ($columns as $column) {
                // Relation column: user.first_name
                if (str_contains($column, '.')) {
                    [$relation, $relColumn] = explode('.', $column, 2);

                    $q->orWhereHas($relation, function ($rq) use ($relColumn, $search) {
                        $rq->where($relColumn, 'LIKE', "%{$search}%");
                    });

                    continue;
                }

                // Direct column: employee_no
                $q->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }
}
