<?php $sale = $data['sale']; ?>

<div class="alert alert-success sale-confirmation-banner" role="status">
    Venda #<?= (int) $sale->id ?> registrada com sucesso.
</div>

<dl class="row sale-details">
    <dt class="col-sm-3">Data</dt>
    <dd class="col-sm-9"><?= htmlspecialchars($sale->dataVenda ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
    <dt class="col-sm-3">Cliente</dt>
    <dd class="col-sm-9"><?= htmlspecialchars($sale->clienteNome ?? 'Venda sem cliente', ENT_QUOTES, 'UTF-8') ?></dd>
    <dt class="col-sm-3">Pagamento</dt>
    <dd class="col-sm-9"><?= htmlspecialchars($data['paymentLabel'], ENT_QUOTES, 'UTF-8') ?></dd>
</dl>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th scope="col">Produto</th>
                <th scope="col">Quantidade</th>
                <th scope="col">Preço unitário</th>
                <th scope="col">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['items'] as $line): ?>
                <?php $item = $line['sale_item']; ?>
                <tr>
                    <td><?= htmlspecialchars($item->produtoNome ?? ('Produto #' . $item->produtoId), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $item->quantidade ?></td>
                    <td><?= htmlspecialchars($line['unit_price'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($line['subtotal'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" scope="row">Total</th>
                <th><?= htmlspecialchars($data['total'], ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
        </tfoot>
    </table>
</div>

<a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl . '/carrinho', ENT_QUOTES, 'UTF-8') ?>">Voltar ao carrinho</a>