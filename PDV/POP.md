# Procedimento Operacional Padrão — Implantação do PDV

## 1. Identificação

- **Nome:** Instalação, configuração, inicialização e operação do sistema PDV.
- **Sistema:** PDV — Ponto de Venda.
- **Objetivo:** orientar a preparação do ambiente, o acesso aos módulos MVC e os cuidados de operação e segurança.
- **Responsáveis pela execução:** administrador do ambiente ou responsável técnico designado pela instituição. A operação diária cabe ao responsável pela demonstração.

## 2. Pré-requisitos

- Sistema operacional compatível com XAMPP. O ambiente de desenvolvimento observado é um container Ubuntu 24.04; ele não é uma instalação XAMPP.
- XAMPP/Apache com PHP 8.2 como versão-alvo do projeto. A última verificação local usou PHP CLI 8.3.33; a aplicação não foi validada no XAMPP 8.2 real.
- PostgreSQL acessível pela aplicação. A conexão exige SSL e usa opções de endpoint originalmente destinadas ao serviço Neon; confirme que o provedor escolhido aceita essas opções.
- Extensões PHP `PDO`, `pdo_pgsql` e `fileinfo` habilitadas na instalação que atende as requisições Apache. O driver `pdo_pgsql` não está disponível no ambiente de desenvolvimento verificado.
- Apache com `mod_rewrite` e permissão para aplicar `.htaccess` (`AllowOverride` compatível com as regras do projeto).
- Permissão de leitura para o código e de escrita do usuário Apache/PHP em `PDV/public/uploads/products` para receber imagens. A pasta não deve ser aberta para escrita por usuários não confiáveis.
- Acesso ao código-fonte e às configurações do Apache e do banco. Não é necessário Composer ou npm: não há manifestos desses gerenciadores no projeto.
- Conexão à internet para carregar o Bootstrap 5.3.0-alpha1 referenciado por CDN.

## 3. Preparação do ambiente

### 3.1 Código e diretório público

Disponibilize o projeto em um diretório controlado e configure o VirtualHost para apontar o `DocumentRoot` somente para `PDV/public`. Use o caminho absoluto correspondente à máquina; não copie o caminho do ambiente de desenvolvimento como se fosse universal.

Exemplo estrutural para o VirtualHost do Apache, substituindo o marcador pelo caminho real:

```apache
DocumentRoot "<caminho-absoluto>/PDV/public"
<Directory "<caminho-absoluto>/PDV/public">
    AllowOverride All
    Require all granted
</Directory>
```

Habilite `mod_rewrite` na configuração do Apache e reinicie o serviço depois de alterar o VirtualHost. As regras em `public/.htaccess` encaminham caminhos MVC ao front controller e servem arquivos estáticos existentes. `PDV/.htaccess` é uma defesa adicional; não substitui o `DocumentRoot` correto. Não use `PDV/` como raiz pública.

Os scripts antigos da raiz foram retirados durante a limpeza. O fluxo operacional documentado neste procedimento é o MVC servido por `public/index.php`.

### 3.2 Banco e arquivo `.env`

`app/Config/Database.php` lê `PDV/.env` diretamente, sem biblioteca dotenv. Crie o arquivo localmente com a variável `SRVDB`, substituindo todos os marcadores pelos dados fornecidos pelo administrador do PostgreSQL:

```dotenv
SRVDB="postgresql://<usuario>:<senha>@<host>:<porta>/<banco>"
```

A conexão constrói uma DSN `pgsql`, exige `sslmode=require`, usa o primeiro segmento do host como `endpoint` e solicita o fuso `America/Cuiaba`. Essa opção de endpoint pode não ser aceita por uma instalação PostgreSQL genérica/local. Antes da implantação, confirme o formato de conexão exigido pelo provedor sem remover opções do código por tentativa.

Não há `.env.example`. Não coloque credenciais reais em `POP.md`, `README.md`, commits ou mensagens. Não copie o `.env` para `public/`.

### 3.3 Acesso para demonstração

A autenticação de operador foi removida para a avaliação acadêmica local. Não configure senha, hash ou variável de segredo. Todas as rotas MVC ficam acessíveis a quem alcançar a aplicação; portanto, restrinja o VirtualHost a localhost e não publique/encaminhe a porta para redes externas. Esta configuração não é adequada para produção.

### 3.4 Diretórios e arquivos sensíveis

- Mantenha `.env`, `app/`, `config/` e `database/` fora da raiz pública; somente `public/` deve ser servida.
- Preserve as regras `.htaccess` existentes e confirme que o Apache está autorizado a aplicá-las.
- Garanta escrita apenas para o usuário de serviço em `public/uploads/products`.
- A pasta de uploads desativa listagem/CGI e nega extensões executáveis. O serviço aceita somente JPEG, PNG e WebP, valida MIME/extensão e imagem, limita o tamanho a 2 MB e gera nomes aleatórios.
- A pasta antiga `PDV/imagens/` foi removida. Produtos cujo campo `imagem` ainda referencia arquivos antigos podem não apresentar prévia; imagens novas devem ser enviadas pelo formulário MVC.

## 4. Preparação do banco de dados

**Não execute as migrations até confirmar o schema do banco alvo.** O ambiente de desenvolvimento atual não tem `pdo_pgsql` e a integração PostgreSQL não foi validada.

1. Crie ou selecione o banco PostgreSQL usando o procedimento aprovado pelo administrador/serviço de hospedagem.
2. Verifique a existência da tabela preexistente `produtos` e seus campos. O `ProductRepository` usa `id`, `cod`, `nome`, `categoria`, `validade`, `quantidade`, `preco_unitario`, `preco_pacote` e `imagem`, mas o repositório não contém DDL que confirme tipos, tamanhos, nulabilidade ou restrições da tabela.
3. Confirme especificamente o tipo nativo de `produtos.id`. A migration de vendas declara `itens_venda.produto_id INTEGER REFERENCES produtos(id)`; a criação dessa FK pode falhar se os tipos forem incompatíveis. Não ajuste o schema automaticamente: interrompa e peça validação técnica caso não corresponda.
4. Confira se as tabelas `clientes`, `vendas` ou `itens_venda` já existem. Os arquivos usam `CREATE TABLE` sem `IF NOT EXISTS`; executá-los sobre tabelas existentes causará erro.
5. Aplique manualmente, uma única vez, nesta ordem:
   1. `database/migrations/20261110_create_clientes.sql`
   2. `database/migrations/20261110_create_sales.sql`

A migration de clientes define `clientes.id` como `BIGINT IDENTITY` e CPF obrigatório/único. A migration de vendas depende de `clientes` e da tabela preexistente `produtos`; cria `vendas` e `itens_venda`, usa `ON DELETE SET NULL` para o cliente e `ON DELETE RESTRICT` para o produto. Não existe migration de produtos.

Com `psql` instalado, os arquivos podem ser aplicados manualmente a partir de `PDV/`. `-W` solicita a senha no terminal; substitua os marcadores e use somente o host/banco autorizados:

```sh
psql -W -h <host> -p <porta> -U <usuario> -d <banco> -f database/migrations/20261110_create_clientes.sql
psql -W -h <host> -p <porta> -U <usuario> -d <banco> -f database/migrations/20261110_create_sales.sql
```

Após cada execução, confira as tabelas e chaves no cliente PostgreSQL. Este procedimento é documental: nenhuma migration foi executada durante sua elaboração.

## 5. Inicialização

1. Inicie o Apache pelo painel de controle do XAMPP e confirme que não há erro na configuração do VirtualHost.
2. Inicie/valide o PostgreSQL ou o serviço de banco fornecido pelo ambiente.
3. Confirme que `PDV/.env` contém `SRVDB` e que o processo PHP consegue ler o arquivo.
4. Acesse no navegador a raiz do VirtualHost (acrescente o prefixo do subdiretório, se houver). Não há tela de login.
5. Confirme que o painel e a navegação lateral aparecem. A página inicial não depende de consulta ao banco; produtos, clientes, carrinho, vendas e relatórios dependem do PostgreSQL e das tabelas necessárias.

## 6. Operação do sistema

As rotas não exigem autenticação nesta versão local de demonstração. Formulários que alteram dados continuam usando POST e proteção CSRF; após operações bem-sucedidas a aplicação usa redirecionamento para evitar reenvio.

### Produtos

1. Acesse Produtos no menu para consultar ou pesquisar por nome/código.
2. Use **Novo produto** para cadastrar código, nome, categoria, validade, quantidade, preços unitário/pacote e, opcionalmente, imagem.
3. Na edição, deixe o campo de imagem vazio para manter o valor atual. Uploads aceitos: JPEG, PNG ou WebP, até 2 MB.
4. A listagem marca estoque zerado e considera baixo estoque até 5 unidades.
5. A exclusão é feita por POST. Se o banco rejeitar a exclusão por relacionamento com venda, a aplicação apresenta uma mensagem e preserva o registro.

### Clientes

1. Acesse Clientes para listar os cadastros.
2. Use o formulário para cadastrar nome e CPF, com telefone/e-mail opcionais.
3. Edite ou exclua pelas ações da listagem. A validação verifica formato do CPF, e-mail e CPF duplicado.

### Carrinho e venda

1. Acesse **Nova venda** pelo menu.
2. Adicione produtos e quantidades disponíveis. O carrinho fica na sessão e não altera estoque.
3. Ajuste ou remova itens conforme necessário; estoque e preços são conferidos novamente ao fechar a venda.
4. Selecione opcionalmente um cliente e a forma de pagamento (`dinheiro`, `pix` ou `cartao`) e confirme.
5. Após o commit da transação, o carrinho é limpo e a tela de confirmação exibe a venda persistida. Se ocorrer falha antes da confirmação, a transação sofre rollback e o carrinho é preservado.

### Relatórios e encerramento da sessão

1. Acesse Relatórios para consultar vendas, opcionalmente filtrando por data inicial/final e forma de pagamento.
2. Use **Visualizar** para consultar cliente (quando associado), data, pagamento, produtos, quantidades, preços e subtotais.
3. Não há ação de login ou logout. As sessões PHP continuam sendo usadas pelo carrinho, CSRF e mensagens temporárias; restrinja o uso ao ambiente local.

## 7. Verificações e solução de problemas

| Sintoma | Verificações recomendadas |
|---|---|
| Erro de conexão PostgreSQL | Confirme `PDV/.env`, a sintaxe da URL `SRVDB`, host/porta/banco, `pdo_pgsql`, SSL obrigatório e suporte do provedor à opção `endpoint`. Não publique a URL em logs ou mensagens. |
| Rotas retornam 404 | Confirme `DocumentRoot` em `PDV/public`, `mod_rewrite` ativo, `AllowOverride All`, `.htaccess` presente e prefixo/URL usados no VirtualHost. |
| Upload falha por permissão | Confirme escrita do usuário Apache/PHP em `public/uploads/products`; valide também limite de upload do PHP acima de 2 MB considerando o multipart. Não dê permissão ampla ao diretório. |
| Upload rejeitado | Use JPEG/PNG/WebP verdadeiro, extensão compatível e arquivo menor ou igual a 2 MB; confirme `fileinfo` na configuração PHP carregada pelo Apache. |
| A aplicação não pede senha | Esse é o comportamento intencional da versão de demonstração. Não a exponha à rede; a autenticação foi removida e não há controle de acesso por usuário. |
| Extensão PHP ausente | Verifique o `php.ini` realmente usado pelo Apache/XAMPP, não somente o PHP CLI. Habilite `pdo_pgsql` e `fileinfo` e reinicie o Apache. |
| Migration falha | Não repita `CREATE TABLE` sem verificar se a tabela já existe. Confirme ordem (clientes antes de vendas), existência de `produtos` e compatibilidade entre `produtos.id` e `produto_id INTEGER`. Preserve o erro SQL completo apenas em ambiente administrativo protegido. |

O servidor embutido do PHP não lê `.htaccess`; ele não é equivalente à validação final em Apache. A integração PostgreSQL, as migrations e o fluxo real de upload ainda precisam ser testados no ambiente de implantação.

## 8. Encerramento e segurança

- Feche a aba/janela da demonstração e pare Apache/PostgreSQL local pelo painel XAMPP quando não forem necessários a outros serviços; não desligue um banco gerenciado compartilhado sem autorização.
- Guarde `.env`, backups e arquivos de configuração fora do controle de versão e com acesso restrito. Nunca compartilhe credenciais ou tokens CSRF.
- Como não há autenticação, limite o serviço a localhost e a dados fictícios/de demonstração. Não use esta configuração em produção ou redes compartilhadas.
- Mantenha o site sob HTTPS em uso remoto, faça backups do banco e dos uploads conforme a política institucional e teste a restauração em ambiente controlado.
- Preserve `PDV/public` como única raiz pública. Não exponha `app/`, `config/`, `database/` ou `.env`; as regras `.htaccess` são defesa adicional e não substituem a configuração correta do `DocumentRoot`.

### Situação das validações

Na última verificação, o ambiente tinha PHP CLI 8.3.33, somente o driver PDO SQLite e `fileinfo` habilitado. Foram feitos lint, verificações de rotas e testes isolados com SQLite em memória para partes do repository, validação, carrinho, relatórios e CSRF. Isso não comprova integração PostgreSQL. A conexão real, as migrations, o XAMPP/PHP 8.2 e uploads via Apache permanecem pendentes de validação no ambiente de implantação.
