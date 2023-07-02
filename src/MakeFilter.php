<?php


namespace BFilters;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use JetBrains\PhpStorm\ArrayShape;

class MakeFilter implements Jsonable
{
    protected array $filters = [];
    protected array $sortData = [];
    protected array $paginationData = [];
    protected ?int $offset = null;
    protected ?int $limit = null;
    protected array $withs = [];

    /**
     * @param array $filters
     *
     * @return $this
     */
    public function addFilter(array $filters): MakeFilter
    {
        $filters = $this->prepareAddFilter($filters);
        $this->filters[] = $filters;

        return $this;
    }

    /**
     * @param array $filter
     * @return $this
     */
    public function addMagicFilter(array $filter): MakeFilter
    {
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
        foreach ($this->filters as $mainKey => &$filters) {
            foreach ($filters as $key => $filter) {
                if ($filter->field === $field) {
                    unset($filters[$key]);
                }
            }
            if (empty($filters)) {
                unset($this->filters[$mainKey]);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function removePagination(): makeFilter
    {
        unset($this->paginationData);

        return $this;
    }


    /**
     * @param $filters
     *
     * @return array
     */
    public function prepareAddFilter($filters): array
    {
        $keys = ['field', 'op', 'value'];
        $constFilters = $filters;
        foreach ($filters as &$filter) {
            $filter = Arr::only((array)$filter, $keys);
            if (count($filter) === 2 or (count($filter) === 1 and !isset($filter['value']))) {
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
    public function orderBy($field, $dir): MakeFilter
    {
        return $this->addOrder(
            [
                'field' => $field,
                'dir' => $dir
            ]
        );
    }

    /**
     * @param $sortData
     *
     * @return $this
     */
    public function addOrder($sortData): MakeFilter
    {
        $sortData = $this->prepareAddOrder($sortData);
        $this->sortData[] = $sortData;

        return $this;
    }


    /**
     * @param $sortData
     * @return object
     */
    public function prepareAddOrder($sortData): object
    {
        $constSortData = $sortData;
        $sortData = Arr::only($sortData, ['field', 'dir']);
        if (count($sortData) !== 2) {
            throw new \RuntimeException(
                'order data wrong.' . "\n" . print_r($constSortData, true)
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
     * @param array $sortDataList
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

    public function getPaginationData(): array
    {
        return $this->paginationData;
    }

    /**
     * @return array
     */
    #[ArrayShape(['limit' => "int|null", 'offset' => "int|null"])]
    public function getPage(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset
        ];
    }

    /**
     * @param array $page
     *
     * @return MakeFilter
     */
    public function setPage(array $page): MakeFilter
    {
        $this->paginationData = $page;
        if (!empty($page['limit'])) {
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
     * @param array $loadWiths
     *
     * @return MakeFilter
     */
    public function setWiths(array $loadWiths): MakeFilter
    {
        $this->withs = (array)$loadWiths;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return object|null
     */
    public function getFilter(string $field): ?object
    {
        foreach ($this->filters as $filters) {
            foreach ($filters as $filter) {
                if ($filter->field === $field) {
                    return $filter;
                }
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filtersList
     *
     * @return MakeFilter
     */
    public function setFilters(array $filtersList): MakeFilter
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
    public function offset($offset): MakeFilter
    {
        $this->offset = (int)$offset;

        return $this;
    }

    /**
     * @param $limit
     *
     * @return MakeFilter
     */
    public function limit($limit): MakeFilter
    {
        $this->limit = (int)$limit;

        return $this;
    }


    /**
     * Encode a value as JSON.
     *
     * @param int $options
     *
     * @return string
     * @throws \JsonException
     */
    public function toJson(int $options = 0): string
    {
        $options |= JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        $data = [];

        if (!empty($this->filters)) {
            $data['filters'] = $this->filters;
        }
        if (!empty($this->paginationData)) {
            $data['page'] = $this->paginationData;
        }

        if (!empty($this->sortData)) {
            $data['sort'] = $this->sortData;
        }

        if (!empty($this->getWiths())) {
            $data['with'] = $this->withs;
        }

        return json_encode(
            $data,
            JSON_THROW_ON_ERROR | $options
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
