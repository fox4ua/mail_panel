<?php

declare(strict_types=1);

namespace Modules\System\Settings\Libraries;

use Modules\System\Settings\Config\SettingsSchema;
use CodeIgniter\Validation\Validation;

/**
 * SettingsManager — "нормальный" менеджер настроек:
 * - знает схему (какие поля есть)
 * - применяет дефолты
 * - валидирует значения
 * - сохраняет через SettingsStore
 */
class SettingsManager
{
    private SettingsStore $store;
    private SettingsSchema $schema;
    private Validation $validation;

    public function __construct(?SettingsStore $store = null, ?SettingsSchema $schema = null, ?Validation $validation = null)
    {
        $this->store      = $store ?? new SettingsStore();
        $this->schema     = $schema ?? config(SettingsSchema::class);
        $this->validation = $validation ?? \Config\Services::validation();
    }

    public function groups(): array
    {
        return $this->schema->groups;
    }

    public function getDefault(string $key): mixed
    {
        foreach ($this->schema->groups as $g) {
            foreach (($g['fields'] ?? []) as $f) {
                if (($f['key'] ?? '') === $key) {
                    return $f['default'] ?? null;
                }
            }
        }
        return null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($default === null) {
            $default = $this->getDefault($key);
        }
        return $this->store->get($key, $default);
    }

    /**
     * Значения для формы: key => value (с учётом дефолтов)
     */
    public function valuesForForm(): array
    {
        $values = [];
        foreach ($this->schema->groups as $groupKey => $group) {
            foreach (($group['fields'] ?? []) as $field) {
                $k = (string) ($field['key'] ?? '');
                if ($k === '') {
                    continue;
                }
                $values[$k] = $this->get($k, $field['default'] ?? null);
            }
        }
        return $values;
    }

    /**
     * Сохранение данных из POST массива settings[key] => value.
     * Возвращает массив ошибок: key => message.
     */
    public function saveFromArray(array $posted): array
    {
        $errors = [];

        foreach ($this->schema->groups as $groupName => $group) {
            foreach (($group['fields'] ?? []) as $field) {
                $key   = (string) ($field['key'] ?? '');
                $type  = (string) ($field['type'] ?? 'string');
                $rules = (string) ($field['rules'] ?? 'permit_empty');
                $auto  = !empty($field['autoload']) ? 1 : 0;

                if ($key === '') {
                    continue;
                }

                $value = $posted[$key] ?? null;

                // Нормализация по типу
                $value = $this->normalize($type, $value);

                // Валидация значения (1 поле)
                if (!$this->validateValue($value, $rules, $msg)) {
                    $errors[$key] = $msg;
                    continue;
                }

                try {
                    $this->store->set($key, $value, $this->storageType($type), (string) $groupName, $auto, null);
                } catch (\Throwable $e) {
                    $errors[$key] = $e->getMessage();
                    continue;
                }
            }
        }

        return $errors;
    }

    /**
     * Прокинуть значения в Renderer->share() по карте schema->shareMap
     */
    public function shareToRenderer(object $renderer): void
    {
        foreach ($this->schema->shareMap as $settingKey => $varName) {
            if (!method_exists($renderer, 'share')) {
                break;
            }
            $renderer->share((string) $varName, $this->get((string) $settingKey, null));
        }
    }

    private function validateValue(mixed $value, string $rules, ?string &$message = null): bool
    {
        $message = null;

        // Используем временное поле "v"
        $this->validation->reset();
        $this->validation->setRules(['v' => $rules]);

        $data = ['v' => $value];

        if ($this->validation->run($data) === false) {
            $message = (string) ($this->validation->getError('v') ?? 'Invalid value');
            return false;
        }

        return true;
    }

    private function normalize(string $type, mixed $value): mixed
    {
        if ($type === 'bool') {
            // чекбокс: может прийти '1' или отсутствовать
            return ($value === '1' || $value === 1 || $value === true || $value === 'true' || $value === 'on');
        }

        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int'  => (int) $value,
            'text' => (string) $value,
            default => (string) $value,
        };
    }

    private function storageType(string $type): string
    {
        return match ($type) {
            'bool' => 'bool',
            'int'  => 'int',
            'text' => 'text',
            'select' => 'select',
            default => 'string',
        };
    }
}
