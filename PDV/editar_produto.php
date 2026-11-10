<?php
include 'db.php';

$produto = [];

// 1. CARREGAR DADOS DO PRODUTO (Convertido de mysqli para PDO)
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $id = $_GET["id"];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        $produto = $stmt->fetch();
        
        if (!$produto) {
            die("<div class='alert alert-danger m-3'>Erro: Produto não encontrado.</div>");
        }
    } catch (PDOException $e) {
        die("Erro ao buscar produto: " . $e->getMessage());
    }
}

// 2. ATUALIZAR DADOS DO PRODUTO (Convertido de mysqli para PDO)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id            = $_POST["id"];
    $nome          = $_POST["nome"];
    $categoria     = $_POST["categoria"];
    $validade      = $_POST["validade"];
    $quantidade    = $_POST["quantidade"];
    $precoUnitario = $_POST["preco_unitario"];
    $precoPacote   = $_POST["preco_pacote"];

    try {
        $sql = "UPDATE produtos 
                SET nome = :nome, categoria = :categoria, validade = :validade, 
                    quantidade = :quantidade, preco_unitario = :preco_unitario, preco_pacote = :preco_pacote 
                WHERE id = :id";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':nome'           => $nome,
            ':categoria'      => $categoria,
            ':validade'       => empty($validade) ? null : $validade,
            ':quantidade'     => (int)$quantidade,
            ':preco_unitario' => (float)$precoUnitario,
            ':preco_pacote'   => (float)$precoPacote,
            ':id'             => (int)$id
        ]);

        echo "<div class='alert alert-success m-3'>Produto atualizado com sucesso!</div>";
        
        // Recarrega os dados atualizados para atualizar o formulário visualmente
        $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = :id");
        $stmt->execute([':id' => (int)$id]);
        $produto = $stmt->fetch();

    } catch (PDOException $e) {
        echo "<div class='alert alert-danger m-3'>Erro ao atualizar produto no PostgreSQL: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Editar Produto</h1>
        
        <?php if (!empty($produto)): ?>
        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($produto['id'] ?? '') ?>">
            
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="categoria" class="form-label">Categoria</label>
                <input type="text" class="form-control" id="categoria" name="categoria" value="<?= htmlspecialchars($produto['categoria'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="validade" class="form-label">Validade</label>
                <input type="date" class="form-control" id="validade" name="validade" value="<?= htmlspecialchars($produto['validade'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?= htmlspecialchars($produto['quantidade'] ?? 0) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="preco_unitario" class="form-label">Preço Unitário</label>
                <input type="number" step="0.01" class="form-control" id="preco_unitario" name="preco_unitario" value="<?= htmlspecialchars($produto['preco_unitario'] ?? 0.00) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="preco_pacote" class="form-label">Preço Pacote</label>
                <input type="number" step="0.01" class="form-control" id="preco_pacote" name="preco_pacote" value="<?= htmlspecialchars($produto['preco_pacote'] ?? 0.00) ?>" required>
            </div>
            
            <div class="d-flex justify-content-between mb-5">
                <a href="pdv.php" class="btn btn-secondary">Voltar ao PDV</a>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
        <?php else: ?>
            <div class="alert alert-warning">Selecione um produto válido para editar a partir da tela do PDV.</div>
            <a href="pdv.php" class="btn btn-primary">Voltar ao PDV</a>
        <?php endif; ?>
    </div>
</body>
</html>
