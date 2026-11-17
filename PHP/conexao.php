<?php
// Conexão com o banco de dados - PontoEletrônico
// Arquivo: PHP/conexao.php
// Os outros arquivos usam assim: require __DIR__ . '/conexao.php';
// Depois disso a variável $pdo já está pronta para as consultas.

// 1. Dados de acesso ao banco (padrão do XAMPP: usuário root e senha vazia).
$host = 'localhost';
$banco = 'ponto_eletronico';
$usuarioBanco = 'root';
$senhaBanco = '';

// 2. Horário de Brasília (o PHP do XAMPP vem com o fuso da Europa).
date_default_timezone_set('America/Sao_Paulo');

// 3. Conecta com o PDO. O utf8mb4 grava os acentos (ç, ã, é) certos.
try {
    $pdo = new PDO("mysql:host=$host;dbname=$banco;charset=utf8mb4", $usuarioBanco, $senhaBanco);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);       // erro vira exceção
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);  // $linha['nome']
} catch (PDOException $erro) {
    // 4. Se não conectar (MySQL desligado), avisa o JavaScript com erro 500.
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Erro ao conectar com o banco de dados. Confira se o MySQL do XAMPP está ligado.'], JSON_UNESCAPED_UNICODE);
    exit;
}
