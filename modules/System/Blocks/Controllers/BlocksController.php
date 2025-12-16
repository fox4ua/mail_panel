<?php

namespace Modules\System\Blocks\Controllers;

use Modules\System\Core\Controllers\AdminBaseController;
use Modules\System\Blocks\Models\BlockInstanceModel;

class BlocksController extends AdminBaseController
{
    private BlockInstanceModel $model;

    public function __construct()
    {
        $this->model = new BlockInstanceModel();
    }

    public function index(): string
    {
        $blocks = [];
        try { $blocks = $this->model->orderBy('id','DESC')->findAll(); } catch (\Throwable $e) {}

        return $this->render('Modules\\System\\Blocks\\Views\\admin\\blocks_index', [
            'title'  => 'Blocks',
            'blocks' => $blocks,
        ]);
    }

    public function create(): string
    {
        return $this->render('Modules\\System\\Blocks\\Views\\admin\\blocks_form', [
            'title' => 'Create block',
            'mode'  => 'create',
            'block' => [
                'type' => 'html',
                'title' => '',
                'settings_json' => json_encode(['html' => '<p>Hello block</p>'], JSON_UNESCAPED_UNICODE),
                'is_enabled' => 1,
            ],
        ]);
    }

    public function store()
    {
        $type = (string)($this->request->getPost('type') ?? 'html');
        $title = (string)($this->request->getPost('title') ?? '');
        $html = (string)($this->request->getPost('html') ?? '');
        $enabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $ok = $this->model->insert([
            'type' => $type,
            'title' => $title,
            'settings_json' => json_encode(['html' => $html], JSON_UNESCAPED_UNICODE),
            'is_enabled' => $enabled,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $ok
            ? redirect()->to(site_url('admin/system/blocks'))->with('success', 'Block created')
            : redirect()->back()->withInput()->with('error', 'Create failed');
    }

    public function edit(int $id): string
    {
        $block = $this->model->find($id);
        if (!$block) {
            return $this->render('Modules\\System\\Blocks\\Views\\admin\\not_found', [
                'title' => 'Not found',
                'message' => 'Block not found',
            ]);
        }

        $settings = json_decode($block['settings_json'] ?? '{}', true) ?: [];
        $block['html'] = $settings['html'] ?? '';

        return $this->render('Modules\\System\\Blocks\\Views\\admin\\blocks_form', [
            'title' => 'Edit block #' . $id,
            'mode'  => 'edit',
            'block' => $block,
        ]);
    }

    public function update(int $id)
    {
        if (!$this->model->find($id)) {
            return redirect()->to(site_url('admin/system/blocks'))->with('error', 'Block not found');
        }

        $title = (string)($this->request->getPost('title') ?? '');
        $html = (string)($this->request->getPost('html') ?? '');
        $enabled = $this->request->getPost('is_enabled') ? 1 : 0;

        $ok = $this->model->update($id, [
            'title' => $title,
            'settings_json' => json_encode(['html' => $html], JSON_UNESCAPED_UNICODE),
            'is_enabled' => $enabled,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $ok
            ? redirect()->to(site_url('admin/system/blocks'))->with('success', 'Block updated')
            : redirect()->back()->withInput()->with('error', 'Update failed');
    }

    public function delete(int $id)
    {
        $ok = $this->model->delete($id);
        return $ok
            ? redirect()->to(site_url('admin/system/blocks'))->with('success', 'Block deleted')
            : redirect()->to(site_url('admin/system/blocks'))->with('error', 'Delete failed');
    }
}
