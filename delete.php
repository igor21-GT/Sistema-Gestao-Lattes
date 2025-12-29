<?php
// delete.php
if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // basename evita invasão de pastas
    $filepath = 'data/xml/' . $file;

    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            // Sucesso
            header("Location: index.php?status=deleted");
        } else {
            // Erro de permissão
            header("Location: index.php?status=error");
        }
    } else {
        // Arquivo não existe
        header("Location: index.php?status=notfound");
    }
} else {
    header("Location: index.php");
}
?>