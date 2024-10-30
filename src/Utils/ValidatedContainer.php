<?php

/**
 * Validated container
 */

namespace Clickio\Utils;

/**
 * Validated container
 *
 * @package Utils
 */
abstract class ValidatedContainer extends Container
{
    /**
     * Error list
     *
     * @var array
     */
    private $_errors = [];

    /**
     * Container status
     *
     * @var bool
     */
    private $_is_valid = true;

    /**
     * Validate container
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $values = $this->toArray();
        foreach ($values as $field => $value) {
            $cc_field = $this->snakeCaseToCamelCase($field);
            $validator = sprintf("validate%s", $cc_field);

            if (method_exists($this, $validator)) {
                $is_valid = call_user_func_array([$this, $validator], [$value]);
                if (!$is_valid) {
                    $this->_is_valid = false;
                }
            }
        }
        return $this->_is_valid;
    }

    /**
     * Add validation error
     *
     * @param string $field field with error
     * @param string $msg error message
     *
     * @return void
     */
    protected function pushError(string $field, string $msg)
    {
        if (!array_key_exists($field, $this->_errors)) {
            $this->_errors[$field] = [];
        }

        $this->_errors[$field][] = $msg;
    }

    /**
     * Errors getter
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->_errors;
    }
}