<?php

// Função para buscar a chave SRVDB no arquivo .env
function obterSrvdbDoEnv($caminho) {
    if (!file_exists($caminho)) return '';
    
    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (preg_match('/^SRVDB\s*=\s*(.*)$/', $linha, $matches)) {
            $valor = trim($matches[1]);
            return trim($valor, "\"'");
        }
    }
    return '';
}

// Busca a string de conexão
$connectionString = obterSrvdbDoEnv(__DIR__ . '/.env');

if (empty($connectionString)) {
    die("Erro: Chave de conexão SRVDB não foi encontrada no ambiente.");
}

try {
    // Quebra a URL do Neon para extrair os componentes individuais
    $urlComposta = parse_url($connectionString);

    if (!$urlComposta || !isset($urlComposta['host'])) {
        throw new Exception("Formato da chave SRVDB inválido no .env.");
    }

    $host     = $urlComposta['host'];
    $port     = $urlComposta['port'] ?? 5432;
    $user     = $urlComposta['user'] ?? '';
    $password = $urlComposta['pass'] ?? '';
    $dbname   = ltrim($urlComposta['path'] ?? '', '/');

    // SOLUÇÃO DO ERRO: Extrai a primeira parte do host (Endpoint ID)
    // Ex: de "ep-fancy-queen...neon.tech" extrai "ep-fancy-queen...-pooler"
    $hostPartes = explode('.', $host);
    $endpointId = $hostPartes[0];

    // Monta a DSN injetando obrigatoriamente o endpoint nas opções (passando 'endpoint=seu_id')
    // Também adicionamos o fuso horário exigido de Cuiabá separado por um espaço nas opções
    $optionsString = "endpoint=$endpointId --timezone=America/Cuiaba";
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require;options='{$optionsString}'";

    // Conecta ao Neon PostgreSQL
    $conn = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

} catch (Exception $e) {
    die("Falha na conexão com o Neon PostgreSQL: " . $e->getMessage());
}

// Função para buscar produtos adaptada para o PostgreSQL
function buscarProduto($campo, $valor) {
    global $conn;
    
    $camposPermitidos = ['codigo', 'nome']; 
    if (!in_array($campo, $camposPermitidos)) {
        throw new InvalidArgumentException("Campo de busca inválido.");
    }
    
    // Usamos ILIKE no PostgreSQL para ignorar maiúsculas/minúsculas na busca
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE $campo ILIKE :valor");
    $stmt->execute([
        'valor' => "%" . $valor . "%"
    ]);
    
    return $stmt->fetchAll();
}
?>
