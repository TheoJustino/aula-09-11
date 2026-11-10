<?php $errors = $data['errors']; ?>

<div class="mb-3">
    <label class="form-label" for="cod">Código</label>
    <input class="form-control<?= isset($errors['cod']) ? ' is-invalid' : '' ?>" id="cod" name="cod" type="text" required value="<?= htmlspecialchars($data['values']['cod'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['cod'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['cod'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label" for="nome">Nome</label>
    <input class="form-control<?= isset($errors['nome']) ? ' is-invalid' : '' ?>" id="nome" name="nome" type="text" required value="<?= htmlspecialchars($data['values']['nome'], ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($errors['nome'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nome'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</div>

<div class="mb-3">
    <label class="form-label" for="categoria">Categoria</label>
    <input class="form-control" id="categoria" name="categoria" type="text" value="<?= htmlspecialchars($data['values']['categoria'], ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="validade">Validade</label>
        <input class="form-control<?= isset($errors['validade']) ? ' is-invalid' : '' ?>" id="validade" name="validade" type="date" value="<?= htmlspecialchars($data['values']['validade'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if (isset($errors['validade'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['validade'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="quantidade">Quantidade em estoque</label>
        <input class="form-control<?= isset($errors['quantidade']) ? ' is-invalid' : '' ?>" id="quantidade" name="quantidade" type="number" min="0" step="1" required value="<?= htmlspecialchars($data['values']['quantidade'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if (isset($errors['quantidade'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['quantidade'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="preco_unitario">Preço unitário</label>
        <input class="form-control<?= isset($errors['preco_unitario']) ? ' is-invalid' : '' ?>" id="preco_unitario" name="preco_unitario" type="number" min="0" step="0.01" required value="<?= htmlspecialchars($data['values']['preco_unitario'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if (isset($errors['preco_unitario'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['preco_unitario'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
    <div class="col-12 col-md-6 mb-3">
        <label class="form-label" for="preco_pacote">Preço pacote</label>
        <input class="form-control<?= isset($errors['preco_pacote']) ? ' is-invalid' : '' ?>" id="preco_pacote" name="preco_pacote" type="number" min="0" step="0.01" required value="<?= htmlspecialchars($data['values']['preco_pacote'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if (isset($errors['preco_pacote'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['preco_pacote'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    </div>
</div>

<?php if ($data['currentImage'] !== null && $data['currentImage'] !== ''): ?>
    <div class="mb-3">
        <span class="form-label d-block">Imagem atual</span>
        <?php if ($data['imageUrl'] !== null): ?>
            <img class="product-preview" src="<?= htmlspecialchars($baseUrl . $data['imageUrl'], ENT_QUOTES, 'UTF-8') ?>" alt="Imagem atual do produto">
        <?php else: ?>
            <span class="text-muted small">Imagem legada mantida no cadastro.</span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="mb-3">
    <label class="form-label" for="imagemProduto">Imagem</label>
    <input class="form-control<?= isset($errors['imagem']) ? ' is-invalid' : '' ?>" id="imagemProduto" name="imagemProduto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
    <div class="form-text">JPEG, PNG ou WebP, até 2 MB. Ao editar, deixe vazio para manter a imagem atual.</div>
    <?php if (isset($errors['imagem'])): ?><div class="invalid-feedback d-block"><?= htmlspecialchars($errors['imagem'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
</div>