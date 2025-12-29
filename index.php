<?php
// ARQUIVO: index.php
session_start();

// 1. Carrega configurações globais
require_once 'includes/db_connect.php';

// Verifica se a classe existe antes de tentar carregar, para evitar conflitos
if (!class_exists('LattesParser')) {
    require_once 'src/LattesParser.php';
}

// 2. Carrega o Header (Menu)
require_once 'includes/header.php';

// 3. Roteamento
$pagina = isset($_GET['p']) ? $_GET['p'] : 'home';

switch ($pagina) {
    case 'home':
        require_once 'home.php';
        break;

    case 'docentes':
        require_once 'docentes.php';
        break;

    case 'perfil':
        require_once 'perfil.php';
        break;

    case 'upload':
        require_once 'upload.php';
        break;

    case 'delete':
        require_once 'delete.php';
        break;

    default:
        echo "<div class='container mt-5 text-center'><h2>Erro 404</h2><p>Página não encontrada.</p></div>";
        break;
}

// 4. Carrega o Footer (Rodapé)
require_once 'includes/footer.php';
?>