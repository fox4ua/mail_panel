<?php

namespace Modules\System\ModuleCenter\Controllers;

use Modules\System\Core\Controllers\AdminBaseController;
use Modules\System\ModuleCenter\Libraries\ModuleCenter\ModuleService;

class ModulesController extends AdminBaseController
{
    public function index()
    {
        $svc = new ModuleService();

        return $this->render('Modules\\System\\ModuleCenter\\Views\\admin\\index', [
            'title'   => 'Modules',
            'modules' => $svc->listModules(),
        ]);
    }

    public function enable(string $name)
    {
        $svc = new ModuleService();

        if (!$svc->enable($name)) {
            return redirect()->back()->with('error', 'Не удалось включить модуль');
        }

        return redirect()->to(site_url('admin/system/modules'))->with('success', 'Модуль включён');
    }

    public function disable(string $name)
    {
        $svc = new ModuleService();

        if (!$svc->disable($name)) {
            return redirect()->back()->with('error', 'Не удалось отключить модуль');
        }

        return redirect()->to(site_url('admin/system/modules'))->with('success', 'Модуль отключён');
    }

public function rescan()
{
    (new \Modules\System\ModuleCenter\Libraries\ModuleCenter\ModuleService())->rescan();
    return redirect()->to(site_url('admin/system/modules'))->with('success', 'Rescan completed');
}


}

