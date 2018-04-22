<?php


namespace DateCast;

use Carbon\Carbon;

trait DateCast
{
    /**
     * Model's Accessors, Mutators
     *
     * @var array
     */
    public $methods = [];

    /**
     * magic call method
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $this->setDateAccessorsMutatorsIfNotSet($method);

        if($this->shouldCallDynamicAccessorMutator($method))
        {
            return $this->callDynamicAccessorMutator($method, $parameters);
        }

        return $this->forwardMagicCallToModel($method, $parameters);
    }

    /**
     * overridden method from Eloquent\Model
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $dates = $this->getDateColumnsToCast();

        return array_merge(parent::getMutatedAttributes(), $dates);
    }

    /**
     * overridden method from Eloquent\Model
     *
     * Determine if a get mutator exists for an attribute.
     *
     * @param $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return parent::hasGetMutator($key) || in_array($key, $this->getDateColumnsToCast());
    }

    /**
     * overridden method from Eloquent\Model
     *
     * Determine if a set mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return parent::hasSetMutator($key) || in_array($key, $this->getDateColumnsToCast());
    }

    /**
     * Call accessor or mutator defined by this trait
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function callDynamicAccessorMutator($method, $parameters)
    {
        return call_user_func_array($this->methods[$method], $parameters);
    }

    /**
     * Determine whether to call dynamic accessor or mutator (defined by this trait)
     *
     * @param $method
     * @return bool
     */
    public function shouldCallDynamicAccessorMutator($method)
    {
        return isset($this->methods[$method]) && is_callable($this->methods[$method]);
    }

    /**
     * return date format to be used through out the app
     *
     * @return string
     */
    public function getDestFormat()
    {
        return 'm/d/Y';
    }

    /**
     * Add new dynamic accessor or mutator
     */
    public function addDynamicAccessorsMutators()
    {
        /**
         * check if date property set by model
         */
        if ($columns = $this->getDateColumnsToCast())
        {
            /**
             * user laravel get date function to get created_at and updated_at dates too
             */
            foreach($columns as $dateColumnName)
            {
                $this->addDynamicAccessor($dateColumnName);

                $this->addDynamicMutator($dateColumnName);
            }
        }
    }

    /**
     * As we have right __call magic method, we should provide functionality of Eloquent\Model's __call magic method
     *
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function forwardMagicCallToModel($method, $parameters)
    {
        if (in_array($method, array('increment', 'decrement')))
        {
            return call_user_func_array(array($this, $method), $parameters);
        }

        $query = $this->newQuery();

        return call_user_func_array(array($query, $method), $parameters);
    }

    /**
     *
     * Add Dynamic accessor or mutator if already not been defined
     *
     * @param $method
     */
    public function setDateAccessorsMutatorsIfNotSet($method)
    {
        if (empty($this->methods))
        {
            $this->addDynamicAccessorsMutators();
        }
    }

    /**
     * Add new dynamic accessor
     *
     * @param $dateColumnName
     */
    public function addDynamicAccessor($dateColumnName)
    {
        $functionName = $this->getAccessorFunctionName($dateColumnName);

        $function = $this->getAccessorFunction($dateColumnName);

        $this->methods[$functionName] = \Closure::bind($function, $this, get_class());
    }

    /**
     * Get the Accessor's Name
     *
     * @param $dateColumnName
     * @return string
     */
    public function getAccessorFunctionName($dateColumnName)
    {
        return "get".studly_case($dateColumnName)."Attribute";
    }

    public function getAccessorFunction($dateColumnName)
    {
        return function () use ($dateColumnName) {

            if(!isset($this->attributes[$dateColumnName]))
            {
                return null;
            }

            if(!($dateColumnValue = $this->attributes[$dateColumnName]))
            {
                return null;
            }

            return Carbon::createFromFormat($this->getDateFormat(), $dateColumnValue)->format($this->getDestFormat());
            return $this->parseDate($dateColumnName, $this->getDateFormat(), $dateColumnValue, $this->getDestFormat());
        };

    }

    public function addDynamicMutator($dateColumnName)
    {
        $functionName = $this->getMutatorFunctionName($dateColumnName);

        $function = $this->getMutatorFunction($dateColumnName);

        $this->methods[$functionName] = \Closure::bind($function, $this, get_class());
    }

    public function getMutatorFunctionName($dateColumnName)
    {
        return "set".studly_case($dateColumnName)."Attribute";
    }

    public function getMutatorFunction($dateColumnName)
    {
        return function ($value) use ($dateColumnName) {
            return $this->attributes[$dateColumnName] = $this->parseDate($dateColumnName, $this->getDestFormat(), $value, $this->getDateFormat());
        };

    }

    private function parseDate($dateColumnName, $fromFormat, $date, $toFormat)
    {
        if(is_null($date)) return null;

        if($sourceFormat = $this->getDateFormatForAField($dateColumnName))
        {
            return Carbon::createFromFormat($sourceFormat, $date)->format($toFormat);
        }

        if($this->auto_parse_dates) return Carbon::parse($date)->format($toFormat);

        return Carbon::createFromFormat($fromFormat, $date)->format($toFormat);
    }

    private function getDateColumnsToCast()
    {
        return isset($this->dates_to_cast) ? $this->dates_to_cast : [];
    }

    private function getDateFormatForAField($fieldName)
    {
        $formats = isset($this->dates_cast_from_formats) ? $this->dates_cast_from_formats : [];

        return array_get($formats, $fieldName);
    }

}