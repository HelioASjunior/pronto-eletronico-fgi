# Ponto Eletrônico

Sistema de ponto eletrônico feito com **HTML**, **CSS**, **JavaScript**, **PHP** e **MySQL**.

- O **funcionário** entra, registra o ponto (entrada e saída) e vê o histórico no Espelho de Ponto.
- O **administrador** também cadastra os funcionários que podem usar o sistema.

## Estrutura do projeto

O HTML e o PHP ficam **separados**: as páginas são HTML puro e o PHP só
conversa com o banco. Quem liga os dois é o JavaScript, com o `fetch()`.

```
PontoEletronico/
├── index.html                    -> tela de login
├── paginas/
│   ├── painel.html               -> Registrar Ponto (funcionário e administrador)
│   ├── espelho.html              -> Espelho de Ponto: histórico do mês
│   ├── funcionarios.html         -> cadastro e lista de funcionários (só administrador)
│   └── banco_horas.html          -> saldo de horas de cada funcionário (só administrador)
├── css/
│   ├── style.css                 -> visual da tela de login
│   ├── painel.css                -> visual do Registrar Ponto e do Espelho de Ponto
│   └── funcionarios.css          -> visual da tela de Funcionários
├── js/
│   ├── script.js                 -> login: olho da senha, sugestões e aviso de erro
│   ├── comum.js                  -> confere o login, menu do administrador, funções de apoio
│   ├── painel.js                 -> relógio, registrar ponto e marcações de hoje
│   ├── espelho.js                -> histórico do mês
│   ├── funcionarios.js           -> cadastro e lista de funcionários
│   └── banco_horas.js            -> resumo e saldos do banco de horas
├── banco/
│   └── banco.sql                 -> cria o banco e as tabelas (só a estrutura, sem dados)
└── PHP/
    ├── conexao.php               -> conexão com o banco de dados
    ├── sessao.php                -> login guardado na sessão e respostas em JSON
    ├── login.php                 -> confere usuário e senha (POST)
    ├── logout.php                -> botão Sair
    ├── usuario.php               -> quem está logado (GET)
    ├── funcionarios_listar.php   -> lista e busca de funcionários (GET)
    ├── funcionarios_cadastrar.php-> cadastra funcionário (POST)
    ├── funcionarios_editar.php   -> altera os dados de um funcionário (POST)
    ├── funcionarios_desativar.php-> desativa um funcionário, sem apagar o histórico (POST)
    ├── ponto_hoje.php            -> marcações de hoje e últimos registros (GET)
    ├── ponto_registrar.php       -> registra o ponto (POST)
    ├── ponto_espelho.php         -> histórico do mês (GET)
    └── banco_horas.php           -> saldo de horas de cada funcionário (GET), jornada de 8h
```

## O que cada parte faz

- **HTML**: diz *o que* aparece na tela (título, campos, botões, tabelas vazias).
- **CSS**: diz *como* aparece (cores, fontes, espaçamentos).
- **JavaScript**: pede os dados ao PHP, preenche as tabelas e envia os formulários.
- **PHP**: roda no servidor. Confere o login, grava e busca no banco e responde em **JSON**.
- **MySQL**: o banco de dados, onde ficam os funcionários e os registros de ponto.

### Como uma página conversa com o PHP

Exemplo do Registrar Ponto:

1. O navegador abre o `paginas/painel.html` (só HTML, com as tabelas vazias).
2. O `js/painel.js` faz `fetch("../PHP/ponto_hoje.php")`, um pedido **GET**.
3. O PHP busca no banco e responde em JSON:
   `{"proximo_tipo": "saida", "hoje": [{"tipo": "entrada", "hora": "08:00"}]}`
4. O JavaScript lê esse JSON e preenche a tela.
5. Ao clicar em Registrar Ponto, o JavaScript faz um pedido **POST** para o
   `PHP/ponto_registrar.php`, que grava no banco.

Sem login, o PHP responde com o código **401** e o JavaScript volta para a tela de login.
Qualquer um consegue abrir um `.html`, mas sem login o PHP não entrega nenhum dado.

### GET e POST

- **POST** quando **grava** algo ou envia senha (login, cadastrar funcionário, registrar ponto).
  Os dados vão no corpo da requisição e não aparecem na barra de endereço.
- **GET** quando só **consulta** (busca de funcionários, filtro de mês do espelho, quem está logado).
  Os dados vão na URL (`?busca=maria`, `?mes=2026-11`), então dá para recarregar ou salvar o link.

## Como rodar

O projeto precisa do **XAMPP** (Apache + MySQL), porque o PHP só roda em um servidor.
Abrir o `index.html` com dois cliques mostra a tela, mas o login não funciona.

1. Coloque a pasta `PontoEletronico` dentro de `C:\xampp\htdocs`.
2. No painel do XAMPP, ligue o **Apache** e o **MySQL**.
3. Abra `http://localhost/phpmyadmin`, vá na aba **Importar** e escolha o arquivo `banco/banco.sql`.
   Ele cria o banco `ponto_eletronico` e as tabelas, vazias.
4. Cadastre o primeiro administrador direto no banco (veja abaixo).
5. Acesse `http://localhost/PontoEletronico/` e entre com o administrador.

### Primeiro administrador

Os usuários e as senhas ficam só no banco de dados, nunca nos arquivos do projeto.
A senha é gravada como **hash** (um código gerado pelo `password_hash()` do PHP),
então não dá para digitar a senha pura direto no phpMyAdmin.

1. Gere o hash da senha no terminal (troque `SUA_SENHA`):
   ```
   C:\xampp\php\php.exe -r "echo password_hash('SUA_SENHA', PASSWORD_DEFAULT);"
   ```
2. No phpMyAdmin, abra a tabela `funcionarios`, aba **Inserir**, e preencha:
   `nome`, `sexo`, `cpf` (11 números), `usuario`, `senha` (cole o hash gerado)
   e `tipo_acesso` = `administrador`.

Depois disso, os outros funcionários são cadastrados pela própria tela de Funcionários.

## Dica para estudar

Todos os arquivos têm **comentários** explicando cada parte do código.
Comece pelo `index.html` e pelo `PHP/login.php` para entender o caminho do login.
Depois veja o `paginas/painel.html`, o `js/painel.js` e o `PHP/ponto_hoje.php` juntos.
