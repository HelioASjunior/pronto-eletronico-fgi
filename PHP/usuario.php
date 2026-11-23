<?php
// Usuário logado - PontoEletrônico
// Arquivo: PHP/usuario.php
// Método: GET. Toda página chama ao abrir (js/comum.js) para saber
// quem está logado. Sem login responde 401 e a página volta para o login.

// 1. Carrega a sessão.
require_once __DIR__ . '/sessao.php';

// 2. Confere o método e o login.
exigirMetodo('GET');
exigirLogin();

// 3. Prepara os dados do usuário.
$nome = $_SESSION['nome'];
$partes = explode(' ', $nome);

// 4. Responde para o JavaScript.
responder([
    'nome' => $nome,
    'primeiro_nome' => $partes[0],
    'inicial' => mb_strtoupper(mb_substr($nome, 0, 1)),
    'tipo_acesso' => $_SESSION['tipo_acesso'],
    'acesso' => nomeDoAcesso($_SESSION['tipo_acesso']),
    'pagina_inicial' => paginaInicial(),
]);
