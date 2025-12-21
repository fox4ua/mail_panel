<?php

declare(strict_types=1);

namespace Modules\System\Menu\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\System\Menu\Libraries\MenuTree;
use Modules\System\Menu\Models\MenuItemModel;
use Modules\System\Menu\Models\MenuModel;

class MenuItemsController extends Controller
{
    public function index(int $menuId): string
    {
        $menuModel = new MenuModel();
        $menu      = $menuModel->find($menuId);

        if (!$menu) {
            return 'Menu not found';
        }

        $itemModel = new MenuItemModel();
        $items     = $itemModel->listByMenu($menuId);

        $tree = MenuTree::build($items);
        $flat = MenuTree::flatten($tree);

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Menu items: ' . (string) $menu['title']);
        }

        $data = [
            'menu'  => $menu,
            'items' => $flat,
            'success' => session()->getFlashdata('success'),
            'error' => session()->getFlashdata('error'),
        ];

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/items_index', $data);
        }

        return view('menu/items_index', $data);
    }

    public function create(int $menuId): string
    {
        $menuModel = new MenuModel();
        $menu      = $menuModel->find($menuId);

        if (!$menu) {
            return 'Menu not found';
        }

        $itemModel = new MenuItemModel();

        $data = [
            'menu'   => $menu,
            'item'   => null,
            'parents'=> $itemModel->listParents($menuId),
            'errors' => session()->getFlashdata('errors') ?? [],
        ];

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Add item: ' . (string) $menu['title']);
        }

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/items_form', $data);
        }

        return view('menu/items_form', $data);
    }

    public function store(int $menuId): ResponseInterface
    {
        $menuModel = new MenuModel();
        $menu      = $menuModel->find($menuId);

        if (!$menu) {
            return redirect()->to(site_url('admin/system/menus'))->with('error', 'Menu not found');
        }

        $post = $this->request->getPost();

        $data = $this->mapItemPost($menuId, $post);

        $model = new MenuItemModel();
        if ($model->insert($data) === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors() ?: ['Save failed']);
        }

        return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('success', 'Item added');
    }

    public function edit(int $menuId, int $itemId): string
    {
        $menuModel = new MenuModel();
        $menu      = $menuModel->find($menuId);

        if (!$menu) {
            return 'Menu not found';
        }

        $model = new MenuItemModel();
        $item  = $model->find($itemId);

        if (!$item || (int) $item['menu_id'] !== $menuId) {
            return 'Item not found';
        }

        $parents = $model->listParents($menuId);
        // remove itself from parents list
        $parents = array_values(array_filter($parents, fn($p) => (int) $p['id'] !== $itemId));

        $data = [
            'menu'    => $menu,
            'item'    => $item,
            'parents' => $parents,
            'errors'  => session()->getFlashdata('errors') ?? [],
        ];

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Edit item: ' . (string) $menu['title']);
        }

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/items_form', $data);
        }

        return view('menu/items_form', $data);
    }

    public function update(int $menuId, int $itemId): ResponseInterface
    {
        $model = new MenuItemModel();
        $item  = $model->find($itemId);

        if (!$item || (int) $item['menu_id'] !== $menuId) {
            return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('error', 'Item not found');
        }

        $post = $this->request->getPost();

        $data = $this->mapItemPost($menuId, $post);

        if ($model->update($itemId, $data) === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors() ?: ['Save failed']);
        }

        return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('success', 'Item updated');
    }

    public function delete(int $menuId, int $itemId): ResponseInterface
    {
        $model = new MenuItemModel();
        $item  = $model->find($itemId);

        if (!$item || (int) $item['menu_id'] !== $menuId) {
            return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('success', 'Already deleted');
        }

        if ($model->delete($itemId) === false) {
            return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('error', 'Delete failed');
        }

        return redirect()->to(site_url('admin/system/menus/' . $menuId . '/items'))->with('success', 'Item deleted');
    }

    public function toggle(int $menuId, int $itemId): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(404);
        }

        $model = new MenuItemModel();
        $item  = $model->find($itemId);

        if (!$item || (int) $item['menu_id'] !== $menuId) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Not found']);
        }

        $new = ((int) ($item['is_active'] ?? 0)) ? 0 : 1;

        $ok = $model->update($itemId, ['is_active' => $new]);
        if ($ok === false) {
            return $this->response->setJSON(['ok' => false, 'error' => 'Update failed']);
        }

        return $this->response->setJSON(['ok' => true, 'is_active' => $new]);
    }

    private function mapItemPost(int $menuId, array $post): array
    {
        $parentId = $post['parent_id'] ?? null;
        $parentId = ($parentId === '' || $parentId === null) ? null : (int) $parentId;

        $attrsJson = $post['attrs_json'] ?? null;
        $attrsJson = ($attrsJson === '' || $attrsJson === null) ? null : (string) $attrsJson;

        // Soft JSON validation (do not block if empty)
        if ($attrsJson !== null) {
            json_decode($attrsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // keep raw value; validation rule in model is permit_empty
            }
        }

        return [
            'menu_id'    => $menuId,
            'parent_id'  => $parentId,
            'title'      => (string) ($post['title'] ?? ''),
            'url'        => ($post['url'] ?? null) !== null ? (string) $post['url'] : null,
            'route_name' => ($post['route_name'] ?? null) !== null ? (string) $post['route_name'] : null,
            'icon'       => ($post['icon'] ?? null) !== null ? (string) $post['icon'] : null,
            'target'     => ($post['target'] ?? null) !== null ? (string) $post['target'] : null,
            'css_class'  => ($post['css_class'] ?? null) !== null ? (string) $post['css_class'] : null,
            'attrs_json' => $attrsJson,
            'sort_order' => (int) ($post['sort_order'] ?? 0),
            'is_active'  => (($post['is_active'] ?? '0') === '1') ? 1 : 0,
        ];
    }
}
