<?php
/**
 * SqlWizard Lite — Classe principal do plugin.
 */
declare(strict_types=1);

namespace Beaver\Plugins\SqlWizardLite;

use Beaver\Plugin\PluginBase;

final class SqlWizardLitePlugin extends PluginBase
{
    public function boot(): void
    {
        error_log('>>> [LITE BOOT] boot() chamado. path=' . $this->path);

        $this->loadRoutes();

        error_log('>>> [LITE BOOT] loadRoutes() terminou');

        $this->loadViews();
    }
}
