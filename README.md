# Bahram Filter
apply auto filters in Eloquent. <br>
with this package, easily apply filters, sort and pagination on Eloquent models and their relations. <br>
with query string parameters. <br>    

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
    
    // Define fields for full text serach as "searchable" or "fillable"
    protected $searchable = [ 
    'first_name',
    'last_name' 
    ];
}

```
### Create Filter Class
```
php artisan make:filter {name}

for example: 
php artisan make:filter UserFilter


```
### In your Created Filter Class
```
use Illuminate\Http\Request;

class UserFilter extends Filter
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        
        $this->relations = [
            //actual name of relation defined in original model
            'relationName1' => [ 
                'searchName1' => 'Original column1 Name in Relation table',
                'searchName2' => 'Original column2 Name in Relation table',
                'searchName3' => 'Original column3 Name in Relation table',

                //if searchName and original column name is same
                'Original column4 Name in Relation table'
            ],
          
            'posts' => [
                // in this case when you set posted_at as your filter the filter will applied on 'created_at' field of original table
                'posted_at' => 'created_at',
                'title',
                'topic',
            ],
        ];
        // you set this variabe if you want to have sum of your entries based of a specific field (f.e id here)
        $this->sumField = 'id';
        // define valid eager loading relationships to protect loading unwanted data
        $this->validWiths = ['comments', 'tags'];
    }
}
```

### Usage
In controllers 
```
public function index(YourModelFilter $filters): Response
{
    [$entries, $count, $sum] = YourModel::filter($filters);
}
```

In Requests
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
                    [
                        {"field": "mobile", "op": "like", "value": "9891"}
                    ],
                    [
                        //full search : search a string in fields you set in its model "searchable" or "fillable" arrays
                        {"value" : "al"}
                    ]
         ],
         "with":[
            "comments",
            "tags"
         ]
}
```

Add Validation Rules
####To validate filters before applying it, add this method to your filter file:
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
?filter={"page":{"limit": 10,"offset": 0}}

### pagination per_page=20 and (ordered by `id` desc) :
?filter={"page":{"limit":20,"offset":0},"sort":[{"field":"id","dir":"desc"}]}

### add a filter : name like john :
?filter={"page":{"limit":20,"offset":0},"sort":[{"field":"id","dir":"desc"}],"filters":[[{"field":"name","op":"like","value":"john"}]]}


