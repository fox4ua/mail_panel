<?php

declare(strict_types=1);

namespace Modules\System\Profile\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\System\Profile\Libraries\CurrentUser;
use Modules\System\Profile\Libraries\ProfileService;

class ProfileController extends Controller
{
    public function index(): string
    {
        $current = new CurrentUser();
        $uid = $current->id();

        if (!$uid) {
            return 'Unauthorized';
        }

        $svc = new ProfileService();

        try {
            [$user, $profile] = $svc->getOrCreate($uid);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

        $render = function_exists('service') ? service('render') : null;
        if (is_object($render) && method_exists($render, 'setController')) {
            $render->setController($this);
        }
        if (is_object($render) && method_exists($render, 'addTitle')) {
            $render->addTitle('My profile');
        }

        $data = [
            'user'    => $user,
            'profile' => $profile,
            'success' => session()->getFlashdata('success'),
            'error'   => session()->getFlashdata('error'),
            'errors'  => session()->getFlashdata('errors') ?? [],
        ];

        if (is_object($render) && method_exists($render, 'view')) {
            return $render->view('profile/index', $data);
        }

        return view('profile/index', $data);
    }

    public function save(): ResponseInterface
    {
        $current = new CurrentUser();
        $uid = $current->id();

        if (!$uid) {
            return $this->response->setStatusCode(401);
        }

        $post = $this->request->getPost();

        $payload = [
            'first_name'   => (string) ($post['first_name'] ?? ''),
            'last_name'    => (string) ($post['last_name'] ?? ''),
            'display_name' => (string) ($post['display_name'] ?? ''),
            'bio'          => (string) ($post['bio'] ?? ''),
        ];

        $svc = new ProfileService();

        try {
            $svc->update($uid, $payload);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('account/profile'))->with('success', 'Profile saved');
    }
}
