<?php

namespace Larapress\Contracts;

use Larapress\Components\Table\Table;

interface HasTableContract
{
    public function getTable():Table;

}