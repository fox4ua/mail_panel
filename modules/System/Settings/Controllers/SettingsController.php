<?php

declare(strict_types=1);

namespace Modules\System\Settings\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\System\Settings\Libraries\SettingsManager;

class SettingsController extends Controller
{
    public function index(): string
    {
        $manager = new SettingsManager();

        $data = [
            'groups' => $manager->groups(),
            'values' => $manager->valuesForForm(),
            'errors' => session()->getFlashdata('settings_errors') ?? [],
        ];

        // Интеграция с вашим Renderer (Layout module)
        $render = null;

        if (property_exists($this, 'render')) {
            $render = $this->render;
        } else {
            // Если у вас сервис может называться иначе — замените.
            $render = function_exists('service') ? service('render') : null;
        }

        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Settings');
        }

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('settings/index', $data);
        }

        // Фолбэк (если Renderer не используется): подключите view path сами или адаптируйте.
        return 'Renderer service not found. Configure service(\'render\') or use your BaseController with $this->render.';
    }

    public function save(): ResponseInterface
    {
        $manager = new SettingsManager();

        $posted = $this->request->getPost('settings');
        if (!is_array($posted)) {
            $posted = [];
        }

        // Важно для bool: если чекбокс выключен, ключ может отсутствовать.
        // Добавим "ложные" значения для всех bool-полей, чтобы они гарантированно сохранялись.
        foreach ($manager->groups() as $g) {
            foreach (($g['fields'] ?? []) as $f) {
                if (($f['type'] ?? '') === 'bool') {
                    $k = (string) ($f['key'] ?? '');
                    if ($k !== '' && !array_key_exists($k, $posted)) {
                        $posted[$k] = '0';
                    }
                }
            }
        }

        $errors = $manager->saveFromArray($posted);

        if (!empty($errors)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Some settings are invalid')
                ->with('settings_errors', $errors);
        }

        return redirect()->to(site_url('admin/system/settings'))->with('success', 'Settings saved');
    }
}
