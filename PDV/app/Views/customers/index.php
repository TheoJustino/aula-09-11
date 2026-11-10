<?php $success = $data['success'] ?? null; ?>

<?php if ($success !== null): ?>
    <div class="alert alert-success" role="status">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <a class="btn btn-primary" href="<?= htmlspecialchars($baseUrl . '/clientes/novo', ENT_QUOTES, 'UTF-8') ?>">
        Cadastrar cliente
    </a>
</div>

<?php if ($data['customers'] === []): ?>
    <p class="text-muted">Nenhum cliente cadastrado.</p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Nome</th>
                    <th scope="col">CPF</th>
                    <th scope="col">Telefone</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Criado em</th>
                    <th scope="col">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['customers'] as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer->nome, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($customer->cpf, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($customer->telefone ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($customer->email ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($customer->createdAt ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($baseUrl . '/clientes/editar?id=' . $customer->id, ENT_QUOTES, 'UTF-8') ?>">
                                    Editar
                                </a>
                                <form method="post" action="<?= htmlspecialchars($baseUrl . '/clientes/excluir', ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id" value="<?= (int) $customer->id ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>