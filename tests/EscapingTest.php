<?php

use Larapress\Components\Form\Input;
use Larapress\Components\Form\Select;
use Larapress\Components\Table\Column;

/**
 * Output escaping. Values here reach the admin from public forms, so a gap in
 * any of these is stored XSS.
 */

describe('table columns', function () {
    it('escapes a value by default', function () {
        expect(Column::make('name')->getRenderedValue(['name' => '<script>alert(1)</script>']))
            ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    it('escapes a setGetValue result too', function () {
        $column = Column::make('name')->setGetValue(fn(array $row) => $row['name']);

        expect($column->getRenderedValue(['name' => '<b>x</b>']))->toBe('&lt;b&gt;x&lt;/b&gt;');
    });

    it('renders raw markup only when html() opts in', function () {
        $column = Column::make('link')->html();

        expect($column->getRenderedValue(['link' => '<a href="#">go</a>']))->toBe('<a href="#">go</a>');
    });

    it('renders a missing value as a dash', function () {
        expect(Column::make('absent')->getRenderedValue(['other' => 1]))->toBe('-');
    });

    it('joins an array instead of printing Array', function () {
        $column = Column::make('guests')->setGetValue(fn(array $row) => $row['guests']);

        expect($column->getRenderedValue(['guests' => ['Ana', 'Rui']]))->toBe('Ana, Rui');
    });
});

describe('form inputs', function () {
    it('escapes a value that would close the attribute', function () {
        // After a failed submit the value comes straight from $_POST.
        $html = Input::make('name')->setValue('" autofocus onfocus="alert(1)')->render();

        expect($html)->not->toContain('onfocus="alert(1)"')
            ->and($html)->toContain('&quot;');
    });

    it('escapes the placeholder and the field name', function () {
        $html = Input::make('a"b')->setPlaceholder('"><script>')->render();

        expect($html)->not->toContain('<script>');
    });

    it('escapes option values and labels', function () {
        $html = Select::make('choice')
            ->setOptions(['<v>' => '<script>alert(1)</script>'])
            ->render();

        expect($html)->not->toContain('<script>')
            ->and($html)->toContain('&lt;script&gt;');
    });
});
