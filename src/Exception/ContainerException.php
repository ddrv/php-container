<?php

namespace Ddrv\Container\Exception;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Exception;

final class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface
{
    /**
     * @param string $id
     * @param Exception $previous
     * @return ContainerException
     */
    public static function factoryError(string $id, Exception $previous): self
    {
        $message = sprintf('Error of creating service %s: %s', $id, $previous->getMessage());
        return new self($message, 1, $previous);
    }

    /**
     * @param string $alias
     * @param string $service
     * @param string $path
     * @return ContainerException
     */
    public static function recursiveBinding(string $alias, string $service, string $path): self
    {
        $message = sprintf('Can not bind %s to %s. Recursion detected: %s', $alias, $service, $path);
        return new self($message, 2);
    }
}
