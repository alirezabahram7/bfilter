<?php
namespace BFilters;


trait HasFilter
{
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }
}
