<?php $sale = $data['sale']; ?>

<div class="page-toolbar">
    <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/vendas/relatorios', ENT_QUOTES, 'UTF-8') ?>">Voltar aos relatórios</a>
</div>

<div class="sale-report-heading">
    <div>
        <p class="section-kicker">Venda #<?= (int) $sale->id ?></p>
        <h2 class="h4 mb-0">Detalhes da venda</h2>
    </div>
    <div class="sale-report-total"><span>Valor total</span><strong><?= htmlspecialchars($data['total'], ENT_QUOTES, 'UTF-8') ?></strong></div>
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
                <th class="text-end" scope="col">Quantidade</th>
                <th class="text-end" scope="col">Preço unitário</th>
                <th class="text-end" scope="col">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['items'] as $line): ?>
                <?php $item = $line['sale_item']; ?>
                <tr>
                    <td><?= htmlspecialchars($item->produtoNome ?? ('Produto #' . $item->produtoId), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><?= (int) $item->quantidade ?></td>
                    <td class="text-end"><?= htmlspecialchars($line['unit_price'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><?= htmlspecialchars($line['subtotal'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" scope="row">Total</th>
                <th class="text-end"><?= htmlspecialchars($data['total'], ENT_QUOTES, 'UTF-8') ?></th>
            </tr>
        </tfoot>
    </table>
</div>