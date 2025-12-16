<?php

namespace Modules\System\Blocks\Controllers;

use Modules\System\Core\Controllers\AdminBaseController;
use Modules\System\Blocks\Models\BlockInstanceModel;
use Modules\System\Blocks\Models\BlockPlacementModel;

class PlacementsController extends AdminBaseController
{
    private BlockInstanceModel $instances;
    private BlockPlacementModel $placements;

    public function __construct()
    {
        $this->instances  = new BlockInstanceModel();
        $this->placements = new BlockPlacementModel();
    }

    public function index(): string
    {
        $instances = [];
        $placements = [];
        try {
            $instances  = $this->instances->orderBy('id','DESC')->findAll();
            $placements = $this->placements->orderBy('id','DESC')->findAll();
        } catch (\Throwable $e) {}

        return $this->render('Modules\\System\\Blocks\\Views\\admin\\placements_index', [
            'title'      => 'Block placements',
            'instances'  => $instances,
            'placements' => $placements,
        ]);
    }

    public function add()
    {
        $instanceId = (int)($this->request->getPost('instance_id') ?? 0);
        $area   = (string)($this->request->getPost('area') ?? 'admin');
        $theme  = (string)($this->request->getPost('theme') ?? 'default');
        $region = (string)($this->request->getPost('region') ?? 'sidebar');
        $weight = (int)($this->request->getPost('weight') ?? 0);

        $cond = ['path_prefix' => (string)($this->request->getPost('path_prefix') ?? '')];
        if ($cond['path_prefix'] === '') $cond = [];

        $ok = $this->placements->insert([
            'instance_id' => $instanceId,
            'area' => $area,
            'theme' => $theme,
            'region' => $region,
            'weight' => $weight,
            'conditions_json' => $cond ? json_encode($cond, JSON_UNESCAPED_UNICODE) : null,
            'is_enabled' => 1,
        ]);

        return $ok
            ? redirect()->to(site_url('admin/system/blocks/placements'))->with('success', 'Placement added')
            : redirect()->back()->withInput()->with('error', 'Add failed');
    }

    public function delete(int $id)
    {
        $ok = $this->placements->delete($id);
        return $ok
            ? redirect()->to(site_url('admin/system/blocks/placements'))->with('success', 'Placement deleted')
            : redirect()->to(site_url('admin/system/blocks/placements'))->with('error', 'Delete failed');
    }
}
