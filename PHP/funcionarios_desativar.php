<?php
// Desativar funcionário - PontoEletrônico
// Arquivo: PHP/funcionarios_desativar.php
// Método: POST. Só administrador. Usado pela lixeira do paginas/funcionarios.html.
//
// O funcionário não é apagado do banco: o status vira 'inativo'.
// Assim ele some da lista e não consegue mais entrar, mas o histórico
// de ponto dele continua guardado.

// 1. Carrega a conexão e a sessão.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao.php';

// 2. Só o administrador pode desativar, e só com POST.
exigirMetodo('POST');
exigirAdministrador();

// 3. Recebe o id do funcionário.
$id = (int) ($_POST['id_funcionario'] ?? 0);

// 4. O administrador não pode desativar a si mesmo.
if ($id === (int) $_SESSION['id_funcionario']) {
    responder(['erro' => 'Você não pode desativar o seu próprio acesso.'], 400);
}

// 5. Confere se o funcionário existe e ainda está ativo.
$sql = 'SELECT nome FROM funcionarios WHERE id_funcionario = ? AND status = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$id, 'ativo']);
$funcionario = $stmt->fetch();

if (!$funcionario) {
    responder(['erro' => 'Funcionário não encontrado.'], 404);
}

// 6. Prepara e executa a desativação.
$sql = 'UPDATE funcionarios SET status = ? WHERE id_funcionario = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute(['inativo', $id]);

// 7. Responde para o JavaScript.
responder(['mensagem' => 'Funcionário ' . $funcionario['nome'] . ' desativado.']);
