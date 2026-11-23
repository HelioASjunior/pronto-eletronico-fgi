<?php
// Registrar ponto - PontoEletrônico
// Arquivo: PHP/ponto_registrar.php
// Método: POST. Usado pelo paginas/painel.html.
// Grava a data e a hora atuais e decide sozinho se é entrada ou saída.

// 1. Carrega a conexão e a sessão.
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/sessao.php';

// 2. Precisa estar logado, e só com POST.
exigirMetodo('POST');
exigirLogin();

$idFuncionario = $_SESSION['id_funcionario'];
$agora = date('Y-m-d H:i:s');

// 3. Prepara e executa a busca das marcações de hoje.
$sql = 'SELECT data_hora
        FROM registros_ponto
        WHERE id_funcionario = ? AND data_hora >= ? AND data_hora < ?
        ORDER BY data_hora';
$stmt = $pdo->prepare($sql);
$stmt->execute([$idFuncionario, date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day'))]);
$hoje = $stmt->fetchAll();

// 4. Evita registro duplicado (clique duplo): espera 1 minuto entre registros.
if (count($hoje) > 0) {
    $ultimo = $hoje[count($hoje) - 1];
    if (time() - strtotime($ultimo['data_hora']) < 60) {
        responder(['erro' => 'Você registrou o ponto há menos de 1 minuto. Aguarde para registrar de novo.'], 429);
    }
}

// 5. Alterna sozinho: número par de marcações = entrada; ímpar = saída.
if (count($hoje) % 2 === 0) {
    $tipo = 'entrada';
    $nomeTipo = 'Entrada';
} else {
    $tipo = 'saida';
    $nomeTipo = 'Saída';
}

// 6. Prepara e executa o registro.
$sql = 'INSERT INTO registros_ponto (id_funcionario, data_hora, tipo) VALUES (?, ?, ?)';
$stmt = $pdo->prepare($sql);
$stmt->execute([$idFuncionario, $agora, $tipo]);

// 7. Responde para o JavaScript.
responder(['mensagem' => "$nomeTipo registrada às " . date('H:i', strtotime($agora)) . '.']);
