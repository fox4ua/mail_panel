<?php

namespace Modules\System\Blocks\Libraries\Blocks;

use Modules\System\Blocks\Models\BlockInstanceModel;
use Modules\System\Blocks\Models\BlockPlacementModel;

class BlockRenderer
{
    private BlockInstanceModel $instances;
    private BlockPlacementModel $placements;

    public function __construct()
    {
        $this->instances  = new BlockInstanceModel();
        $this->placements = new BlockPlacementModel();
    }

    public function renderRegion(string $area, string $theme, string $region, string $currentUrl = ''): string
    {
        try {
            $rows = $this->placements
                ->where(['area'=>$area,'theme'=>$theme,'region'=>$region,'is_enabled'=>1])
                ->orderBy('weight', 'ASC')
                ->findAll();
        } catch (\Throwable $e) {
            return '';
        }

        if (!$rows) return '';

        $out = '';
        foreach ($rows as $p) {
            $inst = $this->instances->find($p['instance_id']);
            if (!$inst || !(bool)$inst['is_enabled']) continue;
            if (!$this->passesConditions($p['conditions_json'] ?? null, $currentUrl)) continue;
            $out .= $this->renderInstance($inst);
        }

        return $out;
    }

    private function renderInstance(array $inst): string
    {
        $type = $inst['type'];
        $settings = [];
        if (!empty($inst['settings_json'])) {
            $decoded = json_decode($inst['settings_json'], true);
            if (is_array($decoded)) $settings = $decoded;
        }

        if ($type === 'html') {
            return view('Modules\\System\\Blocks\\Views\\blocks\\html', [
                'title' => $inst['title'] ?? null,
                'html'  => $settings['html'] ?? '',
            ]);
        }

        return view('Modules\\System\\Blocks\\Views\\blocks\\unknown', [
            'type'  => $type,
            'title' => $inst['title'] ?? null,
        ]);
    }

    private function passesConditions(?string $json, string $url): bool
    {
        if (!$json) return true;
        $cond = json_decode($json, true);
        if (!is_array($cond)) return true;

        if (!empty($cond['path_prefix'])) {
            $prefix = (string)$cond['path_prefix'];
            return str_starts_with($url, $prefix);
        }

        return true;
    }
}
