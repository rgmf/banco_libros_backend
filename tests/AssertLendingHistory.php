<?php
namespace Tests;

use function PHPUnit\Framework\assertTrue;

function assertByBookCopyLendingHistory(array $bookCopy): void
{
    assertTrue(array_key_exists('id', $bookCopy));
    assertTrue(array_key_exists('barcode', $bookCopy));
    assertTrue(array_key_exists('comment', $bookCopy));

    assertTrue(array_key_exists('book', $bookCopy));
    assertTrue(array_key_exists('id', $bookCopy['book']));
    assertTrue(array_key_exists('isbn', $bookCopy['book']));
    assertTrue(array_key_exists('title', $bookCopy['book']));
    assertTrue(array_key_exists('author', $bookCopy['book']));
    assertTrue(array_key_exists('publisher', $bookCopy['book']));
    assertTrue(array_key_exists('volumes', $bookCopy['book']));

    assertTrue(array_key_exists('lendings', $bookCopy));
    foreach ($bookCopy['lendings'] as $lending) {
        assertTrue(array_key_exists('id', $lending));
        assertTrue(array_key_exists('lending_date', $lending));
        assertTrue(array_key_exists('returned_date', $lending));

        assertTrue(array_key_exists('student', $lending));
        assertTrue(array_key_exists('id', $lending['student']));
        assertTrue(array_key_exists('nia', $lending['student']));
        assertTrue(array_key_exists('name', $lending['student']));
        assertTrue(array_key_exists('lastname1', $lending['student']));
        assertTrue(array_key_exists('lastname2', $lending['student']));
        assertTrue(array_key_exists('name_father', $lending['student']));
        assertTrue(array_key_exists('lastname1_father', $lending['student']));
        assertTrue(array_key_exists('lastname2_father', $lending['student']));
        assertTrue(array_key_exists('email_father', $lending['student']));
        assertTrue(array_key_exists('name_mother', $lending['student']));
        assertTrue(array_key_exists('lastname1_mother', $lending['student']));
        assertTrue(array_key_exists('lastname2_mother', $lending['student']));
        assertTrue(array_key_exists('email_mother', $lending['student']));
        assertTrue(array_key_exists('is_member', $lending['student']));

        assertTrue(array_key_exists('academic_year', $lending));
        assertTrue(array_key_exists('lending_status', $lending));
        assertTrue(array_key_exists('returned_status', $lending));
    }
}

function assertByStudentLendingHistory(array $student): void
{
    assertTrue(array_key_exists('id', $student));
    assertTrue(array_key_exists('nia', $student));
    assertTrue(array_key_exists('name', $student));
    assertTrue(array_key_exists('lastname1', $student));
    assertTrue(array_key_exists('lastname2', $student));
    assertTrue(array_key_exists('name_father', $student));
    assertTrue(array_key_exists('lastname1_father', $student));
    assertTrue(array_key_exists('lastname2_father', $student));
    assertTrue(array_key_exists('email_father', $student));
    assertTrue(array_key_exists('name_mother', $student));
    assertTrue(array_key_exists('lastname1_mother', $student));
    assertTrue(array_key_exists('lastname2_mother', $student));
    assertTrue(array_key_exists('email_mother', $student));
    assertTrue(array_key_exists('is_member', $student));

    assertTrue(array_key_exists('lendings', $student));
    foreach ($student['lendings'] as $lending) {
        assertTrue(array_key_exists('id', $lending));
        assertTrue(array_key_exists('lending_date', $lending));
        assertTrue(array_key_exists('returned_date', $lending));

        assertTrue(array_key_exists('book_copy', $lending));
        assertTrue(array_key_exists('academic_year', $lending));
        assertTrue(array_key_exists('lending_status', $lending));
        assertTrue(array_key_exists('returned_status', $lending));
    }
}
