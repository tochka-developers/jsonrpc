<?php

namespace Tochka\JsonRpc\Exceptions\RPC;

use Tochka\JsonRpc\Exceptions\JsonRpcException;

/**
 * Class WebServiceException
 * @package App\Exceptions
 */
class BusinessValidationErrorException extends JsonRpcException
{
    /**
     * BusinessValidationErrorException constructor.
     *
     * @param int             $code
     * @param null            $message
     * @param null            $object_name
     * @param null            $meta
     * @param \Exception|null $previous
     */
    public function __construct($code, $message, $object_name = null, $meta = null, \Exception $previous = null)
    {
        $error = [
            'code'    => $code,
            'message' => $message,
        ];

        if (null !== $object_name) {
            $error['object_name'] = $object_name;
        }

        if (null !== $meta) {
            $error['meta'] = $meta;
        }

        parent::__construct(self::CODE_VALIDATION_ERROR, null, [$error], $previous);
    }
}