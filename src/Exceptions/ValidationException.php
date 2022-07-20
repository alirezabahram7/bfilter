<?php
namespace  BFilters\Exceptions;

use Illuminate\Http\JsonResponse;


/**
 * Class BusinessLogicException
 *
 * @package  App\Exceptions
 */
class ValidationException extends ExceptionAbstract
{
    public function __construct(
        $message,
        string $errorCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode , $previous);
    }
}
