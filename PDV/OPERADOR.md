# Acesso do operador

O módulo operacional exige `PDV_OPERATOR_SECRET_HASH` no ambiente do PHP. O valor deve ser um hash produzido com `password_hash()`; o segredo original não deve ser salvo no projeto, em `$_SESSION` ou no controle de versão. Sem a variável configurada, a autenticação falha de forma fechada.

No XAMPP, gere o hash em um terminal PHP local e configure-o no bloco `<VirtualHost>` do Apache, em `apache/conf/extra/httpd-vhosts.conf`:

```apache
SetEnv PDV_OPERATOR_SECRET_HASH "<hash-gerado-com-password_hash>"
```

Gere o hash com PHP 8.2 usando `password_hash($segredo, PASSWORD_DEFAULT)`. Não inclua o segredo real nem o hash real neste arquivo ou em commits. Reinicie o Apache após alterar a configuração.

O formulário de acesso valida o segredo com `password_verify()`. Após o sucesso, a aplicação regenera o ID da sessão e guarda apenas a marca de operador autenticado. O token CSRF existente é mantido durante a regeneração. Use HTTPS fora do ambiente local; o cookie da sessão recebe a flag `Secure` quando o PHP detecta HTTPS.