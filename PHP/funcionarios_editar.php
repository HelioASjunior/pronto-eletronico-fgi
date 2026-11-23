<?php
// Edição de funcionário - PontoEletrônico
// Arquivo: PHP/funcionarios_editar.php
// Método: POST. Só administrador. Usado pelo paginas/funcionarios.html.
// Recebe os mesmos campos do cadastro, mais o id_funcionario.
// A senha é opcional: em branco, continua a mesma.

// 1. Carrega a conexão e a sessão.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao.php';

// 2. Só o administrador pode editar, e só com POST.
exigirMetodo('POST');
exigirAdministrador();

// 3. Recebe os campos do formulário.
$id = (int) ($_POST['id_funcionario'] ?? 0);
$nome = trim($_POST['nome'] ?? '');
$sexo = $_POST['sexo'] ?? '';
$cpf = $_POST['cpf'] ?? '';
$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';
$cargo = $_POST['cargo'] ?? '';
$departamento = $_POST['departamento'] ?? '';
$acesso = $_POST['acesso'] ?? '';

// 4. Prepara o CPF para o banco: grava só os números.
$cpf = str_replace(['.', '-'], '', $cpf);

// Cargo e departamento são opcionais: vazio vira NULL no banco.
if ($cargo === '') {
    $cargo = null;
}
if ($departamento === '') {
    $departamento = null;
}

// 5. Confere os dados.
if ($nome === '' || $usuario === '') {
    responder(['erro' => 'Preencha todos os campos obrigatórios.'], 400);
}
if (!in_array($sexo, ['feminino', 'masculino', 'nao_informado'])) {
    responder(['erro' => 'Selecione o sexo.'], 400);
}
if (strlen($cpf) !== 11 || !ctype_digit($cpf)) {
    responder(['erro' => 'O CPF precisa ter 11 números.'], 400);
}
if (!in_array($acesso, ['funcionario', 'administrador'])) {
    responder(['erro' => 'Selecione o tipo de acesso.'], 400);
}
if ($senha !== '' && strlen($senha) < 6) {
    responder(['erro' => 'A senha precisa ter pelo menos 6 caracteres.'], 400);
}

// 6. Confere se o funcionário existe.
$sql = 'SELECT id_funcionario FROM funcionarios WHERE id_funcionario = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    responder(['erro' => 'Funcionário não encontrado.'], 404);
}

// 7. O administrador não pode tirar o próprio acesso de administrador
//    (ele perderia na hora o acesso a esta tela).
$editandoASiMesmo = ($id === (int) $_SESSION['id_funcionario']);
if ($editandoASiMesmo && $acesso !== 'administrador') {
    responder(['erro' => 'Você não pode tirar o seu próprio acesso de administrador.'], 400);
}

// 8. Prepara a alteração. Com senha nova, ela também é trocada;
//    com a senha em branco, a coluna senha fica de fora.
if ($senha === '') {
    $sql = 'UPDATE funcionarios
            SET nome = ?, sexo = ?, cpf = ?, usuario = ?, cargo = ?, departamento = ?, tipo_acesso = ?
            WHERE id_funcionario = ?';
    $valores = [$nome, $sexo, $cpf, $usuario, $cargo, $departamento, $acesso, $id];
} else {
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = 'UPDATE funcionarios
            SET nome = ?, sexo = ?, cpf = ?, usuario = ?, cargo = ?, departamento = ?, tipo_acesso = ?, senha = ?
            WHERE id_funcionario = ?';
    $valores = [$nome, $sexo, $cpf, $usuario, $cargo, $departamento, $acesso, $senhaHash, $id];
}

// 9. Executa a alteração.
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute($valores);
} catch (PDOException $erro) {
    // Erro 1062 = CPF ou usuário que já existe no banco (coluna UNIQUE).
    if ($erro->errorInfo[1] == 1062) {
        if (str_contains($erro->getMessage(), "'cpf'")) {
            responder(['erro' => 'Já existe um funcionário com este CPF.'], 409);
        }
        responder(['erro' => 'Este usuário (login) já está em uso. Escolha outro.'], 409);
    }
    throw $erro;
}

// 10. Se editou o próprio nome, atualiza o nome guardado no login.
if ($editandoASiMesmo) {
    $_SESSION['nome'] = $nome;
}

// 11. Responde para o JavaScript.
responder(['mensagem' => "Dados de $nome atualizados com sucesso!"]);
