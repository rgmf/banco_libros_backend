<?php
namespace Tests;

require_once 'AssertBook.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // It is often useful to reset your database after each test so that data
    // from a previous test does not interfere with subsequent tests.
    use RefreshDatabase;
}
