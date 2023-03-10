<?php

namespace Waka\YamlForms;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Validation\Rule;

trait YamlFormsTrait
{
    protected static $role;

    public static function setRole($role)
    {
        static::$role = $role;
    }

    public static function getValidationRules($context = null)
    {
        $attributs = static::getYamlContextAttributs();
        $fields = static::extractFields($attributs);
        $rules = [];

        foreach ($fields as $field => $config) {
            $required = $config['required'] ?? false;
            if (is_array($required)) {
                $rules[$field] = $required;
            } elseif ($required === true) {
                $rules[$field] = ['required'];
            } elseif (is_string($required) && stripos($required, 'required') !== false) {
                $rules[$field] = ['required'];
            }
        }

        return $rules;
    }

    public static function getStaticModelValidationRules()
    {
        $validationsRules = static::getValidationRules();
        // Modifier les règles de validation pour ajouter la validation unique
        array_walk_recursive($validationsRules, [static::class, 'modifyStaticUniqueValidation']);
        return $validationsRules;
    }

    protected static function modifyStaticUniqueValidation(&$item, $key)
    {
        if ($item === 'unique') {
            $tab = (new static())->getTable();
            $item = Rule::unique($tab);
        }
    }

    public static function getFormFields($context = null)
    {
        $attributs = static::getYamlContextAttributs($context);
        $fields = static::extractFields($attributs);
        $formFields = collect($fields)
            ->filter(function ($config) use ($context) {
                return (!isset($config['permission']) || !static::$role || $config['permission'] === static::$role)
                    && !($config['hidden'] ?? false);
            })
            ->map(function ($config, $field) {
                $isRequired = isset($config['required']) && (
                    (is_array($config['required']) && in_array('required', $config['required'], true)) ||
                    $config['required'] === true ||
                    (is_string($config['required']) && stripos($config['required'], 'required') !== false)
                );
                $config['isRequired'] = $isRequired;
                if (!$type = $config['type'] ?? false) {
                    $config['type'] = 'label';
                }
                unset($config['required'], $config['context']);
                return $config;
            })
            ->all();


        return $formFields;
    }

    public static function getColumnsConfig($context = null)
    {
        $yaml = static::getYaml();
        $grid = $yaml['grid'];
        if (!$grid['defaultOrder'] ?? false) {
            $grid['defaultOrder'] = 'id';
        }
        if (!$grid['pagination'] ?? false) {
            $grid['pagination'] = 15;
        }
        return $grid;
    }

    public static function getFormConfig($context = null)
    {
        $yaml = static::getYaml();
        $form = $yaml['form'];
        return [
            'form' => $form,
            'fields' => static::getFormFields($context),
        ];
    }

    public static function getEmptyForm($context = null, $fields = null) {
    $fields = $fields ?? static::getFormFields($context);
    $defaultFields = [];

    foreach ($fields as $key => $field) {
        if (is_array($field) && array_key_exists('attributs', $field)) {
            $defaultFields[$key] = static::getEmptyForm($context, $field['attributs']);
        } else {
            $defaultFields[$key] = $field['default'] ?? null;
        }
    }

    return $defaultFields;
}

    public static function getStaticModelFormConfig()
    {
        $fields = collect(static::getFormConfig());
        $updatedCollection = $fields->map(function ($item) {
            if (is_array($item)) {
                return static::updateStaticOptionsData($item);
            } elseif ($item instanceof \Illuminate\Support\Collection) {
                return static::updateStaticOptionsData($item->toArray());
            }
            return $item;
        });

        return $updatedCollection;
    }

    private static function updateStaticOptionsData($array)
    {
        unset($array['optionsData']);
        foreach ($array as $key => $value) {
            if ($key === 'staticOptionsData') {
                $options = [];
                if (method_exists(static::class, $value)) {
                    $options = call_user_func_array([static::class, $value], []);
                }
                $array['optionsData'] = $options; // remplacez 'votre nouvelle valeur' par la valeur que vous souhaitez définir
            } elseif (is_array($value)) {
                $array[$key] = static::updateStaticOptionsData($value);
            } elseif ($value instanceof \Illuminate\Support\Collection) {
                $array[$key] = static::updateStaticOptionsData($value->toArray());
            }
        }
        return $array;
    }

    public static function getColumnsMeta($context = null)
    {
        $attributs = static::getYamlContextAttributs($context);
        $columns = static::extractColumns($attributs);

        $formColumns = collect($columns)
            ->filter(function ($config) use ($context) {
                return (!isset($config['permission']) || !static::$role || $config['permission'] === static::$role)
                    && !($config['hidden'] ?? false);
            })
            ->map(function ($config, $field) {
                if (!$type = $config['type'] ?? false) {
                    $config['type'] = 'text';
                }
                unset($config['context']);
                return $config;
            })
            ->all();

        return $formColumns;
    }

    // public static function getSearchableColumn(Array $addotherField) {
    //     $attributs = static::getYamlContextAttributs();
    //     $columns = collect(static::extractColumns($attributs));
    //     $columns = $columns->filter(function ($config) {
    //             return $ordorable = $config['searchable'] ?? false;
    //         })->keys();
    //     //logger($columns);
    // }

    public static function dataYamlColumnTransformer($data, $context = null)
    {
        $attributs = static::getYamlContextAttributs();
        $columns = static::extractColumns($attributs, $context);
        $columnData = [];
        foreach ($columns as $key => $config) {
            $columnFromYaml = $config['valueFrom'] ?? null;
            if ($columnFromYaml) {
                $columnData[$key] =  $data->$columnFromYaml;
            } else {
                $columnData[$key] =  $data->$key;
            }
        }
        return $columnData;
    }



    private static function extractFields($data)
    {
        $processedData = [];

        foreach ($data as $key => $value) {

            if (isset($value['column'])) {
                unset($value['column']);
            }
            // If the node has a "column" attribute that is boolean false, skip it.
            if (isset($value['field']) && $value['field'] === false) {
                continue;
            }

            // If the node has a "column" attribute that is an array, merge its data with its parent's data.
            if (isset($value['field']) && is_array($value['field'])) {
                $value = array_merge($value, $value['field']);
                unset($value['field']);
            }

            // If the node has "datas" children, process them recursively.
            if (isset($value['attributs'])) {
                $value['attributs'] = static::extractFields($value['attributs']);
                // Remove the "datas" node if it has no "column" children.
                if (empty($value['attributs'])) {
                    unset($value['attributs']);
                }
            }

            // Add the processed node to the result.
            $processedData[$key] = $value;
        }

        return $processedData;
    }

    private static function extractColumns($data)
    {
         $processedData = [];

        foreach ($data as $key => $value) {

            if (isset($value['field'])) {
                unset($value['field']);
            }
            // If the node has a "column" attribute that is boolean false, skip it.
            if (isset($value['column']) && $value['column'] === false) {
                continue;
            }

            // If the node has a "column" attribute that is an array, merge its data with its parent's data.
            if (isset($value['column']) && is_array($value['column'])) {
                $value = array_merge($value, $value['column']);
                unset($value['column']);
            }

            // If the node has "datas" children, process them recursively.
            if (isset($value['attributs'])) {
                $value['attributs'] = static::extractColumns($value['attributs']);
                // Remove the "datas" node if it has no "column" children.
                if (empty($value['attributs'])) {
                    unset($value['attributs']);
                }
            }

            // Add the processed node to the result.
            $processedData[$key] = $value;
        }
        return $processedData;
    }

    private static function getYamlContextAttributs($context = null)
    {
        $cacheEndName = 'all';
        if ($context) {
            $cacheEndName = $context;
        }
        $cacheKey = static::class . "::yaml_$cacheEndName";
        if (app()->environment('local', 'testing')) {
            Cache::forget($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function () use ($context) {
            $attributs = static::getYaml()['attributs'];

            if ($context === null) {
                return $attributs;
            }

            return $attributs->filter(function ($config) use ($context) {
                return !isset($config['context']) || in_array($context, (array)$config['context']);
            });
        });
    }

    /**
     * ===================================== NON STATIC ===========================
     */

    public function getModelValidationRules()
    {
        $validationsRules = static::getValidationRules();
        array_walk_recursive($validationsRules, [$this, 'modifyUniqueValidation']);
        return $validationsRules;
    }

    protected function modifyUniqueValidation(&$item, $key)
    {
        if ($item === 'unique') {
            $tab = $this->getTable();
            //logger($this->id);
            $item = Rule::unique($tab)->ignore($this->id);
        }
    }

    public function getModelFormConfig()
    {
        $formConfig = static::getFormConfig();
        $fields = $formConfig['fields'];
        $processedFields = [];
        foreach($fields as $key=>$field) {
            if (is_array($field)) {
                 $processedFields[$key] = $this->updateOptionsData($field);
            } else {
                $processedFields[$key] = $field;
            }
        }
        $formConfig['fields'] = $processedFields;
        return $formConfig;
    }

    private function updateOptionsData($array)
    {
        unset($array['staticOptionsData']);
        foreach ($array as $key => $value) {
            if ($key === 'optionsData') {
                $options = [];
                if (method_exists($this, $value)) {
                    $options = call_user_func_array([$this, $value], []);
                }
                $array[$key] = $options; // remplacez 'votre nouvelle valeur' par la valeur que vous souhaitez définir
            } elseif (is_array($value)) {
                $array[$key] = $this->updateOptionsData($value);
            }
        }
        return $array;
    }

    public function dataYamlFieldsTransformer($context = null)
    {
        $attributs = static::getYamlContextAttributs($context);
        $fields = static::extractFields($attributs);
        return $this->setValueFrom($fields);
    }

    public function setValueFrom($fields, $recurseKey= false)
    {
        logger("setValueFrom : ".$recurseKey);
        logger($fields);
        $columnData = [];
        $model = $this;
        if($recurseKey) {
            $model = $this->$recurseKey;
            logger($model);
        }

        foreach ($fields as $key => $config) {
            $columnFromYaml = $config['valueFrom'] ?? null;
            if ($columnFromYaml) {
                $columnData[$key] =  $model->$columnFromYaml;
            } else {
                $columnData[$key] =  $model->$key ?? $model[$key] ?? null;
            }

            // If the config has "datas" children, process them recursively.
            if (isset($config['attributs'])) {
                //logger($config['attributs']);
                $columnData[$key] = $this->setValueFrom($config['attributs'], $key);   
            }
        }

        return $columnData;
    }

    public function processImage($url)
    {
        if ($url) {
            $path = storage_path('app/public/' . $url);
            if (file_exists($path)) {
                //suppresion de la première image
                if($this->getFirstMedia('image')) $this->getFirstMedia('image')->delete();
                //Et création de la nouvelle
                $this->addMedia($path)->toMediaCollection('image');
            } else {
                //logger('pas de fichier, je ne met rien à jour !');
            }
        } else {
            //Url est vide L'image du mdoèle est a supprimé. 
            if($this->getFirstMedia('image')) $this->getFirstMedia('image')->delete();
        }
    }

    public function moveOrder($moveMode)
    {
        if ($moveMode == 'up') $this->moveOrderUp();
        if ($moveMode == 'down') $this->moveOrderDown();
        if ($moveMode == 'start') $this->moveToStart();
        if ($moveMode == 'end') $this->moveToEnd();
        $this->save();
    }




    private static function getYaml()
    {
        $cacheKey = static::class . '::yaml';
        if (app()->environment('local', 'testing')) {
            Cache::forget($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function () {
            $fileName = static::getYamlFileName();
            $filePath = static::getYamlFilePath($fileName);

            if (!file_exists($filePath)) {
                throw new \Exception("Le fichier YAML n'existe pas : $filePath");
            }

            $yaml = Yaml::parseFile($filePath);

            return new Collection($yaml);
        });
    }





    private static function getYamlFileName()
    {
        $className = (new \ReflectionClass(static::class))->getShortName();

        return $className . '.yaml';
    }

    private static function getYamlFilePath($fileName)
    {
        return base_path("App/Models/Yaml/$fileName");
    }
}
