<?php
/**
 * Created by PhpStorm.
 * User: Alireza
 * Date: 6/26/2019
 * Time: 5:28 PM
 */
namespace BFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class Filter
{
    protected $request;
    protected $builder;
    protected $filters = [];
    protected $relations = [];
    protected $sumField = null;

    /**
     * PostFilter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
        $sum = 0 ;

        if($this->sumField){
            $sum = $entries->sum($this->sumField);
        }

        if ($this->hasFilter()) {
            [$sortData, $offset, $limit, $filters] = $this->getFilters(
                $this->request
            );

            if ($filters) {
                $entries = $this->applyFilters($filters, $this->builder);
            }

            if ($sortData) {
                $entries = $this->sort($sortData, $entries);
            }

            $count = $entries->count();

            if($this->sumField){
                $sum = $entries->sum($this->sumField);
            }

            if ($limit) {
                $entries = $entries->offset($offset)->limit($limit);
            }
        }
        return array($entries,$count,$sum);
    }

    /**
     * @param array $filterList
     * @param  Builder  $entries
     *
     * @return Builder
     */
    protected function applyFilters(array $filterList,Builder $entries): Builder
    {
        foreach ($filterList as $filters) {
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
                    if (! $this->applyRelations($query, $item, $filterKey === 0)){
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

    protected function orWhere($query, $item){
        return $query->orWhere($item->field, $item->op, $item->value);
    }

    /**
     * @param  object  $filter
     *
     * @return object $filter
     */
    private function prepareFilter(object $filter){
        if ($filter->op == 'like') {
            $filter->value = '%'.$filter->value.'%';
        }
        return $filter;
    }

    private function hasRelation(): bool
    {
        return ! empty($this->relations);
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
    protected function applyRelations(& $query, $item, $isWhere = true){
        if (! $this->hasRelation()) {
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
    private function hasRelationField($relationProperties, $filter){
        if (empty($relationProperties)) {
            return false;
        }

        if (! is_array($relationProperties)){
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
    private function setRelationKey($item, $keyName){
        if (! empty($keyName)){
            $item->field = $keyName;
        }
        return $item;
    }



    /**
     * @param $sortData
     * @param $entries
     * @return Builder
     */
    protected function sort($sortData, $entries): Builder
    {
        foreach ($sortData as $sortDatum) {
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
        if (! $isWhere) {
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
        return ! empty($this->request->get('filter', null));
    }


    /**
     * @param Request $request
     * @return array
     */
    protected function getFilters(Request $request): array
    {
        $requestData = \json_decode($request->get('filter', (object)[]));

        $sortData = data_get($requestData, 'sort', null);
        $offset   = data_get($requestData, 'offset', null);
        $limit    = data_get($requestData, 'limit', null);
        $filters  = data_get($requestData, 'filters', null);

        return array($sortData, $offset, $limit, $filters);
    }
}
