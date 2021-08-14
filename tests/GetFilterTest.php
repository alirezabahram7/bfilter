<?php


namespace BFilters\Tests;


class GetFilterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->filterCompleteCasesJson = '{
      "page": {
        "limit": 100,
        "offset": 0
      },
      "sort": [
        {
          "field": "id",
          "dir": "desc"
        }
      ],
      "filters": [
        [
          {
            "field": "first_name",
            "op": "like",
            "value": "yourname"
          }
        ],
        [
          {
            "field": "last_name",
            "op": "like",
            "value": "your_lastname"
          }
        ],
        [
          {
            "field": "mobile",
            "op": "like",
            "value": "09111111111"
          }
        ]
      ]
    }';
        parent::setUp();
    }

    public function test_get_filter(): void
    {
        $filter = $this->filter->getFilter('mobile');
        $this->assertObjectHasAttribute('field', $filter);
    }

    public function test_get_empty_filter(): void
    {
        $filter = $this->filter->getFilter('anyfilter');
        $this->assertEquals(null, $filter);
    }
}