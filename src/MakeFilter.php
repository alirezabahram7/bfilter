<?php


namespace BFilters;

use Illuminate\Contracts\Support;

class MakeFilter implements \Jsonable
{
    protected $filters = [];
    protected $sortData = [];
    protected $offset = 0;
    protected $limit = null;

    /**
     * @param array $filters
     *
     * @return $this
     */
    public function addFilter(array $filters)
    {
        $this->filters[] = $filters;
        return $this;
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
                'dir' => $dir
            ]
        );
    }

    public function addOrder($sortData)
    {
        $this->sortData[] = $sortData;
        return $this;
    }

    /**
     * @return array
     */
    public function getSortData(): array
    {
        return $this->sortData;
    }

    /**
     * @param array $sortData
     *
     * @return MakeFilter
     */
    public function setSortData(array $sortData)
    {
        $this->sortData = $sortData;
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
     * @param array $page
     *
     * @return MakeFilter
     */
    public function setPage(array $page)
    {
        if (!empty($page['limit'])) {
            $this->limit = $page['limit'];
        }

        if (!empty($page['offset'])) {
            $this->offset = $page['offset'];
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     *
     * @return MakeFilter
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Encode a value as JSON.
     *
     * @param int $options
     * @return string
     * @throws \JsonException
     */
    public function toJson($options = 0)
    {
        $options |= JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        return \json_encode(
            [
                'filter' => [
                    'filters' => $this->filters,
                    'page' => $this->page,
                    'sort' => $this->sortData
                ],
            ],
            JSON_THROW_ON_ERROR | $options
        );
    }
}