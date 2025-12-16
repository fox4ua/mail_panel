<?php

namespace Modules\System\ModuleCenter\Controllers;

use Modules\System\Core\Controllers\AdminBaseController;
use Modules\System\ModuleCenter\Libraries\ModuleCenter\ModuleService;
use Modules\System\ModuleCenter\Libraries\ModuleCenter\ModulePackageService;

class ModuleInstallController extends AdminBaseController
{
    public function uploadForm()
    {
        return $this->render('Modules\System\ModuleCenter\Views\admin\upload', [
            'title' => 'Upload module',
        ]);
    }

    public function upload()
    {
        $zip = $this->request->getFile('module_zip');
        if (!$zip || !$zip->isValid()) {
            return redirect()->back()->with('error', 'Некорректный ZIP-файл');
        }

        $pkg = new ModulePackageService();
        $res = $pkg->deployFromZip($zip);

        if (empty($res['ok'])) {
            $msg = (string)($res['error'] ?? 'Не удалось загрузить модуль');
            if (!empty($res['details'])) {
                $msg .= ' (' . json_encode($res['details'], JSON_UNESCAPED_UNICODE) . ')';
            }
            return redirect()->back()->with('error', $msg);
        }

        $mode = (string)($res['mode'] ?? 'install');
        $name = (string)($res['moduleKey'] ?? '');

        $text = $mode === 'update' ? 'Модуль обновлён' : 'Модуль установлен';
        if ($name !== '') {
            $text .= ': ' . $name;
        }

        return redirect()->to(site_url('admin/system/modules'))->with('success', $text);
    }

    public function install(string $name)
    {
        $svc = new ModuleService();

        if (!$svc->installOrUpdate($name)) {
            return redirect()->back()->with('error', $svc->getLastError() ?? 'Не удалось установить модуль');
        }

        return redirect()->to(site_url('admin/system/modules'))->with('success', 'Модуль установлен');
    }

    public function uninstall(string $name)
    {
        $svc = new ModuleService();
        $res = $svc->uninstall($name, false);

        if (empty($res['ok'])) {
            return redirect()->back()->with('error', (string)($res['error'] ?? 'Не удалось удалить модуль'));
        }

        return redirect()->to(site_url('admin/system/modules'))->with('success', 'Модуль удалён');
    }
}
