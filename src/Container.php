<?php

namespace Ddrv\Container;

use Closure;
use Ddrv\Container\Exception\ContainerException;
use Ddrv\Container\Exception\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{
    /**
     * @var Closure[]
     */
    private array $factories = [];

    /**
     * @var array
     */
    private array $services = [];

    /**
     * @var string[]
     */
    private array $map = [];

    /**
     * @var bool[]
     */
    private array $instances = [];

    /**
     * @var ContainerInterface[]
     */
    private array $containers = [];

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException($id);
        }
        if (array_key_exists($id, $this->map)) {
            return $this->get($this->map[$id]);
        }
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }
        if (array_key_exists($id, $this->factories)) {
            $factory = $this->factories[$id];
            try {
                $service = $factory($this);
            } catch (Exception $exception) {
                throw ContainerException::factoryError($id, $exception);
            }
            if (!array_key_exists($id, $this->instances)) {
                $this->services[$id] = $service;
            }
            return $service;
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return $container->get($id);
            }
        }

        return $this->services[$id];
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->map)) {
            return $this->has($this->map[$id]);
        }
        if (array_key_exists($id, $this->services)) {
            return true;
        }
        if (array_key_exists($id, $this->factories)) {
            return true;
        }
        foreach ($this->containers as $container) {
            if ($container->has($id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $id
     * @param mixed $value
     * @return void
     */
    public function value(string $id, $value)
    {
        $this->services[$id] = $value;
        if (array_key_exists($id, $this->factories)) {
            unset($this->factories[$id]);
        }
        if (array_key_exists($id, $this->instances)) {
            unset($this->instances[$id]);
        }
        if (array_key_exists($id, $this->map)) {
            unset($this->map[$id]);
        }
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function service(string $id, callable $factory)
    {
        $this->factories[$id] = $factory;
        if (array_key_exists($id, $this->services)) {
            unset($this->services[$id]);
        }
        if (array_key_exists($id, $this->instances)) {
            unset($this->instances[$id]);
        }
        if (array_key_exists($id, $this->map)) {
            unset($this->map[$id]);
        }
    }

    /**
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function instance(string $id, callable $factory)
    {
        $this->factories[$id] = $factory;
        if (array_key_exists($id, $this->services)) {
            unset($this->services[$id]);
        }
        $this->instances[$id] = true;
        if (array_key_exists($id, $this->map)) {
            unset($this->map[$id]);
        }
    }

    /**
     * @param string $alias
     * @param string $service
     * @return void
     */
    public function bind(string $alias, string $service)
    {
        $alias = trim($alias);
        $service = trim($service);
        $path = [$alias];
        $to = $service;
        do {
            $path[] = $to;
            if ($alias === $to) {
                throw ContainerException::recursiveBinding($alias, $service, implode(' -> ', $path));
            }
            $to = array_key_exists($to, $this->map) ? $this->map[$to] : null;
        } while ($to);
        $this->map[$alias] = $service;
        if (array_key_exists($alias, $this->services)) {
            unset($this->services[$alias]);
        }
        if (array_key_exists($alias, $this->instances)) {
            unset($this->instances[$alias]);
        }
        if (array_key_exists($alias, $this->factories)) {
            unset($this->factories[$alias]);
        }
    }

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function delegate(ContainerInterface $container)
    {
        if ($container === $this) {
            return;
        }
        foreach ($this->containers as $delegated) {
            if ($delegated === $this) {
                return;
            }
        }
        $this->containers[] = $container;
    }
}
