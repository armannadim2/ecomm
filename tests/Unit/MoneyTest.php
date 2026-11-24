<?php

use App\Support\Money;

it('converts euros typed by staff into cents', function (string|float $input, int $cents) {
    expect(Money::toCents($input))->toBe($cents);
})->with([
    ['19.99', 1999],
    ['19,99', 1999],
    [0.1 + 0.2, 30],
    ['5', 500],
]);

it('treats an empty amount as null', function () {
    expect(Money::toCents(''))->toBeNull()
        ->and(Money::toCents(null))->toBeNull();
});

it('rejects text that is not a number', function () {
    Money::toCents('diez');
})->throws(InvalidArgumentException::class);

it('formats cents the Spanish way', function () {
    expect(Money::format(123456))->toBe('1.234,56 €')
        ->and(Money::toEuros(1999))->toBe('19.99');
});
