<?php
$success = $data['success'] ?? null;
$error = $data['error'] ?? null;
?>

<?php if ($success !== null): ?>
    <div class="alert alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="page-toolbar product-toolbar">
    <form class="product-search" method="get" action="<?= htmlspecialchars($baseUrl . '/produtos', ENT_QUOTES, 'UTF-8') ?>">
        <label class="visually-hidden" for="product-search">Pesquisar por nome ou código</label>
        <input class="form-control" id="product-search" name="q" type="search" value="<?= htmlspecialchars($data['search'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Nome ou código">
        <button class="btn btn-outline-secondary" type="submit">Pesquisar</button>
    </form>
    <a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl . '/produtos/novo', ENT_QUOTES, 'UTF-8') ?>">Novo produto</a>
</div>

<?php if ($data['products'] === []): ?>
    <div class="empty-state">
        <?= $data['search'] === '' ? 'Nenhum produto cadastrado.' : 'Nenhum produto corresponde à pesquisa.' ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle product-table">
            <thead>
                <tr>
                    <th scope="col">Produto</th>
                    <th scope="col">Código</th>
                    <th scope="col">Categoria</th>
                    <th class="text-end" scope="col">Estoque</th>
                    <th class="text-end" scope="col">Preço unitário</th>
                    <th class="text-end" scope="col">Preço pacote</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['products'] as $product): ?>
                    <tr>
                        <td>
                            <div class="product-name-cell">
                                <?php if ($product['imageUrl'] !== null): ?>
                                    <img class="product-thumbnail" src="<?= htmlspecialchars($baseUrl . $product['imageUrl'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                <?php else: ?>
                                    <span class="product-thumbnail product-thumbnail--empty" aria-hidden="true">P</span>
                                <?php endif; ?>
                                <span><?= htmlspecialchars((string) $product['nome'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string) $product['cod'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($product['categoria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <span class="stock-badge stock-badge--<?= $product['stockStatus'] === 'Zerado' ? 'empty' : ($product['stockStatus'] === 'Baixo' ? 'low' : 'ok') ?>">
                                <?= (int) $product['quantidade'] ?> · <?= htmlspecialchars($product['stockStatus'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-end"><?= htmlspecialchars((string) $product['preco_unitario'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end"><?= htmlspecialchars((string) $product['preco_pacote'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/produtos/editar?id=' . (int) $product['id'], ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                                <form method="post" action="<?= htmlspecialchars($baseUrl . '/produtos/excluir', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>