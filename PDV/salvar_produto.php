<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Captura os dados usando os nomes exatos do seu formulário HTML
    $codProduto    = $_POST["codProduto"] ?? '';
    $nomeProduto   = $_POST["nomeProduto"] ?? '';
    $categoria     = $_POST["categoria"] ?? '';
    $validade      = $_POST["validade"] ?? null;
    $quantidade    = $_POST["quantidade"] ?? 0;
    $precoUnitario = $_POST["precoUnitario"] ?? 0.00;
    $precoPacote   = $_POST["precoPacote"] ?? 0.00;

    // Upload da imagem (mantendo sua lógica original)
    $imagemProduto = $_FILES["imagemProduto"]["name"] ?? '';
    if (!empty($imagemProduto)) {
        $imagemDestino = "imagens/" . basename($imagemProduto);
        move_uploaded_file($_FILES["imagemProduto"]["tmp_name"], $imagemDestino);
    }

    // Validação básica para evitar o erro de valor nulo no banco
    if (empty($codProduto) || empty($nomeProduto)) {
        die("Erro: Código e Nome do produto são obrigatórios.");
    }

    try {
        // Query adaptada para Prepared Statements do PDO
        $sql = "INSERT INTO produtos (cod, nome, categoria, validade, quantidade, preco_unitario, preco_pacote, imagem)
                VALUES (:cod, :nome, :categoria, :validade, :quantidade, :preco_unitario, :preco_pacote, :imagem)";

        $stmt = $conn->prepare($sql);

        // Executa passando os valores com segurança contra SQL Injection e erros de aspas
        $stmt->execute([
            ':cod'            => $codProduto,
            ':nome'           => $nomeProduto,
            ':categoria'      => $categoria,
            ':validade'       => empty($validade) ? null : $validade, // Trata data vazia como NULL
            ':quantidade'     => (int)$quantidade,
            ':preco_unitario' => (float)$precoUnitario,
            ':preco_pacote'   => (float)$precoPacote,
            ':imagem'         => $imagemProduto
        ]);

        echo "Produto cadastrado com sucesso!";

    } catch (PDOException $e) {
        // Tratamento de erro correto para o PDO
        echo "Erro ao cadastrar o produto no PostgreSQL: " . $e->getMessage();
    }

    // Fecha a conexão no PDO
    $conn = null;
}
?>
