<?php $error = $data['error'] ?? null; ?>

<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($baseUrl . '/operador/acesso', ENT_QUOTES, 'UTF-8') ?>" class="col-12 col-md-6">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-3">
        <label class="form-label" for="operator-secret">Segredo do operador</label>
        <input class="form-control" id="operator-secret" name="secret" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn-primary" type="submit">Acessar</button>
</form>