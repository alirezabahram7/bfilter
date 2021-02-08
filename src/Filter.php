<?php

namespace BFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Filter extends MakeFilter
{
    protected $request;
    protected $builder;
    protected $relations = [];
    protected $sumField = null;

    /**
     * PostFilter constructor.
     *
     * @param  Request  $request
     *
     * @throws \JsonException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->getParamFilters();
    }

    /**
     * @param $builder
     *
     * @return array
     */
    public function apply($builder): array
    {
        $this->builder = $builder;
        $entries = $builder;
        $count = $entries->count();
        $sum = 0;

        if ($this->sumField) {
            $sum = $entries->sum($this->sumField);
        }

        if ($this->hasFilter()) {
            $entries = $this->applyFilters($this->builder);
        }

        if ($this->hasSort()) {
            $entries = $this->sort($entries);
        }

        $count = $entries->count();

        if ($this->sumField) {
            $sum = $entries->sum($this->sumField);
        }

        if (! empty($this->limit)) {
            $entries = $entries->limit($this->limit);

            if ( $this->offset !== null ) {
                $entries = $entries->offset($this->offset);
            }
        }

        return array($entries, $count, $sum);
    }

    /**
     * @param  Builder  $entries
     *
     * @return Builder
     */
    protected function applyFilters(Builder $entries): Builder
    {
        foreach ($this->filters as $filters) {
            $entries = $this->applyFilter($filters, $entries);
        }

        return $entries;
    }

    /**
     * @param $filters
     * @param $entries
     *
     * @return Builder
     */
    protected function applyFilter($filters, $entries): Builder
    {
        return $entries->where(
            function ($query) use ($filters) {
                foreach ($filters as $filterKey => $item) {
                    $item = $this->prepareFilter($item);
                    if (!$this->applyRelations(
                        $query,
                        $item,
                        $filterKey === 0
                    )
                    ) {
                        if ($filterKey === 0) {
                            $this->where($query, $item);
                        } else {
                            $this->OrWhere($query, $item);
                        }
                    }
                }
            }
        );
    }

    /**
     * @param $query
     * @param $item
     *
     * @return Builder
     */
    protected function where($query, $item): Builder
    {
        if ($this->isWhereNull($item)) {
            return $this->whereNull($query, $item->field, 'and');
        }

        if ($this->isWhereIn($item)) {
            return $this->whereIn($query, $item);
        }

        if(!isset($item->field) or $item->field === null){
            return $query->fullSearch($item->value);
        }

        return $query->where($item->field, $item->op, $item->value);
    }

    /**
     * @param $query
     * @param $item
     *
     * @return mixed
     */
    protected function orWhere($query, $item)
    {
        if ($this->isWhereNull($item)) {
            return $this->whereNull($query, $item->field, 'or');
        }

        if ($this->isWhereIn($item)) {
            return $this->whereIn($query, $item);
        }

        if(!isset($item->field) or $item->field == null){
            return $query->fullSearch($item->value,true);
        }

        return $query->orWhere($item->field, $item->op, $item->value);
    }

    /**
     * @param $item
     *
     * @return bool
     */
    protected function isWhereIn($item): bool
    {
        return ($item->op === 'in' and is_array($item->value));
    }

    /**
     * @param $item
     *
     * @return bool
     */
    protected function isWhereNull($item): bool
    {
        return ($item->op === 'is' and $item->value === null);
    }

    /**
     * @param $query
     * @param $item
     *
     * @return mixed
     */
    public function whereIn($query, $item)
    {
        return $query->whereIn($item->field, $item->value);
    }

    /**
     * @param $query
     * @param $columns
     * @param  string  $boolean
     * @param  false  $not
     *
     * @return mixed
     */
    public function whereNull($query, $columns, $boolean = 'and', $not = false)
    {
        return $query->whereNull($columns, $boolean, $not);
    }

    /**
     * @param  object  $filter
     *
     * @return object $filter
     */
    private function prepareFilter(object $filter)
    {
        if ($filter->op === 'like' or $filter->op === 'not like') {
            $filter->value = '%'.$filter->value.'%';
        }

        if ($filter->op === 'is') {
            $filter->op = '=';
        }
        return $filter;
    }

    private function hasRelation(): bool
    {
        return !empty($this->relations);
    }

    /**
     * relations = [
     *  'relation_name' => [
     *      'destination_key' => 'origin_key'
     *   ]
     * ]
     *
     * @param $query
     * @param $item
     * @param  bool  $isWhere
     *
     * @return mixed
     */
    protected function applyRelations(&$query, $item, $isWhere = true)
    {
        if (!$this->hasRelation()) {
            return false;
        }
        foreach ($this->relations as $relationName => $params) {
            if (($relationKey = $this->hasRelationField($params, $item))
                !== false
            ) {
                $item = $this->setRelationKey($item, $relationKey);
                $query = $this->filterRelation(
                    $query,
                    $item,
                    $relationName,
                    $isWhere
                );
                return true;
            }
        }
        return false;
    }

    /**
     * @param $relationProperties
     * @param $filter
     *
     * @return false|int|string
     */
    private function hasRelationField($relationProperties, $filter)
    {
        if (empty($relationProperties)) {
            return false;
        }

        if (!is_array($relationProperties)) {
            $relationProperties = [$relationProperties];
        }

        return $relationProperties[$filter->field] ??
            array_search($filter->field, $relationProperties, true);
    }

    /**
     * @param $item  'filterObject'
     * @param $keyName
     *
     * @return object $filter
     */
    private function setRelationKey($item, $keyName)
    {
        if (!empty($keyName)) {
            $item->field = $keyName;
        }
        return $item;
    }


    /**
     * @param $entries
     *
     * @return Builder
     */
    protected function sort($entries): Builder
    {
        foreach ($this->sortData as $sortDatum) {
            $field = $sortDatum->field;
            $dir = $sortDatum->dir;
            $entries = $entries->orderBy($field, $dir);
        }
        return $entries;
    }

    /**
     * @param $entries
     * @param $filter
     * @param $relation
     * @param $isWhere
     *
     * @return Builder
     */
    public function filterRelation(
        $entries,
        $filter,
        $relation,
        $isWhere
    ): Builder {
        if (!$isWhere) {
            return $entries->orWhereHas(
                $relation,
                function ($query) use ($filter) {
                    $this->where($query, $filter);
                }
            );
        }
        return $entries->whereHas(
            $relation,
            function ($query) use ($filter) {
                $this->where($query, $filter);
            }
        );
    }


    /**
     * @return bool
     */
    public function hasFilter(): bool
    {
        return !empty($this->filters);
    }

    /**
     * @return bool
     */
    public function hasSort(): bool
    {
        return !empty($this->sortData);
    }


    /**
     * @throws \JsonException
     */
    public function getParamFilters(): void
    {

        $requestData = \json_decode(
            $this->request->get('filter', '[]'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $sortData =  Arr::get($requestData, 'sort', []);
        if (! empty($sortData)) {
            $this->setSortData($sortData);
        }

        $page = Arr::get($requestData, 'page', []);
        if (! empty($page)) {
            $this->setPage($page);
        }

        $filters = Arr::get($requestData, 'filters', []);
        if (! empty($filters)) {
            $this->setFilters($filters);
        }
    }
}
