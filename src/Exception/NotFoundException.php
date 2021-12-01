<?php

namespace Ddrv\Container\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id
     */
    public function __construct(string $id)
    {
        $message = sprintf('Service %s not found in container.', $id);
        parent::__construct($message, 1);
    }
}
