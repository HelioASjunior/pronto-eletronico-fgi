<?php
// Sessão do usuário logado - PontoEletrônico
// Arquivo: PHP/sessao.php
// Os outros arquivos usam assim: require_once __DIR__ . '/sessao.php';
//
// A sessão guarda quem fez login. Depois do login ficam guardados:
//   $_SESSION['id_funcionario']  -> id do funcionário no banco
//   $_SESSION['nome']            -> nome completo
//   $_SESSION['tipo_acesso']     -> 'funcionario' ou 'administrador'

// 1. Inicia (ou continua) a sessão.
session_start();


// 2. Responde para o JavaScript em JSON (texto com os dados, ex.: {"nome": "Maria"}).
//    $status é o código HTTP: 200 = deu certo, 400 = dado inválido,
//    401 = não fez login, 403 = sem permissão, 405 = método errado.
function responder($dados, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');          // sempre buscar o dado novo
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Confere se o pedido chegou com o método HTTP certo ('GET' ou 'POST').
function exigirMetodo($metodo)
{
    if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
        responder(['erro' => "Use o método $metodo."], 405);
    }
}

// 4. Ninguém logado: responde 401 e o JavaScript volta para o login.
function exigirLogin()
{
    if (!isset($_SESSION['id_funcionario'])) {
        responder(['erro' => 'Faça login para continuar.'], 401);
    }
}

// 5. Só o administrador pode continuar (ex.: cadastro de funcionários).
function exigirAdministrador()
{
    exigirLogin();

    if ($_SESSION['tipo_acesso'] !== 'administrador') {
        responder(['erro' => 'Apenas administradores podem fazer isso.'], 403);
    }
}

// 6. Página aberta depois do login: administrador vai para Funcionários,
//    funcionário vai para Registrar Ponto.
function paginaInicial()
{
    if ($_SESSION['tipo_acesso'] === 'administrador') {
        return 'paginas/funcionarios.html';
    }
    return 'paginas/painel.html';
}


// 7. Funções para mostrar os dados do jeito certo na tela.

// '12345678900' -> '123.456.789-00'
function formatarCpf($cpf)
{
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

// 'administrador' -> 'Administrador', 'funcionario' -> 'Funcionário'
function nomeDoAcesso($tipo)
{
    if ($tipo === 'administrador') {
        return 'Administrador';
    }
    return 'Funcionário';
}

// 30600 segundos -> '08:30'
function formatarHoras($segundos)
{
    $horas = intdiv($segundos, 3600);
    $minutos = intdiv($segundos % 3600, 60);
    return sprintf('%02d:%02d', $horas, $minutos);
}
