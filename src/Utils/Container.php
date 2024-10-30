<?php
/**
 * Common container
 */

namespace Clickio\Utils;

/**
 * Common container
 *
 * @package Utils
 */
abstract class Container implements \ArrayAccess
{
    /**
     * Factory method
     *
     * @param array $params input params
     *
     * @return static
     */
    public static function create(array $params = [])
    {
        $obj = new static();
        foreach ($params as $name => $value) {
            $obj->__set($name, $value);
        }
        return $obj;
    }

    /**
     * Convert snake case method name to camel-case method name
     *
     * @param string $src snake case name
     *
     * @return string
     */
    protected function snakeCaseToCamelCase(string $src)
    {
        $words = explode('_', $src);
        $capitalized = array_map(
            function ($el) {
                return ucfirst($el);
            },
            $words
        );
        return implode('', $capitalized);
    }

    /**
     * Implemented ArrayAcces method
     *
     * @param string $name property name
     * @param mixed $value property value
     *
     * @return void
     */
    public function offsetSet($name, $value)
    {
        $this->__set($name, $value);
    }

    /**
     * Implemented ArrayAcces method
     *
     * @param string $name property name
     *
     * @return bool
     */
    public function offsetExists($name)
    {
        return property_exists($this, $name) || method_exists($this, $name);
    }

    /**
     * Implemented ArrayAcces method
     *
     * @param string $name property name
     *
     * @return void
     */
    public function offsetUnset($name)
    {
        $new_obj = new static();
        $this->{$name} = $new_obj->{$name};
    }

    /**
     * Implemented ArrayAcces method
     *
     * @param string $name property name
     *
     * @return mixed
     */
    public function offsetGet($name)
    {
        return $this->__get($name);
    }

    /**
     * Magic method
     *
     * @param string $name property name
     * @param mixed $value property value
     *
     * @return void
     */
    public function __set(string $name, $value)
    {
        $cc_prop = $this->snakeCaseToCamelCase($name);
        $method_name = 'set'.$cc_prop;
        if (method_exists($this, $method_name)) {
            call_user_func_array([$this, $method_name], [$value]);
        } else {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * Magic method
     *
     * @param string $name property name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $cc_prop = $this->snakeCaseToCamelCase($name);
        $method_name = 'get'.$cc_prop;
        if (method_exists($this, $method_name)) {
            return call_user_func_array([$this, $method_name], []);
        } else {
            if (property_exists($this, $name)) {
                return $this->{$name};
            }
        }
        return null;
    }

    /**
     * Copy self value into array
     *
     * @return array
     */
    public function toArray()
    {
        $arr = [];
        foreach (array_keys(get_object_vars($this)) as $field) {
            $arr[$field] = $this->__get($field);
        }
        return $arr;
    }
}