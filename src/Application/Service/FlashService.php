<?php

declare(strict_types=1);

namespace App\Application\Service;

final class FlashService
{
    public static function success(string $message): void
    {
        $_SESSION['flash_success'] = $message;
    }

    public static function error(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    public static function getSuccess(): ?string
    {
        $message = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);

        return $message;
    }

    public static function getError(): ?string
    {
        $message = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        return $message;
    }
}
