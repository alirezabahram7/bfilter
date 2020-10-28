<?php


namespace BFilters\Traits;

trait MakeFilter
{
    private $filters = [];
    private $sort = [];
    private $page = [];

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
        return $this->setSort(
            [
                'field' => $field,
                'dir'   => $dir
            ]
        );
    }

    /**
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * @param  array  $sort
     *
     * @return MakeFilter
     */
    public function setSort(array $sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @return array
     */
    public function getPage(): array
    {
        return $this->page;
    }

    /**
     * @param  array  $page
     *
     * @return MakeFilter
     */
    public function setPage(array $page)
    {
        $this->page = $page;
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
     * @param  array  $filters
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
     * @param  int  $opt
     *
     * @return string
     * @throws \JsonException
     */
    public function encode($opt = 0): string
    {
        $opt |= JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        return \json_encode(
            [
                'filter' => [
                    'filters' => $this->filters,
                    'page'    => $this->page,
                    'sort'    => $this->sort
                ],
            ],
            JSON_THROW_ON_ERROR | $opt
        );
    }
}