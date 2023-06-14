<?php
namespace Tests;

use function PHPUnit\Framework\assertTrue;

function assertBook(array $book): void
{
    assertTrue(array_key_exists('id', $book));
    assertTrue(array_key_exists('isbn', $book));
    assertTrue(array_key_exists('title', $book));
    assertTrue(array_key_exists('author', $book));
    assertTrue(array_key_exists('publisher', $book));
    assertTrue(array_key_exists('volumes', $book));
    assertTrue(array_key_exists('grade_id', $book));
    assertTrue(array_key_exists('grade', $book));
    assertTrue(array_key_exists('created_at', $book));
    assertTrue(array_key_exists('updated_at', $book));
}

function assertBookCopy(array $bookCopy): void
{
    assertTrue(array_key_exists('id', $bookCopy));
    assertTrue(array_key_exists('barcode', $bookCopy));
    assertTrue(array_key_exists('comment', $bookCopy));
    assertTrue(array_key_exists('book_id', $bookCopy));
    assertTrue(array_key_exists('status_id', $bookCopy));
    assertTrue(array_key_exists('created_at', $bookCopy));
    assertTrue(array_key_exists('updated_at', $bookCopy));
}
