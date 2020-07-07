<?php


namespace Behamin\Bfilters\Traits;


trait bfilters
{
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }
}
