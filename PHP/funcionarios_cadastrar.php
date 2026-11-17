<?php
// Cadastro de funcionário - PontoEletrônico
// Arquivo: PHP/funcionarios_cadastrar.php
// Método: POST. Só administrador. Usado pelo paginas/funcionarios.html.

// 1. Carrega a conexão e a sessão.
require __DIR__ . '/conexao.php';
require __DIR__ . '/sessao.php';

// 2. Só o administrador pode cadastrar, e só com POST.
exigirMetodo('POST');
exigirAdministrador();

// 3. Recebe os campos do formulário.
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

// 5. Confere os dados. O HTML já obriga a preencher, mas o PHP confere de novo,
//    porque qualquer um pode enviar dados para cá sem passar pelo formulário.
if ($nome === '' || $usuario === '' || $senha === '') {
    responder(['erro' => 'Preencha todos os campos obrigatórios.'], 400);
}
if (!in_array($sexo, ['feminino', 'masculino', 'nao_informado'])) {
    responder(['erro' => 'Selecione o sexo.'], 400);
}
if (strlen($cpf) !== 11 || !ctype_digit($cpf)) {       // ctype_digit: só números
    responder(['erro' => 'O CPF precisa ter 11 números.'], 400);
}
if (!in_array($acesso, ['funcionario', 'administrador'])) {
    responder(['erro' => 'Selecione o tipo de acesso.'], 400);
}
if (strlen($senha) < 6) {
    responder(['erro' => 'A senha precisa ter pelo menos 6 caracteres.'], 400);
}

// 6. Prepara a senha para o banco (hash: código que não dá para desfazer).
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// 7. Prepara e executa o cadastro.
$sql = 'INSERT INTO funcionarios
        (nome, sexo, cpf, usuario, senha, cargo, departamento, tipo_acesso)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([$nome, $sexo, $cpf, $usuario, $senhaHash, $cargo, $departamento, $acesso]);
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

// 8. Responde para o JavaScript.
responder(['mensagem' => "Funcionário $nome cadastrado com sucesso!"]);
