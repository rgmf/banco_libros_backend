<?php
namespace Tests;

use function PHPUnit\Framework\assertTrue;

function assertLending(array $lending): void
{
    assertTrue(array_key_exists('id', $lending));
    assertTrue(array_key_exists('student_id', $lending));
    assertTrue(array_key_exists('book_copy_id', $lending));
    assertTrue(array_key_exists('academic_year_id', $lending));
    assertTrue(array_key_exists('lending_date', $lending));
    assertTrue(array_key_exists('returned_date', $lending));
    assertTrue(array_key_exists('lending_status_id', $lending));
    assertTrue(array_key_exists('returned_status_id', $lending));

    assertTrue(array_key_exists('student', $lending));
    assertTrue(array_key_exists('cohort', $lending['student']));

    assertTrue(array_key_exists('book_copy', $lending));
    assertTrue(array_key_exists('academic_year', $lending));
}
