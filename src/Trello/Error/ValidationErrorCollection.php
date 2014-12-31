<?php
/**
 * collection of errors enumerating all validation errors for a given request
 *
 * <b>== More information ==</b>
 *
 * For more detailed information on Validation errors, see {@link http://www.Trellopayments.com/gateway/validation-errors http://www.Trellopaymentsolutions.com/gateway/validation-errors}
 *
 * @package    Trello
 * @subpackage Error
 * @copyright  2014 Steven Maguire
 *
 * @property-read array $errors
 * @property-read array $nested
 */
class Trello_Error_ValidationErrorCollection extends Trello_Collection
{
    private $_errors = [];
    private $_nested = [];

    /**
     * @codeCoverageIgnore
     */
    public function  __construct($data)
    {
        foreach($data AS $key => $errorData)
            // map errors to new collections recursively
            if ($key == 'errors') {
                foreach ($errorData AS $error) {
                    $this->_errors[] = new Trello_Error_Validation($error);
                }
            } else {
                $this->_nested[$key] = new Trello_Error_ValidationErrorCollection($errorData);
            }

    }

    public function deepAll()
    {
        $validationErrors = array_merge([], $this->_errors);
        foreach($this->_nested as $nestedErrors)
        {
            $validationErrors = array_merge($validationErrors, $nestedErrors->deepAll());
        }
        return $validationErrors;
    }

    public function deepSize()
    {
        $total = sizeof($this->_errors);
        foreach($this->_nested as $_nestedErrors)
        {
            $total = $total + $_nestedErrors->deepSize();
        }
        return $total;
    }

    public function forIndex($index)
    {
        return $this->forKey("index" . $index);
    }

    public function forKey($key)
    {
        return isset($this->_nested[$key]) ? $this->_nested[$key] : null;
    }

    public function onAttribute($attribute)
    {
        $matches = [];
        foreach ($this->_errors AS $key => $error) {
           if($error->attribute == $attribute) {
               $matches[] = $error;
           }
        }
        return $matches;
    }


    public function shallowAll()
    {
        return $this->_errors;
    }

    /**
     *
     * @codeCoverageIgnore
     */
    public function  __get($name)
    {
        $varName = "_$name";
        return isset($this->$varName) ? $this->$varName : null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function __toString()
    {
        $output = [];

        // TODO: implement scope
        if (!empty($this->_errors)) {
            $output[] = $this->_inspect($this->_errors);
        }
        if (!empty($this->_nested)) {
            foreach ($this->_nested AS $key => $values) {
                $output[] = $this->_inspect($this->_nested);
            }
        }
        return join(', ', $output);
    }

    /**
     * @codeCoverageIgnore
     */
    private function _inspect($errors)
    {
        $eOutput = '[' . __CLASS__ . '/errors:[';
        $outputErrs = [];
        foreach($errors AS $error => $errorObj) {
            $outputErrs[] = "({$errorObj->error['code']} {$errorObj->error['message']})";
        }
        $eOutput .= join(', ', $outputErrs) . ']]';

        return $eOutput;
    }
}
