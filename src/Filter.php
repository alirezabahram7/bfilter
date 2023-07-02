<?php

namespace BFilters;

use BFilters\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class Filter extends MakeFilter
{
    protected Request $request;
    protected Builder $builder;
    protected array $relations = [];
    protected array $jsonFields = [];
    protected ?string $sumField = null;
    protected array $validWiths = [];

    /**
     * PostFilter constructor.
     *
     * @param Request $request
     * @throws \JsonException
     * @throws ValidationException
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->getParamFilters();
        $this->checkRules();
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

        $sortData = Arr::get($requestData, 'sort', []);
        if (!empty($sortData)) {
            $this->setSortData($sortData);
        }

        $page = Arr::get($requestData, 'page', []);
        if (!empty($page)) {
            $this->setPage($page);
        }

        $filters = Arr::get($requestData, 'filters', []);
        if (!empty($filters)) {
            $this->setFilters($filters);
        }

        $loadWiths = Arr::get($requestData, 'with', []);
        if (!empty($loadWiths)) {
            $this->setWiths($loadWiths);
        }
    }

    public function checkRules(array $rules = [])
    {
        $rules = array_merge($rules, $this->rules());

        $dataFoValidation = array_map(function ($items) {
            return array_map(function ($item) {
                if (isset($item->field))
                    return [$item->field => $item->value];
            }, $items);
        }, $this->getFilters());

        $dataFoValidation = collect($dataFoValidation)->collapse()->collapse()->toArray();

        $validatedData = Validator::make($dataFoValidation, $rules);

        if ($validatedData->fails()) {
            throw new ValidationException($validatedData->errors()->getMessages());
        }
    }

    public function rules(): array
    {
        return [];
    }

    /**
     * @param $builder
     *
     * @return array
     */
    public function apply($builder): array
    {
        $entries = $builder;
        $sum = 0;

        if ($this->hasFilter()) {
            $entries = $this->applyFilters($entries);
        }

        if ($this->hasSort()) {
            $entries = $this->sort($entries);
        }

        $count = $entries->count();

        if ($this->sumField) {
            $sum = $entries->sum($this->sumField);
        }

        if (!empty($this->limit)) {
            $entries = $entries->limit($this->limit);

            if ($this->offset !== null) {
                $entries = $entries->offset($this->offset);
            }
        }

        if ($this->hasWith()) {
            $entries = $this->with($entries);
        }

        $this->builder = $builder;
        return array($entries, $count, $sum);
    }

    /**
     * @return bool
     */
    public function hasFilter(): bool
    {
        return !empty($this->filters);
    }

    /**
     * @param Builder $entries
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
                    $relationQueryFlag = $jsonQueryFlag = false;
                    if (isset($item->field)) {
                        $item = $this->prepareFilter($item);
                        $jsonQueryFlag = $this->applyJsonSearch($query, $item, $filterKey === 0);
                        $relationQueryFlag = $this->applyRelations($query, $item, $filterKey === 0);
                    }
                    if (!$relationQueryFlag and !$jsonQueryFlag) {
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
     * @param object $filter
     *
     * @return object $filter
     */
    private function prepareFilter(object $filter): object
    {
        if ($filter->op === 'like' or $filter->op === 'not like') {
            $filter->value = '%' . $filter->value . '%';
        }

        if ($filter->op === 'is') {
            $filter->op = '=';
        }
        return $filter;
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
    protected function applyRelations(&$query, $item, bool $isWhere = true): bool
    {
        if (!$this->hasRelation()) {
            return false;
        }
        foreach ($this->relations as $relationName => $params) {
            if (($relationKey = $this->hasRelationField($params, $item))
                !== false
            ) {
                $item = $this->setRelationKey($item, $relationKey);
                $query = $this->filterRelation($query, $item, $relationName, $isWhere);
                return true;
            }
        }
        return false;
    }

    protected function applyJsonSearch(&$query, $item, bool $isWhere = true): bool
    {
        if (!$this->hasJsonFields()) {
            return false;
        }
        foreach ($this->jsonFields as $fieldName => $params) {
            if (($jsonKey = $this->hasJsonField($params, $item))
                !== false
            ) {
                $item = $this->setJsonKey($item, $jsonKey);
                $query = $this->filterJson($query, $item, $fieldName, $isWhere);
                return true;
            }
        }
        return false;
    }

    private function hasRelation(): bool
    {
        return !empty($this->relations);
    }

    private function hasJsonFields(): bool
    {
        return !empty($this->jsonFields);
    }

    /**
     * @param $relationProperties
     * @param $filter
     *
     * @return false|int|string
     */
    private function hasRelationField($relationProperties, $filter): bool|int|string
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

    private function hasJsonField($jsonProperties, $filter): bool|int|string
    {
        if (empty($jsonProperties)) {
            return false;
        }

        if (!is_array($jsonProperties)) {
            $jsonProperties = [$jsonProperties];
        }

        return $jsonProperties[$filter->field] ??
            array_search($filter->field, $jsonProperties, true);
    }
    /**
     * @param $item 'filterObject'
     * @param $keyName
     *
     * @return object $filter
     */
    private function setRelationKey($item, $keyName): object
    {
        if (!empty($keyName) && (is_string($keyName) || is_callable($keyName))) {
            $item->field = $keyName;
        }
        return $item;
    }

    private function setJsonKey($item, $keyName): object
    {
        if (!empty($keyName) && (is_string($keyName) || is_callable($keyName))) {
            $item->field = $keyName;
        }
        return $item;
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
    ): Builder
    {
        $callFunction = function ($query) use ($filter) {
            is_callable($filter->field) ? ($filter->field)($query, $filter) : $this->where($query, $filter);
        };
        if (!$isWhere) {
            return $entries->orWhereHas(
                $relation,
                $callFunction
            );
        }
        return $entries->whereHas(
            $relation,
            $callFunction
        );
    }

    public function filterJson($entries, $filter, $jsonFiled, $isWhere): Builder
    {
        $rawQuery = 'JSON_EXTRACT(' . $jsonFiled . ', "$.' . $filter->field . '") = ' . $filter->value;
        if (!$isWhere) {
            return $entries->orWhereRaw($rawQuery);
        }
        return $entries->whereRaw($rawQuery);
    }
    /**
     * @param $query
     * @param $item
     *
     * @return Builder
     */
    protected function where($query, $item): Builder
    {
        if (!isset($item->field) or $item->field === null) {
            return $query->fullSearch($item->value);
        }

        if ($this->isWhereNull($item)) {
            return $this->whereNull($query, $item->field, 'and');
        }

        if ($this->isWhereInOrNotIn($item)) {
            if ($this->isWhereIn($item)) {
                return $this->whereIn($query, $item);
            } else {
                return $this->whereNotIn($query, $item);
            }
        }

        return $query->where($item->field, $item->op, $item->value);
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
     * @param $columns
     * @param string $boolean
     * @param false $not
     *
     * @return mixed
     */
    public function whereNull($query, $columns, string $boolean = 'and', $not = false): Builder
    {
        return $query->whereNull($columns, $boolean, $not);
    }

    /**
     * @param $item
     *
     * @return bool
     */
    protected function isWhereInOrNotIn($item): bool
    {
        return (($item->op === 'in' || $item->op === 'not in') and is_array($item->value));
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
     * @param Builder $query
     * @param $item
     * @param string $boolean
     * @return Builder
     */
    public function whereIn(Builder $query, $item, string $boolean = 'and'): Builder
    {
        return $query->whereIn($item->field, $item->value, $boolean);
    }

    /**
     * @param Builder $query
     * @param $item
     * @param string $boolean
     * @return Builder
     */
    public function whereNotIn(Builder $query, $item, string $boolean = 'and'): Builder
    {
        return $query->whereNotIn($item->field, $item->value, $boolean);
    }

    /**
     * @param $query
     * @param $item
     *
     * @return mixed
     */
    protected function orWhere($query, $item): Builder
    {
        if (!isset($item->field) || $item->field === null) {
            return $query->fullSearch($item->value, true);
        }

        if ($this->isWhereNull($item)) {
            return $this->whereNull($query, $item->field, 'or');
        }

        if ($this->isWhereInOrNotIn($item)) {
            if ($this->isWhereIn($item)) {
                return $this->whereIn($query, $item, 'or');
            }

            return $this->whereNotIn($query, $item, 'or');
        }

        return $query->orWhere($item->field, $item->op, $item->value);
    }

    /**
     * @return bool
     */
    public function hasSort(): bool
    {
        return !empty($this->sortData);
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
     * @return bool
     */
    public function hasWith(): bool
    {
        return !empty($this->withs);
    }

    // if set rule check in filters
    // rules structure must be like laravel validations

    /**
     * @param $entries
     *
     * @return Builder
     */
    protected function with($entries): Builder
    {
        if (!empty($this->validWiths)) {
            foreach ($this->withs as $with) {
                if (in_array($with, $this->validWiths)) {
                    $entries = $entries->with($with);
                }
            }
        }
        return $entries;
    }

    public function toSql(): string
    {
        if (!$this->builder instanceof Builder) {
            throw new \RuntimeException("builder not created.");
        }

        $bindings = $this->builder->getBindings();
        $sql = str_replace('?', '%s', $this->builder->toSql());
        return vsprintf($sql, $bindings);
    }
}
