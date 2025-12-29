<?php
// upload.php
$diretorioDestino = "data/xml/";

if (!is_dir($diretorioDestino)) {
    mkdir($diretorioDestino, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['xmlFile'])) {
    $arquivo = $_FILES['xmlFile'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    // Validação de Extensão
    if ($extensao != 'xml') {
        header("Location: index.php?status=error_type");
        exit;
    }

    // Validação de XML Válido (Evita arquivos corrompidos)
    libxml_use_internal_errors(true);
    if (!simplexml_load_file($arquivo['tmp_name'])) {
        header("Location: index.php?status=error_invalid");
        exit;
    }

    // Sanitização do nome (Sua lógica original)
    $nomeLimpo = preg_replace("/[^a-zA-Z0-9\.]/", "_", $arquivo['name']);
    $caminhoFinal = $diretorioDestino . $nomeLimpo;

    if (move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
        // Redireciona para o INDEX com status de sucesso
        header("Location: index.php?status=success_upload");
        exit;
    } else {
        header("Location: index.php?status=error_move");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
?>