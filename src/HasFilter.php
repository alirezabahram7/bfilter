<?php
namespace BFilters\Traits;


trait HasFilter
{
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }
}
