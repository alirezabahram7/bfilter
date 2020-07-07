<?php
/**
 * Created by PhpStorm.
 * User: Alireza
 * Date: 6/26/2019
 * Time: 5:28 PM
 */

namespace Behamin\Bfilters;

use Illuminate\Http\Request;

class Filter
{
    protected $request, $builder;
    protected $filters = [];
    protected $relation = '';
    protected $additional_properties = [];
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
    public function apply($builder)
    {
        $this->builder = $builder;
        $entries = $builder;
        $count = $entries->count();
        $sum = 0 ;

        if($this->sumField){
            $sum = $entries->sum($this->sumField);
        }

        if (isset($this->request->all()['filter'])) {
            list($sortData, $offset, $limit, $filters) = $this->getFilters($this->request);

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
     * @param Request $request
     * @return array
     */
    protected function getFilters(Request $request): array
    {
        $requestData = json_decode($request->all()['filter']);

        $sortData = isset($requestData->sort) ? $requestData->sort : null;

        $pageLimit = isset($requestData->page) ? $requestData->page : null;
        $offset = isset($pageLimit->offset) ? $pageLimit->offset : null;
        $limit = isset($pageLimit->limit) ? $pageLimit->limit : null;

        $filters = isset($requestData->filters) ? $requestData->filters : null;

        return array($sortData, $offset, $limit, $filters);
    }

    /**
     * @param $filters
     * @param $entries
     * @param $relation
     * @return mixed
     */
    protected function applyFilters($filters, $entries)
    {
        foreach ($filters as $filter) {
            $entries = $entries->where(
                function ($query) use ($filter) {
                    foreach ($filter as $i => $item) {
                        $field = $item->field;
                        $value = $item->value;
                        $op = $item->op;
                        if ($op == 'like') {
                            $value = '%' . $value . '%';
                        }
                        if (in_array($field, $this->additional_properties)) {
                            $key =  array_search($field,$this->additional_properties);
                            $query = $this->filterRelation($query, $field, $op, $value, $i,$key);
                        } else {
                            if ($i == 0) {
                                $query = $query->where($field, $op, $value);
                            } else {
                                $query = $query->orWhere($field, $op, $value);
                            }
                        }
                    }
                }
            );
        }

        return $entries;
    }

    /**
     * @param $sortData
     * @param $entries
     * @return mixed
     */
    protected function sort($sortData, $entries)
    {
        foreach ($sortData as $sortDatum) {
            $field = $sortDatum->field;
            $dir = $sortDatum->dir;

            $entries = $entries->orderBy($field, $dir);
        }
        return $entries;
    }

    /**
     * @param $relation
     * @param $field
     * @param $value
     * @param $index
     * @return mixed
     */
    public function filterRelation($entries, $field, $op, $value, $index,$key)
    {
        $field = is_numeric($key) ? $field : $key;
        $relation = $this->relation;
        if ($index > 0) {
            return $entries->orWhereHas(
                $relation,
                function ($query) use ($field, $op, $value) {
                    $query->where($field, $op, $value);
                }
            );
        }
        return $entries->whereHas(
            $relation,
            function ($query) use ($field, $op, $value) {
                $query->where($field, $op, $value);
            }
        );
    }


}
