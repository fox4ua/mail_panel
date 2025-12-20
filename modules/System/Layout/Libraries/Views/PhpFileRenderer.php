<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Views;

use RuntimeException;
use Throwable;

class PhpFileRenderer
{
    public function render(string $file, array $vars): string
    {
        if (!is_file($file)) {
            throw new RuntimeException('Template file not found: ' . $file);
        }

        // фиксируем текущий уровень буферизации, чтобы корректно очистить всё,
        // что было открыто внутри шаблона, если там упадёт исключение
        $level = ob_get_level();

        extract($vars, EXTR_SKIP);

        ob_start();
        try {
            include $file;
            return (string) ob_get_clean();
        } catch (Throwable $e) {
            // чистим только те буферы, которые открылись после $level
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
