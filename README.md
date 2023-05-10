# Bahram Filter
Apply auto filters to Eloquent models. <br>
Using this package, easily apply filters, sorting and pagination to Eloquent models and their relations, with query string parameters. <br>    

### Requirements
php: `^8.0|^8.1` <br>
laravel: `>=8.0` <br><br>

For miner php|Laravel versions use other releases.

### Installation
```
composer require bahram/bfilters
```
### Use HasFilter Trait On your Eloquent Models
Your models should use the `HasFilter` trait:  
```
use BFilters\Traits;

class MyModel extends Eloquent
{
    use HasFilter;
    
    // Add columns which you want to use for "full text serach" in "searchable" or "fillable" array
    protected $searchable = [ 
    'first_name',
    'last_name' 
    ];
}

```
### Create Filter Class
```
php artisan make:filter {name}
```
for example:
```
php artisan make:filter UserFilter
```
### In your Created Filter Class :
```
use Illuminate\Http\Request;

class UserFilter extends Filter
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        
        $this->relations = [
        
            //Add the actual name of the relation function that is defined in the original model
            'relationName1' => [ 
                'searchName1' => 'Original column1 Name in Relation table',
                'searchName2' => 'Original column2 Name in Relation table',
                'searchName3' => 'Original column3 Name in Relation table',

                //if searchName and original column name is the same :
                'Original column4 Name in Relation table'
            ],
          
            'posts' => [
            
                // in this case when you set posted_at as your filter the filter will applied on 'created_at' field of original table
                'posted_at' => 'created_at',
                'title',
                'topic',
            ],
        ];
        
        // set this variabe if you want to have sum of your entries based on a specific column (f.e 'amount')
        $this->sumField = 'amount';
        
        // define valid eager loading relations to prevent loading unwanted data
        $this->validWiths = ['comments', 'tags'];
    }
}
```

### Usage
In controllers :
```
public function index(YourModelFilter $filters): Response
{
    [$entries, $count, $sum] = YourModel::filter($filters);
    return ($entries->get());
}
```

In Api Request Query String :
```
filter:{
        "sort":[
                { "field": "created_at", "dir": "asc" },
                { "field": "first_name", "dir": "desc" }
         ],
         "page":{ "limit": 10, "offset": 0 },
         "filters":[
         
                    //(first_name LIKE '%alireza%' or last_name = '%bahram%') and (mobile LIKE '%9891%')
                    [ 
                       //use "or" for fields in same array & use "and" for fields in different array
                       {"field": "first_name", "op": "like", "value":  "alireza"},
                       {"field": "last_name", "op": "=", "value":  "bahram"}
                    ],
                    
                    //(mobile LIKE '%9891%')
                    [
                        {"field": "mobile", "op": "like", "value": "9891"}
                    ],
                    
                    [
                        //full text search : search a string on columns you set in its model "searchable" or "fillable" arrays
                        {"value" : "al"}
                    ]
         ],
         "with":[
            "comments",
            "tags"
         ]
}
```

###Add Validation Rules

####To validate filters before applying it, add this method to your filter class:
```
public function rules()
{
    return [
        'id' => 'int|required',
        'user_id' => 'exists:users,id'
    ];
}
```


## If you need custom filter on relation : (For example array search in postgresql) :
In Request
```

class MessageFilter extends Filter
{
    public function __construct(Request $request)
    {
        $this->relations = [
            "packages" => [
                "numbers" => function ($query, $filter) {
                    $query->whereRaw("'{$filter->value}' {$filter->op} ANY(numbers)");
                },
            ],
            "line" => [
                "line_number" => "number",
            ],
        ];

        //$this->sumField = null;
        $this->validWiths = ["packages"];

        parent::__construct($request);
    }
}

```


## Query String Samples:

### pagination: per_page=10 :
`?filter={"page":{"limit": 10,"offset": 0}}`

### pagination per_page=20 and (ordered by `id` desc) :
`?filter={"page":{"limit":20,"offset":0},"sort":[{"field":"id","dir":"desc"}]}`

### add a filter : name like john :
`?filter={"page":{"limit":20,"offset":0},"sort":[{"field":"id","dir":"desc"}],"filters":[[{"field":"name","op":"like","value":"john"}]]}`

### (first_name like alireza `or` last name like bahram) `and` (email = alib327@gmail.com)
```
?filer={
        "page":{"limit":20,"offset":0},
        "sort":[{"field":"id","dir":"desc"}],
        "filters":
                    [
                        {"field": "first_name", "op": "like", "value":  "alireza"},
                        {"field": "last_name", "op": "=", "value":  "bahram"}
                    ],
                    [
                        {"field": "email", "op": "=", "value":  "09196649497"},
                    ],
                    
```
### you can use `magic filters` as well:
without using `field`,`op`, and `value`
(email = alib327@gmail.com):
```
?filer={
        "page":{"limit":20,"offset":0},
        "sort":[{"field":"id","dir":"desc"}],
        "filters":
                    [
                        ["email","09196649497"],
                    ],
                    
```
### full Text Search:

```
?filer={
        "page":{"limit":20,"offset":0},
        "sort":[{"field":"id","dir":"desc"}],
        "filters":
                    [
                        {"value":"alireza"},
                    ],
                    
```
##Basic Methods

### Use Make Filter class to make a custom filter on an Api call
For example assume a function within your controller:
```
public function showUserArticles($userId)
    {
        $filters = new MakeFilter();
        $filters->orderBy('posted_at', 'desc');
        $filters->addFilter([
            [
                'field' => 'user_id',
                'op' => '=',
                'value' => $userId
            ]
        ]);
        return Http::get(Put your URL here, [
            'filter' => $filters->toJson()
        ]);
    }
```

### Edit given request filters

```
public function Index(UserFilter $filters)
    {
        $filters->removeFilter('first_name')->removePagination();
    }
```
### "addMagicFilter" method
```
     $filters->removeFilter('first_name')->removePagination()
                ->addMagicFilter([
                                    [
                                        'user_id',
                                         $userId
                                    ]
                                ]);

```

### "addOrder" method
```
$filters->[
                'field' => 'posted_at',
                'dir' => 'desc'
            ];
```

### use "setFilters" method to set multiple filters
```
    $filters->setFilters([
    [
        [
        'field' => 'user_id',
        'op' => '=',
        'value' => $userId
        ],
        [
        'field' => 'user_name',
        'op' => '=',
        'value' => $userName
        ]
    ]]);
```

### use "getFilters" method to get the applied filters as an array
```
$filters->getFilters();
```
### use "getFilter" method to get a specific filter

```
$filters->getFilter('first_name');
```

### use "setWiths" method to eager loads some specific relations of the model
```
$filters->setWiths(['posts, 'comments']);
```

### use "getWiths" method to get relations already set on given filters
```
$filters->getWiths();
```

### use "setPage" method to set pagination on filters

```
$filters->setPage(
                    [
                        "limit" => 20,
                        "offset"=> 100
                    ]
                  );
```

### use "getPage" method to get pagination already applied to the given request

```
$filters->getPage();
```
