<?php

namespace Modules\System\Menu\Controllers;

use Modules\System\Core\Controllers\AdminBaseController;
use Modules\System\Menu\Libraries\Menu\MenuService;
use Modules\System\Menu\Models\MenuItemModel;

class MenuController extends AdminBaseController
{
    private MenuService $svc;
    private MenuItemModel $model;

    public function __construct()
    {
        $this->svc   = new MenuService();
        $this->model = new MenuItemModel();
    }

    public function index(): string
    {
        $area = (string)($this->request->getGet('area') ?? 'admin');
        $menuKey = (string)($this->request->getGet('menu_key') ?? 'sidebar');

        $items = $this->svc->list($area, $menuKey);

        return $this->render('Modules\\System\\Menu\\Views\\admin\\index', [
            'title'   => 'Menu',
            'items'   => $items,
            'area'    => $area,
            'menuKey' => $menuKey,
        ]);
    }

    public function sync()
    {
        $area = (string)($this->request->getPost('area') ?? 'admin');
        $menuKey = (string)($this->request->getPost('menu_key') ?? 'sidebar');

        $this->svc->syncSystemItemsFromManifests($area, $menuKey);
        return redirect()->to(site_url('admin/system/menu?area=' . urlencode($area) . '&menu_key=' . urlencode($menuKey)))
            ->with('success', 'Synced');
    }

    public function create(): string
    {
        return $this->render('Modules\\System\\Menu\\Views\\admin\\form', [
            'title' => 'Create menu item',
            'mode'  => 'create',
            'item'  => [
                'area' => 'admin',
                'menu_key' => 'sidebar',
                'item_key' => 'custom.item',
                'label' => 'New item',
                'icon' => 'bi bi-link-45deg',
                'url' => 'admin',
                'weight' => 0,
                'is_enabled' => 1,
                'is_system' => 0,
            ],
        ]);
    }

    public function store()
    {
        $data = [
            'area' => (string)$this->request->getPost('area'),
            'menu_key' => (string)$this->request->getPost('menu_key'),
            'item_key' => (string)$this->request->getPost('item_key'),
            'label' => (string)$this->request->getPost('label'),
            'icon' => (string)($this->request->getPost('icon') ?? ''),
            'url' => (string)($this->request->getPost('url') ?? ''),
            'weight' => (int)($this->request->getPost('weight') ?? 0),
            'is_enabled' => $this->request->getPost('is_enabled') ? 1 : 0,
        ];

        if ($data['area'] === '') $data['area'] = 'admin';
        if ($data['menu_key'] === '') $data['menu_key'] = 'sidebar';
        if ($data['item_key'] === '') $data['item_key'] = 'custom.' . time();

        $ok = $this->svc->create($data);
        if ($ok) return redirect()->to(site_url('admin/system/menu'))->with('success', 'Created');
        return redirect()->back()->withInput()->with('error', 'Create failed (duplicate key?)');
    }

    public function edit(int $id): string
    {
        $item = $this->model->find($id);
        if (!$item) {
            return $this->render('Modules\\System\\Menu\\Views\\admin\\not_found', [
                'title' => 'Not found',
                'message' => 'Menu item not found',
            ]);
        }

        return $this->render('Modules\\System\\Menu\\Views\\admin\\form', [
            'title' => 'Edit menu item #' . $id,
            'mode'  => 'edit',
            'item'  => $item,
        ]);
    }

    public function update(int $id)
    {
        $item = $this->model->find($id);
        if (!$item) return redirect()->to(site_url('admin/system/menu'))->with('error', 'Not found');

        $data = [
            'area' => (string)$this->request->getPost('area'),
            'menu_key' => (string)$this->request->getPost('menu_key'),
            'label' => (string)$this->request->getPost('label'),
            'icon' => (string)($this->request->getPost('icon') ?? ''),
            'url' => (string)($this->request->getPost('url') ?? ''),
            'weight' => (int)($this->request->getPost('weight') ?? 0),
            'is_enabled' => $this->request->getPost('is_enabled') ? 1 : 0,
        ];

        if ($data['area'] === '') $data['area'] = 'admin';
        if ($data['menu_key'] === '') $data['menu_key'] = 'sidebar';

        $ok = $this->svc->update($id, $data);
        if ($ok) return redirect()->to(site_url('admin/system/menu'))->with('success', 'Updated');
        return redirect()->back()->withInput()->with('error', 'Update failed');
    }

    public function delete(int $id)
    {
        if ($this->svc->delete($id)) {
            return redirect()->to(site_url('admin/system/menu'))->with('success', 'Deleted');
        }
        return redirect()->to(site_url('admin/system/menu'))->with('error', 'Delete failed (system item?)');
    }

    public function toggle(int $id)
    {
        if ($this->svc->toggle($id)) {
            return redirect()->back()->with('success', 'Toggled');
        }
        return redirect()->back()->with('error', 'Toggle failed');
    }
}
