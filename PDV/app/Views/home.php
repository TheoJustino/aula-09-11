<section class="dashboard-intro" aria-label="Resumo do sistema">
    <div class="dashboard-banner">
        <span class="banner-label">Painel do operador</span>
        <h2>Uma visão clara da sua operação.</h2>
        <p>Consulte clientes ou comece uma venda pelos módulos disponíveis no PDV.</p>
    </div>
    <div class="dashboard-note">
        <div>
            <h2>Módulos disponíveis</h2>
            <p>O painel reúne atalhos para as rotinas disponíveis de catálogo, clientes, vendas e consulta de vendas realizadas.</p>
        </div>
        <a href="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>">Acessar clientes</a>
    </div>
</section>

<section aria-labelledby="modules-heading">
    <h2 class="section-kicker" id="modules-heading">Acesso rápido</h2>
    <div class="module-grid">
        <a class="module-link" href="<?= htmlspecialchars($baseUrl . '/produtos', ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-symbol" aria-hidden="true">P</span>
            <span><strong>Produtos</strong><small>Consultar catálogo e estoque</small></span>
            <span class="module-arrow" aria-hidden="true">›</span>
        </a>
        <a class="module-link" href="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-symbol" aria-hidden="true">C</span>
            <span><strong>Clientes</strong><small>Consultar e manter cadastros</small></span>
            <span class="module-arrow" aria-hidden="true">›</span>
        </a>
        <a class="module-link" href="<?= htmlspecialchars($baseUrl . '/carrinho', ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-symbol" aria-hidden="true">V</span>
            <span><strong>Nova venda</strong><small>Montar carrinho e finalizar atendimento</small></span>
            <span class="module-arrow" aria-hidden="true">›</span>
        </a>
        <a class="module-link" href="<?= htmlspecialchars($baseUrl . '/vendas/relatorios', ENT_QUOTES, 'UTF-8') ?>">
            <span class="module-symbol" aria-hidden="true">R</span>
            <span><strong>Relatórios</strong><small>Consultar vendas registradas</small></span>
            <span class="module-arrow" aria-hidden="true">›</span>
        </a>
    </div>
</section>