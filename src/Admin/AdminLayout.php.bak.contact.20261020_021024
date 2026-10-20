<?php

/**
 * Beaver Framework — Modern PHP framework with a plugin ecosystem.
 *
 * @package    Beaver Framework
 * @author     Franco <onidesk@outlook.com>
 * @copyright  2026 Onidesk
 * @license    MIT <https://opensource.org/licenses/MIT>
 * @link       https://github.com/Onidesk-TI/beaver-framework
 */

namespace Beaver\Admin;

use Beaver\Foundation\Application;
use Beaver\Plugin\Hooks;
use Beaver\Sdk\HooksCatalog;

/**
 * Layout administrativo partilhado.
 *
 * Renderiza uma página do /admin com:
 *   - sidebar dinâmica (items do hook ADMIN_MENU)
 *   - header com user menu
 *   - CSS base do Beaver Admin
 *
 * Uso:
 *
 *     $layout = new AdminLayout(app());
 *     return Response::html(
 *         $layout->render('Título', $content, '/admin/rota-atual')
 *     );
 */
class AdminLayout
{
    public function __construct(
        private ?Application $app = null,
    ) {
        $this->app ??= Application::getInstance();
    }

    /**
     * Renderiza a página completa.
     *
     * @param string $title      Título (aparece no <title> e no <h1>)
     * @param string $content    HTML do conteúdo central (dentro do <main>)
     * @param string $activeHref Href do item ativo (para destacar na sidebar)
     * @param array  $data       Variáveis extra para a view (opcional)
     */
    public function render(
        string $title,
        string $content,
        string $activeHref = '',
        array $data = [],
    ): string {
        $items = $this->menuItems();
        $user  = $this->currentUser();

        // Torna as variáveis disponíveis para a view
        $sidebarItems = $items;
        $pageTitle    = $title;
        $pageContent  = $content;
        $activeHref   = $activeHref;
        $userName     = $user->name  ?? 'Convidado';
        $userEmail    = $user->email ?? '';

        // Variáveis extra
        extract($data, EXTR_SKIP);

        ob_start();
        require __DIR__ . '/resources/admin.php';
        return (string) ob_get_clean();
    }

    /**
     * Devolve os items do menu (já ordenados).
     *
     * @return array<int, array{label:string, href:string, icon?:string, order?:int}>
     */
    private function menuItems(): array
    {
        $hooks = $this->app->make(Hooks::class);

        // Items fixos do core (podem ser filtrados pelos plugins)
        $items = [
            [
                'label' => 'Dashboard',
                'href'  => '/admin',
                'icon'  => 'fa-gauge',
                'order' => 0,
            ],
        ];

        // Plugins podem adicionar/remover via hook
        $items = $hooks->filter(HooksCatalog::ADMIN_MENU, $items);

        // Ordenar por 'order' (default 100)
        usort(
            $items,
            fn ($a, $b) => ($a['order'] ?? 100) <=> ($b['order'] ?? 100)
        );

        return $items;
    }

    /**
     * Devolve o utilizador autenticado (ou null).
     */
    private function currentUser(): ?object
    {
        if (!class_exists(\Beaver\Auth\Auth::class)) {
            return null;
        }

        if (!\Beaver\Auth\Auth::check()) {
            return null;
        }

        return \Beaver\Auth\Auth::user();
    }
}
