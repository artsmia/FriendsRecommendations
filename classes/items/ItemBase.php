<?php namespace DMA\Recomendations\Classes\Items;

use Log;
use Doctrine\DBAL\Query\QueryBuilder;
use Dma\Recomendations\Models\Settings;
use Doctrine\DBAL\Types\ArrayType;

abstract class ItemBase
{
    
    /**
     * This item is active
     * @var boolean
     */
    public $active = true;

    /**
     * This item can be editable by CMS admin
     * @var boolean
     */
    public $adminEditable = true;
    
    /**
     * Array of features and options
     * @var array
     */
    private $features = null;
    
    /**
     * Common recomedation item settings
     * @var array
     */
    protected $common_settings = [
        'max_recomendations' => [
            'label' => 'Maximum limit of recomendations',
            'span'  => 'auto',
            'type'  => 'number', 
            'default' => 5,
            'commentAbove' => 'This value only affects this Recomendation item.',
        ],
        'active' => [
            'label' => 'Is active',
            'span'  => 'auto',
            'type'  => 'checkbox',
            'default' => true ,
            'comment' => 'When disable engine will not get recomendations of this Item.',
        ],
                
        'features' => [
            'label' => 'Features',
            'span'  => 'left',
            'type'  => 'checkboxlist',
            'options' => [],
            'commentAbove' => 'Make recomendations using the following features:',
        ], 

        'filters' => [
            'label' => 'Filters',
            'span'  => 'right',
            'type'  => 'checkboxlist',
            'options' => [],
            'commentAbove' => 'Filter recomendations by one or many of the following filters:',
        ],  

        'weight_by' => [
            'label' => 'Weight',
            'span'  => 'left',
            'type'  => 'dropdown',
            'options' => [],
            'commentAbove' => 'Boost recomendation items by:',
        ],
        
        'tools' => [
            'span'  => 'full',
            'type'  => 'partial',
            'path'  => '@/plugins/dma/recomendations/models/settings/_tools_field.htm'
        ],
        
    ];

    /**
     * Return classname of the model that will feed 
     * this recomendation item
     *
     * @return string
     */
    abstract public function getModel();

    /**
     * Return QueryScope to filer the data send to populate 
     * the engine.
     *
     * @return QueryBuilder
     */
    public function getQueryScope()
    {
        $model = $this->getModel();
        $query = new $model;
        return $query;
    }
    
    /**
     * Helper method to get the Primary key name field of this model
     * @return string
     */
    public function getModelKeyName()
    {
        // Create an instance of the model to get primary key name
        // I couldn't find a better solution 
        $model = $this->getModel();
        $model = new $model;
        return $model->getKeyName();
    }
    
    
    /**
     * Extract data of the features of the given instance model
     * @param October\Rain\Database\Model $model
     * 
     * @return array
     */
    public function getItemData($model)
    {
       	$data = [];
       	       	
       	foreach($this->getItemDataFields() as $field){
       	    try {
       	        // Get field name
                $field = $field[0];
       	        
       	        // Check if a method exists for this feature
       	        $prepareMethod = 'get' . ucfirst($field);
       	        if (! method_exists($this, $prepareMethod) ){
           	        
               	    $value = $model->{$field};
               	    // Check if is the feature is a collection result of a relatioship
               	    if (is_a($value, 'Illuminate\Database\Eloquent\Collection')){
               	        // If so return array of PKs
               	        $value = $value->map(function($r){
                            return($r->getKey());
               	        });
               	    }
       	        }else{
       	            // Call prepare method
       	            $value = $this->{$prepareMethod}($model);
       	        }

            /*   
       	    } catch(\Exception $e) {
       	        $value = null;
        	    Log::error(sprintf('Extracting Item feature [%s] in [%s]', $field, get_class($this)), [
                    'message' => $e->getMessage(),
                    //'stack'   => $e->getTraceAsString()
       	       ]); */
       	    } finally {
       	        $data[$field] = $value or '';
       	        
       	        //$valDebug = (is_array($value)) ? $value : [$value];
       	        //Log::debug($feature, $valDebug);
       	    }
       	}
       	return $data;
    }
    
    
    
    /**
     * Unique ID identifier of the Item.
     *
     * @return string
     */
    abstract public function getKey();
    
    
    /**
     * Configure specific settings fields for this recomendation item.
     * For futher information go to http://octobercms.com/docs/plugin/settings#database-settings
     *
     * @return array
     */
    abstract public function getSettingsFields();
    
    
    /**
     * Get recomendation item settings fields including commong field settings 
     * All settings are prefixed with the key identifier of the recomendation item.
     * 
     * @return array
     */
    public function getPluginSettings()
    {
       # Get common and specific settings
       $combine = array_merge($this->common_settings,  $this->getSettingsFields());
       $settings = [];
       
       $key = strtolower($this->getKey());
       foreach($combine as $k => $v){
            $settings[$key . '_' . $k] = $v;
       }

       # Add Feature list of options 
       $this->addSettingsOptions('features', $settings, $this->getFeatures());

       # Add Feature list of options
       $this->addSettingsOptions('filters', $settings, $this->getFilters());       

       # Add Feature list of options
       $this->addSettingsOptions('weight_by', $settings, $this->getWeightFeatures(), true);
       
       return $settings;
       
    }

    /**
     * Helper function add options list to settings fields. This is used for display  
     * options in OctoberCMS admin interface.
     * 
     * @param string $settingName
     * @param array  $settings
     * @param array  $options
     */
    private function addSettingsOptions($settingName, &$settings, array $options, $emptyValue=false)
    {
        $key = strtolower($this->getKey());
        $idField =  $key. '_' . $settingName;
        
        $fieldSettings = &$settings[$idField];

        if (!is_null($fieldSettings)){
        	
        	if (count($options) > 0){
        		$opts    = &$fieldSettings['options'];     		
        		
        		if($emptyValue){
        		  $opts[null] = '--empty--';
        		}
        		
        		forEach($options as $k){
        		    $k = (is_array($k)) ? array_shift($k) : $k;
        			$opts[$k] = ucfirst($k);
        		}
        		
        	}else{
        		unset($settings[$idField]);
        	}
        }       
    } 
    
    /**
     * Return all declared fields and properties in this Recomendation Item
     * @return array
     */
    public function getItemDataFields(){
        if(is_null($this->features)){
            $features = array_merge(
            		$this->getFeatures(),
            		$this->getFilters(),
            		$this->getWeightFeatures()
            );        
            
            $this->features = array_map(function($f){
                return (!is_array($f)) ? [$f] : $f;
            }, $features); 
        }
        return $this->features;
    }

    /**
     * Return an array of fields of the model that will be used
     * as features of the recomendation item. 
     * 
     * @return array
     */
    abstract protected function getFeatures();
    
    /**
     * Return an array of all active features
     * @return array
     */
    public function getActiveFeatures()
    {
        return Settings::get(strtolower($this->getKey()) .'_features', []);
    }
    

    /**
     * Return an array of filters avaliable by the recomendation item.
     * 
     * @return array
     */
    abstract protected function getFilters();

    /**
     * Return an array of all active filters
     * @return array
     */
    public function getActiveFilters()
    {
    	return Settings::get(strtolower($this->getKey()) .'_filters', []);
    }

    /**
     * Return an array of the fields that can be use to boots
     * each Recomentation setting.
     *
     * @return array
     */
    abstract protected function getWeightFeatures();
    
    /**
     * Return an array of all active boost fields
     * @return array
     */
    public function getActiveWeightFeature()
    {
    	$feature = Settings::get(strtolower($this->getKey()) .'_weight_by', null);
    	$feature = ($feature == '') ? null : $feature;
    	return $feature;
    }    
    
    /**
     * Return an associative array 
     * @return multitype:
     */
    public function getItemRelations()
    {
        return [];
    }

    /**
     * Return an array of events namespaces that will be bind
     * to keep updated this Recomendation Item in each engine backend.
     * 
     * An event can define a custom Clouser with custom logic. If a Clouser
     * is not given the engine will generate a generic Clouser that look for register
     * October databse models and use the Item recomendation of it if register. 
     * 
     * 
     * Eg. 
     * public function getUpdateEvents()
     * {
     *    // Create a reference to this Item so it can be use within the event clouser
     *    $item = $this;
     *    return [
     *		      'friends.activityUpdated',
     *		      'friends.activityCompleted' => function($user, $activity) use ($item){
     *		          // $this is a instance reference to the active engine
     *                $this->update($activity);
     *		          $this->update($user);
     *		      
     *		      }
     *    ];
     *  }
     * 
     * @return Array
     */
    abstract public function getUpdateEvents();
    
}