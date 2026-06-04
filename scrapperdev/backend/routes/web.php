<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/billing/success', fn () => response(
    '<!doctype html><meta charset="utf-8"><title>Pago recibido</title>'
    .'<body style="font-family:system-ui;text-align:center;padding:3rem">'
    .'<h1>¡Gracias por tu pago!</h1><p>Estamos confirmándolo con el proveedor. En unos segundos el plan Pro '
    .'se activa solo en la extensión — volvé a abrirla. Si en un par de minutos seguís en plan free, escribinos.</p>'
)->header('Content-Type', 'text/html'));

Route::get('/billing/cancel', fn () => response(
    '<!doctype html><meta charset="utf-8"><title>Pago cancelado</title>'
    .'<body style="font-family:system-ui;text-align:center;padding:3rem">'
    .'<h1>Pago cancelado</h1><p>No se hizo ningún cobro. Podés volver a la extensión.</p>'
)->header('Content-Type', 'text/html'));
