<?php

namespace Modules\System\ModuleCenter\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table      = 'modules';
    protected $primaryKey = 'name';
    protected $returnType = 'array';

    protected $allowedFields = [
        'name','title','version','is_enabled','installed_at','updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * Registers a module as installed or updates its metadata if it already exists.
     */
    public function register(string $name, string $title = '', string $version = '', bool $enabled = true): bool
    {
        $name = mb_strtolower(trim($name));
        if ($name === '') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $data = [
            'name'       => $name,
            'title'      => $title !== '' ? $title : $name,
            'version'    => $version,
            'is_enabled' => $enabled ? 1 : 0,
            'updated_at' => $now,
        ];

        $existing = $this->find($name);

        if (!$existing) {
            $data['installed_at'] = $now;
            return (bool)$this->insert($data);
        }

        return (bool)$this->update($name, $data);
    }

    /**
     * Marks a module as removed from the system registry.
     */
    public function unregister(string $name): bool
    {
        $name = mb_strtolower(trim($name));
        if ($name === '') {
            return false;
        }

        try {
            return (bool)$this->delete($name);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Enables or disables a module entry.
     */
    public function setEnabled(string $name, bool $enabled): bool
    {
        $name = mb_strtolower(trim($name));
        if ($name === '') {
            return false;
        }

        return (bool)$this->update($name, [
            'is_enabled' => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
