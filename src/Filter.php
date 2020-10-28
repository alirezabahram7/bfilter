<?php

namespace BFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Filter extends MakeFilter
{
    protected $request;
    protected $builder;
    protected $relations = [];
    protected $sumField = null;

    /**
     * PostFilter constructor.
     * @param Request $request
     * @throws \JsonException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->getFilters();
    }

    /**
     * @param $builder
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

        if (! empty($this->limit)){
            $entries = $entries->limit($this->limit);
        }

        if (! empty($this->offset)) {
            $entries = $entries->offset($this->offset);
        }

        return array($entries, $count, $sum);
    }

    /**
     * @param Builder $entries
     *
     * @return Builder
     */
    protected function applyFilters(Builder $entries): Builder
    {
        foreach ($$this->filters as $filters) {
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
                    if (!$this->applyRelations($query, $item, $filterKey === 0)) {
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
        return $query->where($item->field, $item->op, $item->value);
    }

    protected function orWhere($query, $item)
    {
        return $query->orWhere($item->field, $item->op, $item->value);
    }

    /**
     * @param object $filter
     *
     * @return object $filter
     */
    private function prepareFilter(object $filter)
    {
        if ($filter->op == 'like') {
            $filter->value = '%' . $filter->value . '%';
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
     * @param bool $isWhere
     *
     * @return mixed
     */
    protected function applyRelations(&$query, $item, $isWhere = true)
    {
        if (!$this->hasRelation()) {
            return false;
        }
        foreach ($this->relations as $relationName => $params) {
            if (($relationKey = $this->hasRelationField($params, $item)) !== false) {
                $item = $this->setRelationKey($item, $relationKey);
                $query = $this->filterRelation($query, $item, $relationName, $isWhere);
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
     * @param $item 'filterObject'
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
     * @param $sortData
     * @param $entries
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
    public function filterRelation($entries, $filter, $relation, $isWhere): Builder
    {
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
    protected function hasFilter(): bool
    {
        return !empty($this->filters);
    }

    /**
     * @return bool
     */
    protected function hasSort(){
        return !empty($this->sortData);
    }


    /**
     * @return array
     * @throws \JsonException
     */
    public function getFilters(): array
    {
        $requestData = \json_decode(
            $this->request->get('filter', (object)[]),
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->sortData = data_get($requestData, 'sort', null);
        $this->offset = data_get($requestData, 'page.offset', null);
        $this->limit = data_get($requestData, 'page.limit', null);
        $this->filters = data_get($requestData, 'filters', null);
    }
}
