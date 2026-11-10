<?php
include 'db.php';
$produtos = [];

// 1. CORREÇÃO DA BUSCA (Removido ->fetch_all())
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["busca"])) {
    $busca = $_POST["busca"];
    // A função buscarProduto() já devolve a array pronta com PDO
    $produtos = buscarProduto('nome', $busca); 
}

// 2. CORREÇÃO DA VENDA (Convertido mysqli bind_param para PDO execute)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["vender"])) {
    $id = $_POST["id"];
    $quantidadeVendida = $_POST["quantidadeVendida"];

    try {
        // Atualiza a quantidade no banco de dados com segurança
        $stmt = $conn->prepare("UPDATE produtos SET quantidade = quantidade - :qtd WHERE id = :id");
        $stmt->execute([
            ':qtd' => (int)$quantidadeVendida,
            ':id'  => (int)$id
        ]);
        echo "<div class='alert alert-success m-3'>Produto vendido com sucesso!</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Erro ao vender produto: " . $e->getMessage() . "</div>";
    }
}

// 3. CORREÇÃO DA EXCLUSÃO (Convertido mysqli bind_param para PDO execute)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir"])) {
    $id = $_POST["id"];

    try {
        // Exclui o produto do banco de dados de forma segura
        $stmt = $conn->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->execute([
            ':id' => (int)$id
        ]);
        echo "<div class='alert alert-success m-3'>Produto excluído com sucesso!</div>";
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Erro ao excluir produto: " . $e->getMessage() . "</div>";
    }
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
                <input type="text" class="form-control" id="busca" name="busca" placeholder="Digite o nome do produto" required>
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
                            <td><img src="imagens/<?= htmlspecialchars($produto['imagem'] ?? '') ?>" alt="<?= htmlspecialchars($produto['nome'] ?? '') ?>" width="50"></td>
                            <td><?= htmlspecialchars($produto['cod'] ?? '') ?></td>
                            <td><?= htmlspecialchars($produto['nome'] ?? '') ?></td>
                            <td><?= htmlspecialchars($produto['quantidade'] ?? 0) ?></td>
                            <td>
                                <!-- Formulário de Venda -->
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <input type="number" name="quantidadeVendida" min="1" max="<?= $produto['quantidade'] ?>" placeholder="Qtd" required>
                                    <button type="submit" name="vender" class="btn btn-success btn-sm">Vender</button>
                                </form>
                                <!-- Formulário de Exclusão -->
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                                    <button type="submit" name="excluir" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</button>
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
