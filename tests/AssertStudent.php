<?php
namespace Tests;

use function PHPUnit\Framework\assertTrue;

function assertStudent(array $student): void
{
    assertTrue(array_key_exists('id', $student));
    assertTrue(array_key_exists('nia', $student));
    assertTrue(array_key_exists('name', $student));
    assertTrue(array_key_exists('lastname1', $student));
    assertTrue(array_key_exists('lastname2', $student));
    assertTrue(array_key_exists('cohort_id', $student));
    assertTrue(array_key_exists('picture', $student));
    assertTrue(array_key_exists('nationality', $student));
    assertTrue(array_key_exists('address', $student));
    assertTrue(array_key_exists('city', $student));
    assertTrue(array_key_exists('cp', $student));
    assertTrue(array_key_exists('phone1', $student));
    assertTrue(array_key_exists('phone2', $student));
    assertTrue(array_key_exists('phone3', $student));
    assertTrue(array_key_exists('name_father', $student));
    assertTrue(array_key_exists('lastname1_father', $student));
    assertTrue(array_key_exists('lastname2_father', $student));
    assertTrue(array_key_exists('email_father', $student));
    assertTrue(array_key_exists('name_mother', $student));
    assertTrue(array_key_exists('lastname1_mother', $student));
    assertTrue(array_key_exists('lastname2_mother', $student));
    assertTrue(array_key_exists('email_mother', $student));
    assertTrue(array_key_exists('cohort', $student));
    assertTrue(array_key_exists('lendings', $student));
}
