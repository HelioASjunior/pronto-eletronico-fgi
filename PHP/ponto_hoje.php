<?php
// Pontos de hoje e últimos registros - PontoEletrônico
// Arquivo: PHP/ponto_hoje.php
// Método: GET. Usado pelo paginas/painel.html.

// 1. Carrega a conexão e a sessão.
require __DIR__ . '/conexao.php';
require __DIR__ . '/sessao.php';

// 2. Precisa estar logado, e só com GET.
exigirMetodo('GET');
exigirLogin();

$idFuncionario = $_SESSION['id_funcionario'];

// 3. Período de hoje: de 00:00 de hoje até 00:00 de amanhã.
$inicio = date('Y-m-d 00:00:00');
$fim = date('Y-m-d 00:00:00', strtotime('+1 day'));

// 4. Prepara e executa a busca das marcações de hoje.
$sql = 'SELECT tipo, data_hora
        FROM registros_ponto
        WHERE id_funcionario = ? AND data_hora >= ? AND data_hora < ?
        ORDER BY data_hora';
$stmt = $pdo->prepare($sql);
$stmt->execute([$idFuncionario, $inicio, $fim]);

$hoje = [];
foreach ($stmt->fetchAll() as $registro) {
    $hoje[] = [
        'tipo' => $registro['tipo'],
        'hora' => date('H:i', strtotime($registro['data_hora'])),
    ];
}

// 5. Próximo registro: número par de marcações = entrada; ímpar = saída.
if (count($hoje) % 2 === 0) {
    $proximoTipo = 'entrada';
} else {
    $proximoTipo = 'saida';
}

// 6. Prepara e executa a busca dos últimos 5 registros (de todos os dias).
$sql = 'SELECT data_hora, tipo, observacao
        FROM registros_ponto
        WHERE id_funcionario = ?
        ORDER BY data_hora DESC
        LIMIT 5';
$stmt = $pdo->prepare($sql);
$stmt->execute([$idFuncionario]);

$ultimos = [];
foreach ($stmt->fetchAll() as $registro) {
    $ultimos[] = [
        'data' => date('d/m/Y', strtotime($registro['data_hora'])),
        'hora' => date('H:i', strtotime($registro['data_hora'])),
        'tipo' => $registro['tipo'],
        'observacao' => $registro['observacao'],
    ];
}

// 7. Responde para o JavaScript.
responder([
    'proximo_tipo' => $proximoTipo,
    'hoje' => $hoje,
    'ultimos' => $ultimos,
]);
