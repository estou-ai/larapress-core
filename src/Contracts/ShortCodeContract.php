<?php

namespace Larapress\Contracts;

interface ShortCodeContract
{
    public function execute($args): false|string;

}