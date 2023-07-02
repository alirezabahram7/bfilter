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

    private ?int $errorCode;

    /**
     * List of validation errors!
     *
     * @var array
     */
    private array $errors = [];

    /**
     * ExceptionAbstract constructor.
     *
     * @param                 $message
     * @param int|null        $errorCode
     * @param \Throwable|null $previous
     */
    public function __construct($message, ?int $errorCode = null, \Throwable $previous = null)
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
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error array.
     *
     * @return int|null
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }
}
