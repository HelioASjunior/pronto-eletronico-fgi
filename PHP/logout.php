<?php
// Sair - PontoEletrônico
// Arquivo: PHP/logout.php

// 1. Carrega a sessão.
require __DIR__ . '/sessao.php';

// 2. Apaga o login.
$_SESSION = [];
session_destroy();

// 3. Volta para a tela de login.
header('Location: ../index.html');
exit;
