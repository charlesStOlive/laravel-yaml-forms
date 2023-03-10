# Test package for INERTIA, VUE, Tailwind  &  yaml 


This package should not used in production



## Installation

You can install the package via composer:

```bash
composer require waka/laravel_yamlforms
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="yamlforms-config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage
This package allow to load YAML directly for forms. 
### Directory structure

```
app
|-Models
    |-yaml
        |-{ModelName}.yaml
    {ModelName}.php
```

### yaml structure
* grid : config for grid presentation (index)
* form: config for form ( update/create)
* attributs: (fields list)

```yaml

grid:
    pagination: 15
    defaultOrder: "-order_column"
form:
    url: /bo/tableau/{id}'
    formClass: flex flex-wrap
attributs:
    id: 
        field: 
            hidden: true
        label: ID
        column: 
            hidden: true
        
    name: 
        label: Nom du tableau
        ordorable: true
        searchable: true
        field:  
            required: true
            class: w-full md:w-1/2
        column:
            class: font-bold
    slug: 
        label: Slug/Code du tableau
        ordorable: true
        searchable: true
        field:  
            class: w-full md:w-1/2
            required: [unique, required]
            options:
                preset: name
    tableauTags:
        label: Tags
        field:  
            class: w-full
            type: tagList 
            optionsData: listTags 
            staticOptionsData: staticListTags
            valueFrom: tagsPluckId
            valueProp: id
            label: name 
            mode: tags 
            closeOnSelect: false
        column:
            valueFrom: joinTags 

    description:
        label: descriptions
        field:
            type: textArea
            required: [required,max:500]
        column: false
    order_column:
        label: Ordre
        field: false
        ordorable: true
        context: [create, update]
    image:
        label: image
        type: fileUploader
        mode: image
        context: create
        column:
            valueFrom: thumb
        field:  
            valueFrom: imageBigThumb   
            class: w-96 mx-auto
    painted_at: 
        label: Paint le 
        mode: date 
        format: short
        required: ['date']
        ordorable: true
    metas:
        label: Meta Données
        type: nestedform 
        blocClass: m-1 p-1 bg-red-500  
        class: w-full 
        nestedClass: w-full flex flex-wrap 
        required: ['array']
        column: false
        attributs:
            propa:
                label: Propd A
                column: false
                type: label
                field: 
                    class: w-1/3
            propb:
                label: Propd B
                column: false
                type: label
                field: 
                    class: w-1/3
            propc:
                label: Propd C
                column: false
                type: label
                context: ['create']
                field: 
                    class: w-1/3




```
### Model
```php
use Waka\YamlForms\YamlFormsTrait;
use Waka\YamlForms\YamlFormsInterface;


class Tableau extends Model implements YamlFormsInterface
{
    use YamlFormsTrait;
    ...
}

```

### Controller
```php

//in this exemple we are using spaties querybuilder 
use App\Models\Tableau;
use Spatie\QueryBuilder\QueryBuilder;

class TableauController extends Controller
{
    private $orderInverted = true;

    public function index()
    {
        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query->orWhere('name', 'LIKE', "%{$value}%");
                    $query->orWhere('slug', 'LIKE', "%{$value}%");
                    $query->orWhere('description', 'LIKE', "%{$value}%");
                });
            });
        });
        // logger(Tableau::extractFields($columnsConfig));

        $columnsConfig = Tableau::getColumnsConfig();
        $columnsMeta = Tableau::getColumnsMeta();
        

        $tableaux = QueryBuilder::for(Tableau::class)
        ->defaultSort($columnsConfig['defaultOrder'])
        ->allowedSorts(['id', 'painted_at','name', 'order_column',  'slug', 'updated_at'])
        ->allowedFilters([$globalSearch])
        ->paginate($columnsConfig['pagination'])
        ->withQueryString()
        ->through([Tableau::class, 'dataYamlColumnTransformer']);
        
        $inertiaData = [
            'tableaux' => $tableaux,
            'metas' => $columnsMeta,
            'columnsConfig' => $columnsConfig,
            'sort' => Request::all('sort'),
            'filter' => Request::all('filter'),
        ];

        return Inertia::render('Tableaux/Index', $inertiaData);
    }

    public function edit(Tableau $tableau)
    {
        //logger('edit');
        $inertiaData = [
            'formData' => $tableau->dataYamlFieldsTransformer(),
            'config' => $tableau->getModelFormConfig()
        ];
        //logger($tableau->getModelFormConfig()['fields']);
        return Inertia::render('Tableaux/Edit', $inertiaData);
    }


    public function create() {
        $inertiaData = [
            'formData' => Tableau::getEmptyForm(),
            'config' => Tableau::getStaticModelFormConfig()
        ];
        return Inertia::render('Tableaux/Create', $inertiaData);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreTableauRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $validationRules = Tableau::getStaticModelValidationRules();
        $tableau = Tableau::create(Request::validate($validationRules));
        $tableau->processImage(Request::get('image'));
        if($tags = Request::get('tableauTags')) {
            $tableau->tableauTags()->sync($tags);
        }
        return to_route('tableaux.index')->with('message', 'Tableau crée');
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateTableauRequest  $request
     * @param  \App\Models\Tableau  $tableau
     * @return \Illuminate\Http\Response
     */
    public function update(Tableau $tableau)
    {
        
        $validationRules = Tableau::getStaticModelValidationRules();
        $tableau->update(Request::validate($validationRules));
        $tableau->processImage(Request::get('image'));
        if($tags = Request::get('tableauTags')) {
            $tableau->tableauTags()->sync($tags);
        }
        // return redirect()->back()->with('message', 'Tableau  mis à jour');;
        return to_route('tableaux.index')->with('message', 'Tableau crée');
    }


```

## Testing

```bash
Test is not working
// composer test
```



## Credits

- [Charles](https://github.com/charlesStOlive)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
