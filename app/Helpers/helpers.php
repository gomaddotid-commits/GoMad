<?php
// File: app/Helpers/helpers.php
// Deskripsi: Helper functions global

if (!function_exists('ensure_array')) {
    function ensure_array($data): array {
        if (is_array($data)) {
            return $data;
        }
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        if (is_object($data)) {
            return (array) $data;
        }
        return [];
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah($number): string {
        return 'Rp ' . number_format((float) $number, 0, ',', '.');
    }
}