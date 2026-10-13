<?php
declare(strict_types=1);

namespace App\Helpers;

class Response
{
    public static function json(bool $ok, string $message, array $extra = []): void
    {
        // Discard any output buffered before this point — warnings,
        // notices, stray echos, BOM characters, etc. Any of these
        // would corrupt the JSON payload and break the client's
        // `r.json()` call with "Unexpected token '<'".
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode(array_merge(
            ['success' => $ok, 'message' => $message],
            $extra
        ));
        exit;
    }

    public static function readJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $_POST;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $_POST;
    }
}