<?php

use function Pest\Laravel\get;
use function Pest\Laravel\withHeader;

it('shows the storefront in Spanish by default', function () {
    get('/')->assertOk()
        ->assertSee('<html lang="es">', false)
        ->assertSee('Saltar al contenido');
});

it('switches the storefront language and remembers it', function (string $locale, string $text) {
    get('/idioma/'.$locale)->assertRedirect();

    get('/')->assertOk()
        ->assertSee('<html lang="'.$locale.'">', false)
        ->assertSee($text);
})->with([
    'english' => ['en', 'Skip to content'],
    'catalan' => ['ca', 'Salta al contingut'],
]);

it('stays in Spanish whatever the browser language, so search engines index Spanish', function () {
    withHeader('Accept-Language', 'en-US,en;q=0.9')->get('/')->assertOk()->assertSee('Saltar al contenido');
});

it('rejects unknown languages', function () {
    get('/idioma/fr')->assertNotFound();
});
