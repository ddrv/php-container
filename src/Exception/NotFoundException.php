<?php

namespace Ddrv\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $message = 'Service ' . (string)$id . ' not found in container.';
        parent::__construct($message, 1);
    }
}
