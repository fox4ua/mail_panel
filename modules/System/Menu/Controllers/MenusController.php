<?php

declare(strict_types=1);

namespace Modules\System\Menu\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\System\Menu\Models\MenuModel;

class MenusController extends Controller
{
    public function index(): string
    {
        $model = new MenuModel();
        $menus = $model->listAll();

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Menus');
        }

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/menus_index', ['menus' => $menus]);
        }

        return view('menu/menus_index', ['menus' => $menus]);
    }

    public function create(): string
    {
        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Create menu');
        }

        $data = ['menu' => null, 'errors' => session()->getFlashdata('errors') ?? []];

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/menus_form', $data);
        }

        return view('menu/menus_form', $data);
    }

    public function store(): ResponseInterface
    {
        $post = $this->request->getPost();

        $data = [
            'menu_key'    => (string) ($post['menu_key'] ?? ''),
            'title'       => (string) ($post['title'] ?? ''),
            'description' => ($post['description'] ?? null) !== null ? (string) $post['description'] : null,
            'is_active'   => (($post['is_active'] ?? '0') === '1') ? 1 : 0,
        ];

        $model = new MenuModel();
        if ($model->insert($data) === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors() ?: ['Save failed']);
        }

        return redirect()->to(site_url('admin/system/menus'))->with('success', 'Menu created');
    }

    public function edit(int $id): string
    {
        $model = new MenuModel();
        $menu  = $model->find($id);

        if (!$menu) {
            return 'Menu not found';
        }

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('Edit menu');
        }

        $data = ['menu' => $menu, 'errors' => session()->getFlashdata('errors') ?? []];

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('menu/menus_form', $data);
        }

        return view('menu/menus_form', $data);
    }

    public function update(int $id): ResponseInterface
    {
        $model = new MenuModel();
        $menu  = $model->find($id);

        if (!$menu) {
            return redirect()->to(site_url('admin/system/menus'))->with('error', 'Menu not found');
        }

        $post = $this->request->getPost();

        $data = [
            'menu_key'    => (string) ($post['menu_key'] ?? ''),
            'title'       => (string) ($post['title'] ?? ''),
            'description' => ($post['description'] ?? null) !== null ? (string) $post['description'] : null,
            'is_active'   => (($post['is_active'] ?? '0') === '1') ? 1 : 0,
        ];

        if ($model->update($id, $data) === false) {
            return redirect()->back()->withInput()->with('errors', $model->errors() ?: ['Save failed']);
        }

        return redirect()->to(site_url('admin/system/menus'))->with('success', 'Menu updated');
    }

    public function delete(int $id): ResponseInterface
    {
        $model = new MenuModel();
        $menu  = $model->find($id);

        if (!$menu) {
            return redirect()->to(site_url('admin/system/menus'))->with('success', 'Already deleted');
        }

        if ($model->delete($id) === false) {
            return redirect()->to(site_url('admin/system/menus'))->with('error', 'Delete failed');
        }

        return redirect()->to(site_url('admin/system/menus'))->with('success', 'Menu deleted');
    }
}
