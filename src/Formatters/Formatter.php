<?php

namespace Sihae\Formatters;

interface Formatter
{
    /**
     * Format the given array of data somehow
     *
     * @param array $data
     * @return array
     */
    public function format(array $data) : array;
}