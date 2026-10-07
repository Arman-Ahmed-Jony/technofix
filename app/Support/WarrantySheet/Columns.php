<?php

namespace App\Support\WarrantySheet;

/**
 * The warranty sheet's columns, in order: field => [header, width]. A–M are the shop's original "Warranty List"
 * columns; the rest were added with the website (email, own stock, notes, and the ID that lets an edited download be
 * imported back over the same claims).
 */
class Columns
{
    public const FIELDS = [
        'number' => ['NO', 6.0],
        'received_on' => ['DATE', 9.6],
        'customer_name' => ['PERSON', 18.3],
        'customer_phone' => ['CONTACT', 11.6],
        'product' => ['DESCRIPTION', 20.3],
        'qty' => ['QTY', 5.6],
        'serial' => ['PRODUCT SN', 13.1],
        'problem' => ['PROBLEM', 14.4],
        'vendor' => ['VENDOR', 23.9],
        'claimed_on' => ['CLAIM DATE', 9.7],
        'returned_on' => ['RECEIVE DATE', 9.9],
        'new_serial' => ['NEW SN', 15.4],
        'status' => ['REMARKS', 12.9],
        'customer_email' => ['EMAIL', 20.0],
        'is_internal' => ['OWN STOCK', 8.0],
        'customer_note' => ['NOTE FOR CUSTOMER', 24.0],
        'staff_note' => ['STAFF NOTE', 24.0],
        'id' => ['SYSTEM ID', 8.0],
    ];

    /** Other spellings accepted when importing. */
    public const ALIASES = [
        'number' => ['NO.', 'SL', 'SL.', 'SL NO', 'S/N'],
        'received_on' => ['RECEIVED', 'RECEIVED DATE', 'DATE RECEIVED'],
        'customer_name' => ['CUSTOMER', 'NAME', 'CUSTOMER NAME'],
        'customer_phone' => ['PHONE', 'MOBILE', 'CONTACT NO', 'CONTACT NUMBER'],
        'product' => ['PRODUCT', 'ITEM'],
        'serial' => ['SN', 'SERIAL', 'SERIAL NO'],
        'returned_on' => ['RETURN DATE', 'RECEIVED BACK'],
        'status' => ['STATUS'],
        'customer_email' => ['E-MAIL', 'CUSTOMER EMAIL'],
        'customer_note' => ['NOTE', 'CUSTOMER NOTE'],
        'staff_note' => ['INTERNAL NOTE'],
        'id' => ['ID'],
    ];

    /** Dates in the sheet. */
    public const DATES = ['received_on', 'claimed_on', 'returned_on'];

    public const DATE_FORMAT = 'dd-mm-yy';

    /** Column letters A, B, … for a 0-based index. */
    public static function letter(int $index): string
    {
        $letter = '';
        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $letter = chr(65 + ($index - 1) % 26).$letter;
        }

        return $letter;
    }

    /** Header text → field (case and spacing don't matter). */
    public static function fieldFor(?string $header): ?string
    {
        $header = strtoupper(preg_replace('/\s+/', ' ', trim((string) $header)));

        foreach (self::FIELDS as $field => [$label]) {
            if ($header === $label || in_array($header, self::ALIASES[$field] ?? [], true)) {
                return $field;
            }
        }

        return null;
    }
}
