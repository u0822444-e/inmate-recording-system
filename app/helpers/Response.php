<?php
declare(strict_types=1);

namespace App\Helpers;

class Response
{
    public static function json(bool $ok, string $message, array $extra = []): void
    {
        header('Content-Type: application/json; charset=utf-8');
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