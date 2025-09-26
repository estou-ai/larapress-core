<?php

namespace Larapress\Contracts;

use Larapress\Components\Form\Form;

interface ResourceContract
{
    public function form(Form $form): Form;

    public function getActions(): array;


    public function getPages(): array;


}