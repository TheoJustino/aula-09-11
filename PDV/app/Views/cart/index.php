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

<section class="mb-5" aria-labelledby="add-product-heading">
    <h2 class="h4 mb-3" id="add-product-heading">Adicionar produto</h2>
    <?php if ($data['products'] === []): ?>
        <p class="text-muted">Não há produtos disponíveis em estoque.</p>
    <?php else: ?>
        <form class="row g-3 align-items-end" method="post" action="<?= htmlspecialchars($baseUrl . '/carrinho/itens', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-12 col-md-8">
                <label class="form-label" for="produto_id">Produto</label>
                <select class="form-select" id="produto_id" name="produto_id" required>
                    <option value="">Selecione um produto</option>
                    <?php foreach ($data['products'] as $product): ?>
                        <option value="<?= (int) $product['id'] ?>">
                            <?= htmlspecialchars($product['cod'] . ' - ' . $product['nome'] . ' (estoque: ' . $product['quantidade'] . ') - ' . \PDV\Services\Money::format(\PDV\Services\Money::toCents($product['preco_unitario'])), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="add-quantity">Quantidade</label>
                <input class="form-control" id="add-quantity" name="quantidade" type="number" min="1" step="1" value="1" required>
            </div>
            <div class="col-6 col-md-2">
                <button class="btn btn-primary w-100" type="submit">Adicionar</button>
            </div>
        </form>
    <?php endif; ?>
</section>

<section class="mb-5" aria-labelledby="cart-items-heading">
    <h2 class="h4 mb-3" id="cart-items-heading">Itens do carrinho</h2>
    <?php if ($data['items'] === []): ?>
        <p class="text-muted">O carrinho está vazio.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th scope="col">Produto</th>
                        <th scope="col">Preço unitário</th>
                        <th scope="col">Quantidade</th>
                        <th scope="col">Subtotal</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['items'] as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if ($item['code'] !== ''): ?>
                                    <small class="text-muted d-block"><?= htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') ?></small>
                                <?php endif; ?>
                                <?php if ($item['unavailable'] || $item['quantity'] > $item['stock']): ?>
                                    <span class="badge text-bg-warning">Revise o estoque deste item</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['unit_price'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!$item['unavailable']): ?>
                                    <form class="d-flex gap-2" method="post" action="<?= htmlspecialchars($baseUrl . '/carrinho/quantidade', ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="produto_id" value="<?= (int) $item['product_id'] ?>">
                                        <input class="form-control" aria-label="Quantidade de <?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>" name="quantidade" type="number" min="1" max="<?= (int) $item['stock'] ?>" step="1" value="<?= (int) $item['quantity'] ?>" required>
                                        <button class="btn btn-outline-secondary" type="submit">Atualizar</button>
                                    </form>
                                <?php else: ?>
                                    <?= (int) $item['quantity'] ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['subtotal'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" action="<?= htmlspecialchars($baseUrl . '/carrinho/remover', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="produto_id" value="<?= (int) $item['product_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" scope="row">Total</th>
                        <th><?= htmlspecialchars($data['total'], ENT_QUOTES, 'UTF-8') ?></th>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($data['items'] !== []): ?>
    <section aria-labelledby="checkout-heading">
        <h2 class="h4 mb-3" id="checkout-heading">Finalizar venda</h2>
        <form class="form-surface row g-3 align-items-end" method="post" action="<?= htmlspecialchars($baseUrl . '/vendas', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-12 col-md-5">
                <label class="form-label" for="cliente_id">Cliente (opcional)</label>
                <select class="form-select" id="cliente_id" name="cliente_id">
                    <option value="">Venda sem cliente</option>
                    <?php foreach ($data['customers'] as $customer): ?>
                        <option value="<?= (int) $customer->id ?>">
                            <?= htmlspecialchars($customer->nome . ' - ' . $customer->cpf, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="forma_pagamento">Forma de pagamento</label>
                <select class="form-select" id="forma_pagamento" name="forma_pagamento" required>
                    <option value="dinheiro">Dinheiro</option>
                    <option value="pix">Pix</option>
                    <option value="cartao">Cartão</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <button class="btn btn-success w-100" type="submit"<?= $data['canCheckout'] ? '' : ' disabled' ?>>Confirmar venda</button>
            </div>
        </form>
    </section>
<?php endif; ?>