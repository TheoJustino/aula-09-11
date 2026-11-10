<?php
$currentPath = $path ?? '/';
$activeSection = match (true) {
    $currentPath === '/' => 'panel',
    str_starts_with($currentPath, '/produtos') => 'products',
    str_starts_with($currentPath, '/clientes') => 'customers',
    str_starts_with($currentPath, '/vendas/relatorios') => 'reports',
    str_starts_with($currentPath, '/carrinho'),
    str_starts_with($currentPath, '/vendas') => 'sales',
    default => '',
};
$description = match ($activeSection) {
    'panel' => 'Acesso rápido aos módulos disponíveis do PDV.',
    'products' => 'Consulte e mantenha os produtos e o estoque.',
    'customers' => 'Consulte e mantenha os cadastros de clientes.',
    'sales' => 'Organize os itens e acompanhe a finalização da venda.',
    'reports' => 'Consulte vendas realizadas e seus detalhes.',
    default => 'Sistema de ponto de venda para demonstração acadêmica local.',
};
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102c3e">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | PDV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($baseUrl . '/assets/css/app.css', ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <script src="<?= htmlspecialchars($baseUrl . '/assets/js/app.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body>
    <div class="app-shell app-shell--application" data-app-shell>
            <aside class="sidebar" id="primary-navigation" aria-label="Navegação principal">
                <a class="sidebar-brand" href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="brand-mark" aria-hidden="true">P</span>
                    <span class="brand-copy"><strong>PDV</strong><small>Operação comercial</small></span>
                </a>

                <div class="sidebar-label">Workspace</div>
                <nav class="sidebar-nav">
                    <a class="nav-link-pdv<?= $activeSection === 'panel' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>"<?= $activeSection === 'panel' ? ' aria-current="page"' : '' ?>>
                        <span class="nav-glyph" aria-hidden="true">P</span><span class="nav-copy">Painel</span>
                    </a>
                    <a class="nav-link-pdv<?= $activeSection === 'products' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/produtos', ENT_QUOTES, 'UTF-8') ?>"<?= $activeSection === 'products' ? ' aria-current="page"' : '' ?>>
                        <span class="nav-glyph" aria-hidden="true">P</span><span class="nav-copy">Produtos</span>
                    </a>
                    <a class="nav-link-pdv<?= $activeSection === 'customers' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>"<?= $activeSection === 'customers' ? ' aria-current="page"' : '' ?>>
                        <span class="nav-glyph" aria-hidden="true">C</span><span class="nav-copy">Clientes</span>
                    </a>
                    <a class="nav-link-pdv<?= $activeSection === 'sales' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/carrinho', ENT_QUOTES, 'UTF-8') ?>"<?= $activeSection === 'sales' ? ' aria-current="page"' : '' ?>>
                        <span class="nav-glyph" aria-hidden="true">V</span><span class="nav-copy">Nova venda</span>
                    </a>
                    <a class="nav-link-pdv<?= $activeSection === 'reports' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($baseUrl . '/vendas/relatorios', ENT_QUOTES, 'UTF-8') ?>"<?= $activeSection === 'reports' ? ' aria-current="page"' : '' ?>>
                        <span class="nav-glyph" aria-hidden="true">R</span><span class="nav-copy">Relatórios</span>
                    </a>
                </nav>

                <div class="sidebar-spacer"></div>
            </aside>
            <button class="sidebar-backdrop" type="button" aria-label="Fechar navegação" data-sidebar-close></button>

        <div class="app-main">
            <header class="topbar">
                <button class="menu-toggle" type="button" aria-label="Abrir ou recolher menu" aria-controls="primary-navigation" aria-expanded="false" data-sidebar-toggle>
                    <span aria-hidden="true">Menu</span>
                </button>
                <div class="topbar-context"><span class="topbar-kicker">Ponto de venda</span><span class="topbar-divider" aria-hidden="true">/</span><span><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span></div>
                <div class="topbar-status"><span class="status-indicator" aria-hidden="true"></span><span>Demonstração local</span></div>
            </header>

            <main class="content-shell">
                <div class="page-heading">
                    <div>
                        <?php if ($activeSection !== ''): ?>
                            <p class="page-eyebrow"><?= htmlspecialchars(match ($activeSection) { 'panel' => 'Visão geral', 'products' => 'Catálogo', 'customers' => 'Relacionamento', 'sales' => 'Vendas', 'reports' => 'Análises', default => 'PDV' }, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
                <div class="page-content">
                    <?= $content ?>
                </div>
                <footer class="app-footer"><span>PDV · Operação comercial</span><span>Interface administrativa</span></footer>
            </main>
        </div>
    </div>
</body>
</html>