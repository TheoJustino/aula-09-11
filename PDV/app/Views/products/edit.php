<form class="form-surface product-form" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($baseUrl . '/produtos/atualizar', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= (int) $data['id'] ?>">
    <?php require __DIR__ . '/_form.php'; ?>
    <div class="d-flex flex-wrap gap-2 mt-4">
        <button class="btn btn-primary" type="submit">Salvar alterações</button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/produtos', ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
    </div>
</form>