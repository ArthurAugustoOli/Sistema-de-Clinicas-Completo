<?php
// Arquivo: public_dashboard.php

// Permitir o uso de iframes
header("X-Frame-Options: ALLOWALL");
header("Content-Security-Policy: frame-ancestors 'self' https://gamma.app");
header("Access-Control-Allow-Origin: *");

// Incluir apenas os dados que você quer mostrar no Gamma
require_once 'config/config.php';
require_once 'functions/utils/helpers.php';

// Dados básicos que você quer mostrar
echo "<h1>Painel de Controle Público</h1>";
echo "<p>Este é um exemplo de painel público visível pelo Gamma.</p>";
