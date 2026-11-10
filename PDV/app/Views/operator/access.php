<?php $error = $data['error'] ?? null; ?>

<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="access-panel">
    <div class="access-emblem" aria-hidden="true">PDV</div>
    <h2 class="h4 mb-2">Acesso da equipe</h2>
    <p class="text-muted mb-4">Entre para acessar as rotinas do sistema.</p>
    <form method="post" action="<?= htmlspecialchars($baseUrl . '/operador/acesso', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
            <label class="form-label" for="operator-secret">Segredo do operador</label>
            <input class="form-control" id="operator-secret" name="secret" type="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Acessar</button>
    </form>
</section>