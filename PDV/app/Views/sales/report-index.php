<?php
$filters = $data['filters'];
$errors = $data['errors'];
?>

<form class="form-surface report-filters mb-4" method="get" action="<?= htmlspecialchars($baseUrl . '/vendas/relatorios', ENT_QUOTES, 'UTF-8') ?>">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label class="form-label" for="data_inicial">Data inicial</label>
            <input class="form-control<?= isset($errors['data_inicial']) ? ' is-invalid' : '' ?>" id="data_inicial" name="data_inicial" type="date" value="<?= htmlspecialchars($filters['data_inicial'], ENT_QUOTES, 'UTF-8') ?>"<?= isset($errors['data_inicial']) ? ' aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['data_inicial'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['data_inicial'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="data_final">Data final</label>
            <input class="form-control<?= isset($errors['data_final']) ? ' is-invalid' : '' ?>" id="data_final" name="data_final" type="date" value="<?= htmlspecialchars($filters['data_final'], ENT_QUOTES, 'UTF-8') ?>"<?= isset($errors['data_final']) ? ' aria-invalid="true"' : '' ?>>
            <?php if (isset($errors['data_final'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['data_final'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="forma_pagamento">Forma de pagamento</label>
            <select class="form-select<?= isset($errors['forma_pagamento']) ? ' is-invalid' : '' ?>" id="forma_pagamento" name="forma_pagamento"<?= isset($errors['forma_pagamento']) ? ' aria-invalid="true"' : '' ?>>
                <option value="">Todas</option>
                <option value="dinheiro"<?= $filters['forma_pagamento'] === 'dinheiro' ? ' selected' : '' ?>>Dinheiro</option>
                <option value="pix"<?= $filters['forma_pagamento'] === 'pix' ? ' selected' : '' ?>>Pix</option>
                <option value="cartao"<?= $filters['forma_pagamento'] === 'cartao' ? ' selected' : '' ?>>Cartão</option>
            </select>
            <?php if (isset($errors['forma_pagamento'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['forma_pagamento'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="col-12 col-md-3">
            <div class="d-flex gap-2">
                <button class="btn btn-primary flex-grow-1" type="submit">Filtrar</button>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/vendas/relatorios', ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
            </div>
        </div>
    </div>
</form>

<?php if ($data['sales'] === []): ?>
    <div class="empty-state">
        <?= $errors === [] ? 'Nenhuma venda encontrada para os filtros informados.' : 'Corrija os filtros para consultar as vendas.' ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle sales-report-table">
            <thead>
                <tr>
                    <th scope="col">Venda</th>
                    <th scope="col">Data</th>
                    <th scope="col">Cliente</th>
                    <th scope="col">Pagamento</th>
                    <th class="text-end" scope="col">Total</th>
                    <th scope="col">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['sales'] as $row): ?>
                    <?php $sale = $row['sale']; ?>
                    <tr>
                        <td>#<?= (int) $sale->id ?></td>
                        <td><?= htmlspecialchars($sale->dataVenda ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sale->clienteNome ?? 'Venda sem cliente', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['paymentLabel'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-semibold"><?= htmlspecialchars($row['total'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/vendas/relatorios/detalhes?id=' . (int) $sale->id, ENT_QUOTES, 'UTF-8') ?>">
                                Visualizar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>