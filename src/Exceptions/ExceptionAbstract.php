<?php
namespace BFilters\Exceptions;


use BFilters\Traits\ArrayRecursiveImplodeTrait;

/**
 * Class ExceptionAbstract
 *
 * @package Infrastructure\Abstracts
 */
abstract class ExceptionAbstract extends \Exception
{
    use ArrayRecursiveImplodeTrait;

    /**
     * @var null|int
     */

    private $errorCode ;

    /**
     * List of validation errors!
     *
     * @var array
     */
    private $errors = [];

    /**
     * ExceptionAbstract constructor.
     *
     * @param $message
     * @param null $errorCode
     * @param \Throwable|null $previous
     */
    public function __construct($message, $errorCode = null,  \Throwable $previous = null)
    {
        $this->errorCode = $errorCode;

        if (is_array($message)) {
            $this->errors = $message;
            $message = $this->arrayRecursiveImplode($message);
        }

        parent::__construct($message, $errorCode, $previous);
    }



    /**
     * Get error array.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get error array.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
