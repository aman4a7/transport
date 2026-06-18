<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you return to your test functions provides the "test case"
| configuration that Pest uses to tie everything together. You can set
| the "uses" key to extend the base test case used by your tests.
|
*/

uses(
    TestCase::class,
    // Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature', 'Unit');
