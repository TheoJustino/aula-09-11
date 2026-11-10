<?php
$servername = "69.49.241.16";
$username = "gara4688_round6";
$password = "Batatinhafrita123";
$database = "gara4688_aulas";

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função para buscar produtos pelo código ou nome
function buscarProduto($campo, $valor) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE $campo LIKE ?");
    $valor = "%$valor%";
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    return $stmt->get_result();
}
?>
