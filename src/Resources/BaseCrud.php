<?php

namespace Larapress\Resources;

interface BaseCrud
{
    public function generateActions();
    public function getPages();
    public function getActions();
    public static function getRoute();
}