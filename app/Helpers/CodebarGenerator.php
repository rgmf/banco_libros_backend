<?php
namespace App\Helpers;

class CodebarGenerator
{
    /**
     * Generate a cryptographic secure random number of $length digits.
     */
    public static function randomNumber(int $length): int
    {
        $maxNumber = pow(10, $length) - 1;
        $bytesNeeded = ceil(log($maxNumber + 1, 256));
        return hexdec(bin2hex(random_bytes($bytesNeeded))) % ($maxNumber + 1);
    }

    /**
     * Generates a EAN13 codebar.
     */
    public static function ean13(): string
    {
        // Build a 12 digits number
        $numberString = strval(CodebarGenerator::randomNumber(12));

        // Add 0's to complete the lenght of 12
        $numberString = str_pad($numberString, 12, '0', STR_PAD_LEFT);

        // Compute control digit according to EAN13 algorithm
        $evenSum = $numberString[1] + $numberString[3] + $numberString[5] + $numberString[7] + $numberString[9] + $numberString[11];
        $oddSum = $numberString[0] + $numberString[2] + $numberString[4] + $numberString[6] + $numberString[8] + $numberString[10];
        $totalSum = (3 * $oddSum) + $evenSum;
        $checkDigit = 10 - ($totalSum % 10);
        if ($checkDigit == 10) {
            $checkDigit = 0;
        }

        // The barcode is the result of append to the 12 digits number the control
        return $numberString . $checkDigit;
    }
}
