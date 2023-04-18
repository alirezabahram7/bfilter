<?php
namespace  BFilters\Exceptions;

use Symfony\Component\HttpFoundation\Response;


/**
 * Class BusinessLogicException
 *
 * @package  App\Exceptions
 */
class ValidationException extends ExceptionAbstract
{
    public function __construct(
        $message,
        string $errorCode = Response::HTTP_UNPROCESSABLE_ENTITY,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode , $previous);
    }
}
