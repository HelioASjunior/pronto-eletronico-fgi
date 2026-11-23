<?php
// Login - PontoEletrônico
// Arquivo: PHP/login.php
// Recebe o formulário do index.html (método POST), confere usuário e senha
// e manda cada um para a sua página. Se der errado, volta para o login
// com ?erro=... na URL e o js/script.js mostra o aviso.

// 1. Carrega a conexão e a sessão.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao.php';

// 2. Só aceita o envio do formulário (POST).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.html');
    exit;
}

// 3. Recebe os campos do formulário.
$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';

// 4. Prepara e executa a busca do funcionário pelo usuário.
$sql = 'SELECT id_funcionario, nome, senha, tipo_acesso, status
        FROM funcionarios
        WHERE usuario = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario]);
$funcionario = $stmt->fetch();

// 5. Confere a senha digitada com o hash do banco.
//    O aviso é o mesmo para usuário ou senha errados (não revela quem existe).
if (!$funcionario || !password_verify($senha, $funcionario['senha'])) {
    header('Location: ../index.html?erro=senha');
    exit;
}

// 6. Funcionário inativo não entra.
if ($funcionario['status'] !== 'ativo') {
    header('Location: ../index.html?erro=inativo');
    exit;
}

// 7. Guarda o funcionário na sessão e vai para a página dele.
session_regenerate_id(true);       // novo código de sessão (mais seguro)
$_SESSION['id_funcionario'] = $funcionario['id_funcionario'];
$_SESSION['nome'] = $funcionario['nome'];
$_SESSION['tipo_acesso'] = $funcionario['tipo_acesso'];

header('Location: ../' . paginaInicial());
exit;
