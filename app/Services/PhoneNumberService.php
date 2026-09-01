<?php

namespace App\Services;

class PhoneNumberService
{
    /**
     * Normalize a phone number to standard Pakistani format: 10 or 11 digits starting with 0.
     * e.g., +923001234567 -> 03001234567
     *       00924231112233 -> 04231112233
     *       0300-1234567 -> 03001234567
     *       3001234567 -> 03001234567
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function normalize(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        // 1. Remove all non-numeric characters
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // 2. If it starts with 0092:
        if (str_starts_with($clean, '0092')) {
            $clean = '0' . substr($clean, 4);
        }
        // 3. Else if it starts with 92 and has at least 11 digits:
        elseif (str_starts_with($clean, '92') && strlen($clean) >= 11) {
            $clean = '0' . substr($clean, 2);
        }

        // 4. If it does not start with 0, and length is 9 or 10, prepend 0 (e.g. 3001234567 -> 03001234567)
        if (!str_starts_with($clean, '0') && (strlen($clean) === 9 || strlen($clean) === 10)) {
            $clean = '0' . $clean;
        }

        return $clean;
    }

    /**
     * Format a normalized phone number for UI display.
     * e.g., 03001234567 -> 0300-1234567
     *       04231112233 -> 042-31112233
     *       0512333444 -> 051-2333444
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function formatForDisplay(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        $clean = self::normalize($phone);

        // 1. Mobile number starting with 03 (11 digits): 03xx-xxxxxxx
        if (preg_match('/^03\d{9}$/', $clean)) {
            return substr($clean, 0, 4) . '-' . substr($clean, 4);
        }

        // 2. Landline number starting with 021 or 042 (Karachi/Lahore): 0xx-xxxxxxxx
        if (preg_match('/^0(21|42)\d{8}$/', $clean)) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3);
        }

        // 3. Other landlines (10 digits starting with 0): 0xxx-xxxxxxx
        if (preg_match('/^0\d{9}$/', $clean)) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3);
        }

        // 4. Alternate landlines (11 digits starting with 0): 0xxx-xxxxxxxx
        if (preg_match('/^0\d{10}$/', $clean)) {
            return substr($clean, 0, 3) . '-' . substr($clean, 3);
        }

        return $phone;
    }

    /**
     * Convert to international format without plus prefix (e.g. 923001234567).
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function toInternational(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        $clean = self::normalize($phone);

        if (str_starts_with($clean, '0')) {
            return '92' . substr($clean, 1);
        }

        return $clean;
    }

    /**
     * Convert to international format with plus prefix (e.g. +923001234567).
     *
     * @param string|null $phone
     * @return string|null
     */
    public static function toInternationalWithPlus(?string $phone): ?string
    {
        if (empty($phone)) {
            return $phone;
        }

        $clean = self::normalize($phone);

        if (str_starts_with($clean, '0')) {
            return '+92' . substr($clean, 1);
        }

        return '+' . $clean;
    }

    /**
     * Check if a phone number is a valid Pakistani phone/landline number.
     * Pattern: Starts with 0, followed by 9 or 10 digits.
     *
     * @param string|null $phone
     * @return bool
     */
    public static function isValid(?string $phone): bool
    {
        $clean = self::normalize($phone);
        
        if (empty($clean)) {
            return false;
        }

        // 1. Mobile numbers (starting with 03) must be exactly 11 digits
        if (str_class_starts_with_03($clean)) {
            return (bool) preg_match('/^03[0-9]{9}$/', $clean);
        }

        // 2. Landline numbers must start with 0 (but not 03 or 00) and be 10 or 11 digits
        return (bool) preg_match('/^0[124-9][0-9]{8,9}$/', $clean);
    }
}

if (!function_exists('str_class_starts_with_03')) {
    function str_class_starts_with_03($str) {
        return str_starts_with($str, '03');
    }
}
