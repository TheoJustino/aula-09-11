<?php
$values = $data['values'];
$errors = $data['errors'];
?>

<form class="form-surface" method="post" action="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
    <?php require __DIR__ . '/_form_fields.php'; ?>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" type="submit">Salvar cliente</button>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/clientes', ENT_QUOTES, 'UTF-8') ?>">Cancelar</a>
    </div>
</form>