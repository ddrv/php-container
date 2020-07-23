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
    public static function factoryError($id, Exception $previous)
    {
        $message = 'Error of creating service ' . $id . ': ' . $previous->getMessage();
        return new static($message, 1, $previous);
    }

    /**
     * @param string $alias
     * @param string $service
     * @param string $path
     * @return ContainerException
     */
    public static function recursiveBinding($alias, $service, $path)
    {
        $message = 'Can not bind ' . $alias . ' to ' . $service . '. Recursion detected: ' . $path;
        return new static($message, 2);
    }
}
