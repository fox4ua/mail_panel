<?php

namespace Modules\System\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\System\Core\Libraries\ModuleSupport\ModuleRegistry;
use Modules\System\ModuleCenter\Models\ModuleModel;

/**
 * Blocks access to Pages/Blocks controllers of modules that are:
 * - not installed (no row in modules)
 * - disabled (is_enabled=0)
 *
 * System-category modules are always allowed (protected).
 *
 * This filter is intentionally lightweight and does NOT read any "manifest" or dependencies.
 */
class ModuleEnabledFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            $router = service('router');
            if (!$router) return;

            $handler = $router->handler();
            if (!$handler || strpos($handler, '::') === false) return;

            [$class] = explode('::', $handler, 2);

            // Expect: Modules\<Category>\<Module>\Controllers\...
            $parts = explode('\\', ltrim($class, '\\'));
            if (count($parts) < 4) return;
            if (mb_strtolower($parts[0]) !== 'modules') return;

            $cat = mb_strtolower($parts[1] ?? '');
            $mod = mb_strtolower($parts[2] ?? '');

            if ($cat === '' || $mod === '') return;

            // Only enforce for pages/blocks
            if (!in_array($cat, ['pages', 'blocks'], true)) {
                return;
            }

            // Must exist on disk
            $reg = new ModuleRegistry();
            $exists = false;
            foreach ($reg->all() as $i) {
                if (mb_strtolower((string)$i->module) === $mod && mb_strtolower((string)$i->category) === $cat) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                return $this->notFound();
            }

            // Must be installed & enabled in DB
            $model = new ModuleModel();
            $row = $model->find($mod);
            if (!$row) {
                return $this->notFound();
            }
            if ((int)($row['is_enabled'] ?? 0) !== 1) {
                return $this->notFound();
            }

            return;
        } catch (\Throwable $e) {
            // fail-open to avoid bricking admin in case of misconfiguration
            return;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }

    private function notFound()
    {
        $resp = service('response');
        if ($resp instanceof ResponseInterface) {
            return $resp->setStatusCode(404);
        }
        return;
    }
}
