<?php

declare(strict_types=1);

namespace Sihae;

use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface;

final class Container extends PimpleContainer implements ContainerInterface
{
    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }
}
