<?php
declare(strict_types=1);

namespace Modules\System\Core\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Modules\System\Layout\Libraries\Render\Renderer;

/**
 * Базовый контроллер модульной системы.
 * Даёт доступ к $this->render->view() без правок app/Controllers/BaseController.php.
 */
abstract class CoreBaseController extends Controller
{
    protected Renderer $render;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        // Renderer сам поймёт текущий модуль по файлу контроллера (Reflection).
        $this->render = service('render')->setController($this);
    }
}
