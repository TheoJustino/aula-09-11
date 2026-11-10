<?php
include 'db.php';
$produtos = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["busca"])) {
    $busca = $_POST["busca"];
    $produtos = buscarProduto('nome', $busca)->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["vender"])) {
    $id = $_POST["id"];
    $quantidadeVendida = $_POST["quantidadeVendida"];

    // Atualiza a quantidade no banco de dados
    $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
    $stmt->bind_param("ii", $quantidadeVendida, $id);
    $stmt->execute();
    echo "Produto vendido com sucesso!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir"])) {
    $id = $_POST["id"];

    // Exclui o produto do banco de dados
    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "Produto excluído com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ponto de Venda</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Ponto de Venda</h1>
        <form method="POST">
            <div class="mb-3">
                <label for="busca" class="form-label">Buscar Produto</label>
                <input type="text" class="form-control" id="busca" name="busca" placeholder="Digite o nome ou código do produto" required>
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
        <hr>
        <?php if (!empty($produtos)): ?>
            <h2>Resultados da Busca:</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>COD</th>
                        <th>Nome</th>
                        <th>Quantidade</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $produto): ?>
                        <tr>
                            <td><img src="imagens/<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>" width="50"></td>
                            <td><?= $produto['cod'] ?></td>
                            <td><?= $produto['nome'] ?></td>
                            <td><?= $produto['quantidade'] ?></td>
                            <td>
                                <!-- Formulário de Venda -->
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <input type="number" name="quantidadeVendida" min="1" max="<?= $produto['quantidade'] ?>" placeholder="Qtd">
                                    <button type="submit" name="vender" class="btn btn-success btn-sm">Vender</button>
                                </form>
                                <!-- Formulário de Exclusão -->
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <button type="submit" name="excluir" class="btn btn-danger btn-sm">Excluir</button>
                                </form>
                                <!-- Botão de Editar -->
                                <a href="editar_produto.php?id=<?= $produto['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
