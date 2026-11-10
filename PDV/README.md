# PDV — Ponto de Venda

## Apresentação

O PDV é uma aplicação web para apoiar rotinas básicas de comércio: manutenção do catálogo de produtos e dos clientes, composição de carrinho, registro de vendas e consulta do histórico. A interface MVC fica em `public/`; os scripts PHP antigos permanecem no projeto, mas não fazem parte do roteamento MVC.

## Funcionalidades

- Acesso operacional por segredo compartilhado, com hash configurado fora do código, sessão regenerada no login e logout via POST.
- CRUD de produtos com pesquisa por nome/código, indicação de estoque e upload de imagem JPEG, PNG ou WebP de até 2 MB.
- CRUD de clientes com validação de CPF, e-mail e duplicidade.
- Carrinho de sessão com alteração e remoção de itens, consulta de estoque e cálculo em centavos. O carrinho não baixa estoque.
- Finalização de venda com validação de cliente/pagamento, leitura do preço no banco, bloqueio dos produtos, registro da venda/itens e atualização transacional do estoque.
- Relatórios de vendas com filtros por data e pagamento e consulta dos detalhes de uma venda.
- Layout responsivo com navegação lateral, CSS e JavaScript próprios e Bootstrap carregado por CDN.

O indicador de estoque baixo considera até 5 unidades; estoque zero tem indicação própria. Esse limite é uma regra fixa no controller, pois a tabela existente não fornece um limite configurável.

## Tecnologias

- PHP 8.2 como versão-alvo; o ambiente de desenvolvimento usado na última validação tinha PHP CLI 8.3.33.
- PDO com PostgreSQL. O driver `pdo_pgsql` precisa estar habilitado no ambiente de execução.
- Apache/XAMPP com `mod_rewrite` e suporte a `.htaccess`.
- HTML, CSS, JavaScript nativo e Bootstrap 5.3.0-alpha1 por CDN.
- Extensão PHP `fileinfo` para verificar o tipo real dos uploads.

Não há `composer.json`, `package.json` ou `.env.example` no projeto; não é necessário instalar dependências PHP ou JavaScript via gerenciador de pacotes.

## Estrutura

```text
PDV/
├── app/
│   ├── Config/       # Conexão PDO
│   ├── Controllers/  # Entrada HTTP, validação e seleção de views
│   ├── Models/       # Objetos de dados Customer, Sale e SaleItem
│   ├── Repositories/ # Consultas PDO para clientes, produtos e vendas
│   ├── Services/     # Carrinho, venda, dinheiro, operador e imagens
│   └── Views/        # Layout e telas de clientes, produtos, carrinho e vendas
├── config/routes.php
├── database/migrations/
├── public/
│   ├── index.php     # Front Controller
│   ├── assets/       # CSS e JavaScript da interface
│   └── uploads/products/ # Imagens enviadas pelo CRUD MVC
├── imagens/         # Imagens usadas pelos scripts legados
├── db.php           # Conexão/funções dos scripts legados
├── pdv.php          # PDV legado, fora das rotas MVC
└── OPERADOR.md      # Instruções complementares do segredo do operador
```

Arquivos legados adicionais incluem `editar_produto.php`, `salvar_produto.php`, `salvar_produtomysql.php` e `index.html`. Eles não foram migrados para o MVC nem devem ser confundidos com as rotas atuais. O módulo MVC de produtos usa `ProductRepository` e a conexão de `Database`.

## Arquitetura

O front controller em `public/index.php` normaliza método e caminho, procura a chave `MÉTODO /caminho` devolvida por `config/routes.php`, aplica o `OperatorGuard` às rotas operacionais e então executa a closure correspondente. As closures retornam dados e o identificador da view; o dispatcher inclui a view e o layout compartilhado.

- **MVC:** controllers recebem entradas, chamam repositórios/serviços e fornecem dados às views; as views em `app/Views/` apresentam dados escapados.
- **Repository:** `CustomerRepository`, `ProductRepository` e `SaleRepository` concentram SQL e prepared statements.
- **Service Layer:** `CartService` mantém e valida o carrinho; `SaleService` coordena a finalização; `Money` faz cálculos em centavos; `OperatorGuard` autentica/autorização de operador; `ProductImageStorage` valida e grava imagens.
- **Front Controller:** `public/index.php` é a única entrada MVC e aplica autorização centralizada antes das closures.
- **Injeção de dependência:** as factories em `config/routes.php` obtêm o PDO único de `Database::getConnection()` e o passam aos repositórios; repositórios e serviços são injetados nos controllers/serviços que os utilizam.

Os Models `Customer`, `Sale` e `SaleItem` representam dados. O CRUD de produtos utiliza arrays associativos retornados pelo repository, seguindo o contrato que já era usado pelo carrinho e pelas vendas.

## Finalização de venda

1. O carrinho armazena na sessão somente IDs e quantidades; estoque e preços são consultados no servidor.
2. O `SaleService` rejeita carrinho vazio, forma de pagamento inválida e cliente inexistente, quando informado.
3. O serviço abre uma transação PDO e solicita os produtos ordenados por ID com `SELECT ... FOR UPDATE`.
4. Confere a existência e o estoque novamente e usa os preços obtidos do banco para calcular subtotais e total em centavos.
5. Registra a venda e seus itens e reduz o estoque com atualização condicional que exige quantidade suficiente.
6. Confirma a transação e só então limpa o carrinho.

Uma exceção durante as operações provoca rollback; como o carrinho só é limpo após o commit, permanece disponível se a transação falhar. O fluxo exige PostgreSQL para validar em integração o `FOR UPDATE`, `RETURNING` e o comportamento transacional real.

## Ambiente XAMPP

1. Use XAMPP com PHP 8.2, Apache e as extensões `pdo_pgsql` e `fileinfo` habilitadas.
2. Configure o `DocumentRoot` do VirtualHost para `<caminho-absoluto-do-projeto>/PDV/public`, e a seção `<Directory>` correspondente com `AllowOverride All` e `Require all granted`. Habilite `mod_rewrite`; as regras do `.htaccess` encaminham as rotas ao front controller e desativam listagem de diretórios.
3. Não use `PDV/` como raiz pública. Código interno, migrations e o `.env` ficam fora de `public/`; os scripts legados não são a entrada MVC.
4. Garanta que o usuário do Apache/PHP possa gravar em `PDV/public/uploads/products`. A pasta e suas regras de bloqueio são criadas/fornecidas pelo módulo de imagens.
5. Acesse a URL configurada no VirtualHost e entre pelo endereço `/operador/acesso`.

O `.htaccess` de uploads nega execução de extensões de script. A configuração deve ser conferida no Apache real, pois o servidor embutido do PHP não aplica `.htaccess`.

## Banco e ambiente

`PDV/app/Config/Database.php` lê `PDV/.env` diretamente; não usa biblioteca dotenv nem busca `SRVDB` no ambiente do Apache. Crie o arquivo localmente com a URL de conexão no formato abaixo, preenchendo os marcadores com dados do seu provedor e sem incluir esse conteúdo no repositório:

```dotenv
SRVDB="postgresql://<usuario>:<senha>@<host>:<porta>/<banco>"
```

A conexão configura PDO para exceções e fetch associativo, exige SSL (`sslmode=require`) e define o fuso `America/Cuiaba`. Também deriva um `endpoint` a partir do primeiro segmento do host, requisito do ambiente Neon usado originalmente. Um PostgreSQL local ou outro provedor pode não aceitar essa opção; valide a compatibilidade do endpoint antes de usar.

Não há `.env.example`. Nunca copie credenciais reais para este README, mensagens de erro, commits ou documentação compartilhada.

## Acesso do operador

Configure `PDV_OPERATOR_SECRET_HASH` no ambiente do Apache, conforme o guia [OPERADOR.md](OPERADOR.md). Gere um hash localmente com PHP CLI; o segredo deve ser informado durante a execução e não deve ser salvo no código ou na sessão:

```sh
php -r '$secret = trim(fgets(STDIN)); echo password_hash($secret, PASSWORD_DEFAULT), PHP_EOL;'
```

No `<VirtualHost>` do XAMPP, defina `SetEnv PDV_OPERATOR_SECRET_HASH "<hash-gerado-localmente>"` e reinicie o Apache. A aplicação valida com `password_verify()`, regenera o ID de sessão e guarda somente uma marca de autenticação. Formulários mutáveis mantêm CSRF; cookies usam `HttpOnly`, `SameSite=Lax` e `Secure` quando o PHP detecta HTTPS.

O acesso é baseado em um segredo compartilhado, não em contas individuais. Não há atribuição/auditoria por operador, recuperação de senha ou limitação de tentativas.

## Migrations

Não existe executor automático. As migrations devem ser aplicadas manualmente, uma única vez, em um banco de desenvolvimento/implantação preparado:

1. Confirme que a tabela `produtos` já existe. Não há migration de produtos no projeto.
2. Confira no banco alvo o tipo nativo de `produtos.id`. Os scripts legados não contêm DDL. A migration de vendas declara `itens_venda.produto_id INTEGER`, portanto esse tipo precisa ser compatível com a chave existente antes de prosseguir.
3. Aplique `database/migrations/20261110_create_clientes.sql`.
4. Aplique `database/migrations/20261110_create_sales.sql`, que depende de `clientes` e `produtos`.

A migration de clientes cria `clientes` com `id BIGINT IDENTITY`, CPF obrigatório/único e data de criação. A migration de vendas cria `vendas` e `itens_venda`, com total/preços `NUMERIC(14,2)`, quantidade inteira positiva, formas `dinheiro`, `pix` ou `cartao`, exclusão de cliente com `ON DELETE SET NULL` e itens relacionados por chave estrangeira. As migrations usam `CREATE TABLE` sem `IF NOT EXISTS`; verifique se as tabelas já existem antes de executá-las. Não execute a migration de vendas até confirmar o tipo de `produtos.id`.

Com `psql` instalado, a partir da pasta `PDV/`, o formato de execução é:

```sh
psql -W -h <host> -p <porta> -U <usuario> -d <banco> -f database/migrations/20261110_create_clientes.sql
psql -W -h <host> -p <porta> -U <usuario> -d <banco> -f database/migrations/20261110_create_sales.sql
```

`-W` solicita a senha no terminal. Confirme previamente a existência de `produtos` e a compatibilidade da FK; não há migration de produtos.

## Rotas e uso

A autorização do operador é aplicada no dispatcher às operações de clientes, produtos, carrinho, vendas e relatórios.

| Método | Rota | Uso |
|---|---|---|
| GET | `/` | Painel com atalhos para módulos MVC disponíveis |
| GET / POST | `/operador/acesso` | Formulário e validação do segredo do operador |
| POST | `/operador/sair` | Logout com CSRF |
| GET | `/produtos` | Listagem e busca por nome/código |
| GET | `/produtos/novo` | Formulário de cadastro |
| POST | `/produtos` | Cadastro |
| GET | `/produtos/editar?id=...` | Formulário de edição |
| POST | `/produtos/atualizar` | Atualização |
| POST | `/produtos/excluir` | Exclusão; pode ser recusada se houver vendas relacionadas |
| GET | `/clientes` | Listagem de clientes |
| GET / POST | `/clientes/novo` e `/clientes` | Formulário e cadastro |
| GET | `/clientes/editar?id=...` | Formulário de edição |
| POST | `/clientes/atualizar` | Atualização |
| POST | `/clientes/excluir` | Exclusão |
| GET | `/carrinho` | Consultar carrinho e iniciar venda |
| POST | `/carrinho/itens` | Adicionar produto |
| POST | `/carrinho/quantidade` | Alterar quantidade |
| POST | `/carrinho/remover` | Remover produto |
| POST | `/vendas` | Finalizar venda |
| GET | `/vendas/confirmacao?id=...` | Confirmação persistida da venda |
| GET | `/vendas/relatorios` | Histórico com filtros de data/pagamento |
| GET | `/vendas/relatorios/detalhes?id=...` | Itens e valores de uma venda |

A sequência usual é autenticar o operador, acessar Produtos e Clientes conforme necessário, montar o carrinho, selecionar forma de pagamento/cliente e confirmar. Relatórios permitem consultar as vendas existentes. Todas as mutações dos módulos usam POST e CSRF.

## Validação e limitações

- Há validações CLI ad hoc e verificações de sintaxe, mas não foi encontrado um framework/pacote de testes automatizados.
- Foram executados testes com SQLite em memória para operações do `ProductRepository`, regras do carrinho, cálculos, listagem/filtros/detalhes de relatórios e validações de controller. Esses testes não substituem a integração PostgreSQL.
- PHP CLI disponível na última verificação: 8.3.33; PDO listou somente `sqlite`. A conexão PostgreSQL não foi confirmada e nenhuma migration foi executada. O uso com PHP 8.2/XAMPP e PostgreSQL real ainda requer validação nesse ambiente.
- O tipo nativo e as restrições da tabela legada `produtos` não estão documentados por DDL no repositório; confirme-os, especialmente `produtos.id`, antes da migration de vendas.
- A listagem de relatórios não tem paginação. O limite de estoque baixo está fixado em cinco unidades.
- Imagens legadas armazenadas em `PDV/imagens/` são preservadas no campo do produto, mas só imagens presentes em `public/uploads/products/` têm prévia servida pelo MVC.
- O hash do operador é compartilhado, não identifica pessoas individualmente. O cookie `Secure` depende de o PHP detectar HTTPS; confira a configuração se houver proxy terminando TLS.
