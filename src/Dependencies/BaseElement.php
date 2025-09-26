<?php

namespace Larapress\Dependencies;

abstract class BaseElement
{
    protected string $separator = '_';

    protected function getClass(): string
    {
        $class = get_called_class();
        $classStructure = explode('\\',$class);

        $className = lcfirst($classStructure[count($classStructure) - 1]);
        $classNameParts = preg_split('/(?=[A-Z])/',$className);
        return strtolower($this->slugify(...$classNameParts));
    }

    protected function slugify(...$params): string
    {
        return implode($this->separator, $params);
    }

}