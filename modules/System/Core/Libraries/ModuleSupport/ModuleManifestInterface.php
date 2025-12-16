<?php

namespace Modules\System\Core\Libraries\ModuleSupport;

/**
 * Unified module manifest contract.
 *
 * All manifests SHOULD implement this interface (directly or via BaseModuleManifest).
 * Legacy manifests are supported via LegacyManifestAdapter.
 */
interface ModuleManifestInterface
{
    /**
     * Run once on initial installation.
     * Must return true on success; false to abort install (no DB write).
     */
    public function install(): bool;

    /**
     * Run before removing module state (DB) / files (if applicable).
     * Must return true on success; false to abort uninstall.
     */
    public function uninstall(): bool;

    /**
     * Run when module version changes.
     * Must return true on success; false to abort update.
     */
    public function update(string $from, string $to): bool;

    /**
     * Return admin menu items for sync (system menu).
     */
    public function menu(): array;

    /**
     * Optional: provide routes without Config/Routes.php.
     *
     * Return value:
     * - null: use default routes loader behavior
     * - callable: function(RouteCollection $routes): void
     * - string: path to a php routes file (relative to module root OR absolute)
     */
    public function routes();

    /**
     * Dependencies of this module in format ['system/layout', 'system/menu', ...]
     */
    public function requires(): array;
}
