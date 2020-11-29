<?php
namespace BFilters\Traits;


trait HasFilter
{
    /**
     * @param $query
     * @param $filters
     * @return mixed
     */
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    /**
     * @param $query
     * @param $term
     * @param bool $beginsWithOr
     * @return mixed
     */
    public function scopeFullSearch($query, $term, $beginsWithOr = false)
    {
        if (isset($this->searchable) or isset($this->fillable)) {
            $columns = isset($this->searchable) ? $this->searchable : $this->fillable;
            foreach ($columns as $c => $column) {
                if ( ! $beginsWithOr and $c == 0) {
                    $query->where($column, 'like', '%' . $term . '%');
                }else{
                    $query->orWhere($column, 'like', '%' . $term . '%');
                }
            }
        }
        return $query;
    }
}
