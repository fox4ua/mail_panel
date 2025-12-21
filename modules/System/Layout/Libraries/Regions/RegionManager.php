<?php

declare(strict_types=1);

namespace Modules\System\Layout\Libraries\Regions;

use Modules\System\Layout\Config\Render as RenderConfig;
use Modules\System\Layout\Libraries\Renderer;

class RegionManager
{
    /** @var array<string, list<array{name:string,data:array}>> */
    protected array $regions = [];

    protected bool $bootstrapped = false;

    public function __construct(
        protected Renderer $renderer,
        protected RenderConfig $config
    ) {}

    /**
     * Добавить модуль в регион.
     * Пример: add('header', 'profile', ['compact'=>true])
     */
    public function add(string $region, string $moduleName, array $data = []): self
    {
        $region = trim($region);
        if ($region === '') {
            $region = 'default';
        }

        $moduleName = trim($moduleName);
        if ($moduleName === '') {
            return $this;
        }

        $this->regions[$region][] = [
            'name' => $moduleName,
            'data' => $data,
        ];

        return $this;
    }

    /**
     * Очистить регион (если понадобится, например для login-страниц).
     */
    public function clear(string $region): self
    {
        unset($this->regions[$region]);
        return $this;
    }

    /**
     * Отрендерить регион (в порядке добавления).
     */
    public function render(string $region): string
    {
        $this->bootstrapDefaults();

        $items = $this->regions[$region] ?? [];
        if ($items === []) {
            return '';
        }

        $out = '';
        foreach ($items as $item) {
            $out .= $this->renderer->module($item['name'], $item['data']);
        }

        return $out;
    }

    /**
     * Подхватить дефолтные регионы из конфига один раз за запрос.
     *
     * Config/Render.php:
     * public array $defaultRegions = [
     *   'header' => [['name'=>'profile','data'=>['compact'=>true]]],
     *   'left'   => [['name'=>'menu','data'=>[]]],
     * ];
     */
    public function bootstrapDefaults(): void
    {
        if ($this->bootstrapped) {
            return;
        }
        $this->bootstrapped = true;

        $defs = $this->config->defaultRegions ?? [];
        if (!is_array($defs) || $defs === []) {
            return;
        }

        foreach ($defs as $region => $items) {
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item) || empty($item['name'])) {
                    continue;
                }
                $this->add((string) $region, (string) $item['name'], (array) ($item['data'] ?? []));
            }
        }
    }
}
