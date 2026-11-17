<?php
// Lista de funcionários - PontoEletrônico
// Arquivo: PHP/funcionarios_listar.php
// Método: GET. Só administrador. Usado pelo paginas/funcionarios.html.
// A busca vai na URL: funcionarios_listar.php?busca=maria

// 1. Carrega a conexão e a sessão.
require __DIR__ . '/conexao.php';
require __DIR__ . '/sessao.php';

// 2. Só o administrador pode ver a lista, e só com GET.
exigirMetodo('GET');
exigirAdministrador();

// 3. Recebe o texto da busca (vazio = mostra todos).
$busca = trim($_GET['busca'] ?? '');

// 4. Prepara a busca. O % do LIKE quer dizer "qualquer coisa antes ou depois".
//    No CPF a busca usa só os números, para achar com ou sem pontuação.
$texto = '%' . $busca . '%';
$cpfBusca = '%' . str_replace(['.', '-'], '', $busca) . '%';

// 5. Prepara e executa a consulta. Só os ativos: os desativados somem da lista.
$sql = 'SELECT id_funcionario, nome, sexo, cpf, usuario, cargo, departamento, tipo_acesso
        FROM funcionarios
        WHERE status = ?
          AND (nome LIKE ? OR cargo LIKE ? OR departamento LIKE ? OR cpf LIKE ?)
        ORDER BY nome';
$stmt = $pdo->prepare($sql);
$stmt->execute(['ativo', $texto, $texto, $texto, $cpfBusca]);

// 6. Prepara cada funcionário do jeito que a tela vai mostrar.
//    id, sexo e usuario não aparecem na tabela: servem para o botão Editar
//    preencher o formulário. A senha nunca é enviada.
$funcionarios = [];
foreach ($stmt->fetchAll() as $linha) {
    $funcionarios[] = [
        'id' => $linha['id_funcionario'],
        'nome' => $linha['nome'],
        'sexo' => $linha['sexo'],
        'cpf' => formatarCpf($linha['cpf']),
        'usuario' => $linha['usuario'],
        'cargo' => $linha['cargo'],                 // pode vir null (vazio)
        'departamento' => $linha['departamento'],   // pode vir null (vazio)
        'tipo_acesso' => $linha['tipo_acesso'],
        'acesso' => nomeDoAcesso($linha['tipo_acesso']),
    ];
}

// 7. Responde para o JavaScript.
responder(['funcionarios' => $funcionarios]);
