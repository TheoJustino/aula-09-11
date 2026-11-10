<div class="mb-3">
    <label class="form-label" for="nome">Nome</label>
    <input class="form-control<?= isset($errors['nome']) ? ' is-invalid' : '' ?>" id="nome" name="nome" type="text" maxlength="255" required value="<?= htmlspecialchars($values['nome'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['nome'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label" for="cpf">CPF</label>
    <input class="form-control<?= isset($errors['cpf']) ? ' is-invalid' : '' ?>" id="cpf" name="cpf" type="text" inputmode="numeric" maxlength="14" required value="<?= htmlspecialchars($values['cpf'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['cpf'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['cpf'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label" for="telefone">Telefone</label>
    <input class="form-control<?= isset($errors['telefone']) ? ' is-invalid' : '' ?>" id="telefone" name="telefone" type="tel" maxlength="30" value="<?= htmlspecialchars($values['telefone'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['telefone'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['telefone'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label" for="email">E-mail</label>
    <input class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" type="email" maxlength="254" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['email'])): ?>
        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>