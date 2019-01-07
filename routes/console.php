<?php

use Illuminate\Foundation\Inspiring;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('installer:table', function () {
    $this->comment('pusing');
})->describe('Display table list');

Artisan::command('installer:create-table', function () {
    $this->comment('pusing');
})->describe('Display table list');
