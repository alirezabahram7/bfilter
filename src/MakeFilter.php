<?php


namespace BFilters;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;

class MakeFilter implements Jsonable
{
    protected $filters = [];
    protected $sortData = [];
    protected $offset = null;
    protected $limit = null;
    protected $withs = [];

    /**
     * @param  array  $filters
     *
     * @return $this
     */
    public function addFilter(array $filters)
    {
        $filters = $this->prepareAddFilter($filters);
        $this->filters[] = $filters;
        return $this;
    }

    public function addMagicFilter(array $filter){
        $filter = ['field' => ($key = key($filter)), 'value' => $filter[$key], 'op' => '='];
        return $this->addFilter([$filter]);
    }

    /**
     * @param string field
     *
     * @return $this
     */
    public function removeFilter(string $field): MakeFilter
    {
        foreach ($this->filters as $mainKey => &$filters){
            foreach ($filters as $key => $filter) {
                if ($filter->field === $field) {
                    unset($filters[$key]);
                }
            }
            if (empty($filters)){
                unset($this->filters[$mainKey]);
            }
        }
        return $this;
    }

    /**
     * @param $filters
     *
     * @return mixed
     */
    public function prepareAddFilter($filters)
    {
        $keys = ['field', 'op', 'value'];
        $constFilters = $filters;
        foreach ($filters as &$filter) {
            $filter = Arr::only((array)$filter, $keys);
            if (count($filter) !== 3) {
                throw new \RuntimeException(
                    'filter is wrong.' . "\n"
                    . 'filter muse have these keys: ' . implode(', ', $keys) .
                    ".\n\r while " . print_r($constFilters, true)
                );
            }
            $filter = (object)$filter;
        }
        return $filters;
    }

    /**
     * @param $field
     * @param $dir
     *
     * @return MakeFilter
     */
    public function orderBy($field, $dir)
    {
        return $this->addOrder(
            [
                'field' => $field,
                'dir'   => $dir
            ]
        );
    }

    /**
     * @param $sortData
     *
     * @return $this
     */
    public function addOrder($sortData)
    {
        $sortData = $this->prepareAddOrder($sortData);
        $this->sortData[] = $sortData;
        return $this;
    }

    /**
     * @param $sortData
     *
     * @return array
     */
    public function prepareAddOrder($sortData)
    {
        $constSortData = $sortData;
        $sortData = Arr::only($sortData, ['field', 'dir']);
        if (count($sortData) !== 2) {
            throw new \RuntimeException(
                'order data wrong.'."\n".print_r($constSortData, true)
            );
        }
        return (object)$sortData;
    }

    /**
     * @return array
     */
    public function getSortData(): array
    {
        return $this->sortData;
    }

    /**
     * @param  array  $sortDataList
     *
     * @return MakeFilter
     */
    public function setSortData(array $sortDataList): MakeFilter
    {
        foreach ($sortDataList as $sortData) {
            $this->addOrder($sortData);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getPage(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset
        ];
    }

    /**
     * @param  array  $page
     *
     * @return MakeFilter
     */
    public function setPage(array $page)
    {
        if (! empty($page['limit'])) {
            $this->limit($page['limit']);
        }

        if (isset($page['offset'])) {
            $this->offset($page['offset']);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getWiths(): array
    {
        return $this->withs;
    }

    /**
     * @param  array  $loadWiths
     *
     * @return MakeFilter
     */
    public function setWiths(array $loadWiths)
    {
        $this->withs = (array)$loadWiths;
        return $this;
    }

    /**
     * @param string $field
     *
     * @return array
     */
    public function getFilter(string $field): array
    {
        foreach ($this->filters as $filters){
            foreach ($filters as $filter) {
                if ($filter->field === $field) {
                    return $filter;
                }
            }
        }
        return [];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param  array  $filtersList
     *
     * @return MakeFilter
     */
    public function setFilters(array $filtersList)
    {
        foreach ($filtersList as $filters) {
            $this->addFilter($filters);
        }
        return $this;
    }


    /**
     * @param $offset
     *
     * @return MakeFilter
     */
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * @param $limit
     *
     * @return MakeFilter
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }


    /**
     * Encode a value as JSON.
     *
     * @param  int  $options
     *
     * @return string
     * @throws \JsonException
     */
    public function toJson($options = 0)
    {
        $options |= JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $data = [];

        if (!empty($this->filters)) {
            $data['filters'] = $this->filters;
        }
        if (!empty($page = $this->getPage())) {
            $data['page'] = $page;
        }

        if (!empty($this->sortData)) {
            $data['sort'] = $this->sortData;
        }

        return json_encode($data, JSON_THROW_ON_ERROR | $options
        );
    }

    /**
     * @return string
     * @throws \JsonException
     */
    public function __toString()
    {
        return $this->toJson();
    }
}