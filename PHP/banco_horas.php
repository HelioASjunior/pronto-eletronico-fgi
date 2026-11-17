<?php
// Banco de horas - PontoEletrônico
// Arquivo: PHP/banco_horas.php
// Método: GET. Só administrador. Usado pelo paginas/banco_horas.html.
// Filtros na URL: banco_horas.php?mes=2026-11&busca=maria
//
// Regras:
// - Horas previstas: 8h por dia útil (segunda a sexta), contando só os dias
//   já encerrados (até ontem). Para quem foi cadastrado no meio do mês,
//   conta a partir do cadastro.
// - Horas trabalhadas: soma de cada par entrada -> saída (sábado e domingo
//   também contam e viram horas a mais).
// - Saldo = horas trabalhadas - horas previstas.

// 1. Carrega a conexão e a sessão.
require __DIR__ . '/conexao.php';
require __DIR__ . '/sessao.php';

// 2. Só o administrador pode ver, e só com GET.
exigirMetodo('GET');
exigirAdministrador();

// 3. Jornada da empresa. Para mudar, basta trocar aqui.
$jornadaDiaria = 8 * 60 * 60;          // 8 horas em segundos
$diasUteis = [1, 2, 3, 4, 5];          // 1 = segunda ... 5 = sexta (date('N'))

// 4. Recebe os filtros. Mês vazio ou errado = mês atual.
$mes = $_GET['mes'] ?? '';
if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
    $mes = date('Y-m');
}
$busca = trim($_GET['busca'] ?? '');

// 5. Período: do dia 1 até o último dia do mês, mas nunca depois de ontem.
$primeiroDia = $mes . '-01';
$ultimoDia = date('Y-m-t', strtotime($primeiroDia));     // 't' = último dia do mês
$ontem = date('Y-m-d', strtotime('-1 day'));
if ($ultimoDia > $ontem) {
    $ultimoDia = $ontem;
}

// 6. Prepara e executa a busca dos funcionários ativos (os desativados não entram).
$sql = 'SELECT id_funcionario, nome, departamento, criado_em
        FROM funcionarios
        WHERE status = ? AND (nome LIKE ? OR departamento LIKE ?)
        ORDER BY nome';
$stmt = $pdo->prepare($sql);
$stmt->execute(['ativo', '%' . $busca . '%', '%' . $busca . '%']);
$funcionarios = $stmt->fetchAll();

// 7. Prepara e executa a busca dos pontos do período (de todos de uma vez)
//    e separa por funcionário e por dia: $registros[id]['2026-11-10'] = [...]
$registros = [];
if ($primeiroDia <= $ultimoDia) {
    $sql = 'SELECT id_funcionario, data_hora
            FROM registros_ponto
            WHERE data_hora >= ? AND data_hora < ?
            ORDER BY data_hora';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$primeiroDia . ' 00:00:00', date('Y-m-d 00:00:00', strtotime($ultimoDia . ' +1 day'))]);

    foreach ($stmt->fetchAll() as $registro) {
        $dia = date('Y-m-d', strtotime($registro['data_hora']));
        $registros[$registro['id_funcionario']][$dia][] = $registro;
    }
}

// 8. Calcula o saldo de cada funcionário.
$saldos = [];
$totalPositivo = 0;
$totalNegativo = 0;

foreach ($funcionarios as $funcionario) {
    $id = $funcionario['id_funcionario'];
    $diasDoFuncionario = $registros[$id] ?? [];

    // Dias previstos: dias úteis desde o dia 1 (ou desde o cadastro) até o fim do período
    $inicio = max($primeiroDia, date('Y-m-d', strtotime($funcionario['criado_em'])));
    $diasPrevistos = 0;
    for ($dia = $inicio; $dia <= $ultimoDia; $dia = date('Y-m-d', strtotime($dia . ' +1 day'))) {
        if (in_array((int) date('N', strtotime($dia)), $diasUteis)) {
            $diasPrevistos++;
        }
    }

    // Horas trabalhadas: soma de cada par (saída - entrada) de cada dia
    $segundosTrabalhados = 0;
    foreach ($diasDoFuncionario as $marcacoes) {
        for ($i = 0; $i + 1 < count($marcacoes); $i += 2) {
            $segundosTrabalhados += strtotime($marcacoes[$i + 1]['data_hora']) - strtotime($marcacoes[$i]['data_hora']);
        }
    }

    $segundosPrevistos = $diasPrevistos * $jornadaDiaria;
    $saldo = $segundosTrabalhados - $segundosPrevistos;

    if ($saldo > 0) {
        $totalPositivo += $saldo;
    } else {
        $totalNegativo += $saldo;
    }

    $saldos[] = [
        'nome' => $funcionario['nome'],
        'departamento' => $funcionario['departamento'],
        'dias_trabalhados' => count($diasDoFuncionario),
        'dias_previstos' => $diasPrevistos,
        'horas_previstas' => formatarHoras($segundosPrevistos),
        'horas_trabalhadas' => formatarHoras($segundosTrabalhados),
        'saldo' => formatarSaldo($saldo),
        'saldo_segundos' => $saldo,         // o JavaScript usa para escolher a cor
    ];
}

// Saldo com sinal: 9000 -> '+02:30', -4500 -> '-01:15', 0 -> '00:00'
function formatarSaldo($segundos)
{
    if ($segundos > 0) {
        return '+' . formatarHoras($segundos);
    }
    if ($segundos < 0) {
        return '-' . formatarHoras(abs($segundos));
    }
    return '00:00';
}

// 9. Até que dia foi contado (null = nenhum dia do mês terminou ainda).
$ate = null;
if ($primeiroDia <= $ultimoDia) {
    $ate = date('d/m/Y', strtotime($ultimoDia));
}

// 10. Responde para o JavaScript.
responder([
    'mes' => $mes,
    'ate' => $ate,
    'total_positivo' => formatarSaldo($totalPositivo),
    'total_negativo' => formatarSaldo($totalNegativo),
    'saldo_geral' => formatarSaldo($totalPositivo + $totalNegativo),
    'saldo_geral_segundos' => $totalPositivo + $totalNegativo,
    'funcionarios' => $saldos,
]);
