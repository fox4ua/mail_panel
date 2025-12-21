<?php

declare(strict_types=1);

namespace Modules\System\Profile\Libraries;

use Modules\System\Profile\Config\Profile as ProfileConfig;

/**
 * CurrentUser reads user identity from session (project-specific).
 * Adjust Config/Profile.php if your session keys differ.
 */
class CurrentUser
{
    private ProfileConfig $cfg;

    public function __construct(?ProfileConfig $cfg = null)
    {
        $this->cfg = $cfg ?? config(ProfileConfig::class);
    }

    public function id(): ?string
    {
        $sess = session();

        foreach ($this->cfg->sessionUserIdKeys as $k) {
            $v = $sess->get($k);
            if (is_string($v) && trim($v) !== '') {
                return trim($v);
            }
            if (is_int($v)) {
                return (string) $v;
            }
        }

        // common pattern: session('user') array
        $u = $sess->get('user');
        if (is_array($u)) {
            foreach ($this->cfg->sessionUserIdKeys as $k) {
                if (isset($u[$k]) && (is_string($u[$k]) || is_int($u[$k]))) {
                    $vv = (string) $u[$k];
                    if (trim($vv) !== '') return trim($vv);
                }
            }
        }

        return null;
    }

    public function email(): ?string
    {
        $sess = session();
        foreach ($this->cfg->sessionUserEmailKeys as $k) {
            $v = $sess->get($k);
            if (is_string($v) && trim($v) !== '') {
                return trim($v);
            }
        }
        $u = $sess->get('user');
        if (is_array($u)) {
            foreach ($this->cfg->sessionUserEmailKeys as $k) {
                if (isset($u[$k]) && is_string($u[$k]) && trim($u[$k]) !== '') {
                    return trim($u[$k]);
                }
            }
        }
        return null;
    }

    public function name(): ?string
    {
        $sess = session();
        foreach ($this->cfg->sessionUserNameKeys as $k) {
            $v = $sess->get($k);
            if (is_string($v) && trim($v) !== '') {
                return trim($v);
            }
        }
        $u = $sess->get('user');
        if (is_array($u)) {
            foreach ($this->cfg->sessionUserNameKeys as $k) {
                if (isset($u[$k]) && is_string($u[$k]) && trim($u[$k]) !== '') {
                    return trim($u[$k]);
                }
            }
        }
        return null;
    }
}
