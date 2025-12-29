<?php
// ARQUIVO: docentes.php
// Sem includes de header/footer (index.php já cuida disso)

// Lógica de leitura de XML
$diretorio = "data/xml/";
$arquivos = glob($diretorio . "*.xml");
$docentes = [];

foreach ($arquivos as $arquivo) {
    try {
        if (file_exists($arquivo)) {
            $parser = new LattesParser($arquivo);
            $dados = $parser->getDadosGerais();
            
            $docentes[] = [
                'arquivo' => basename($arquivo),
                'nome' => $dados['nome'],
                'area' => $dados['area_curso'],
                'instituicao' => $dados['instituicao']
            ];
        }
    } catch (Exception $e) { continue; }
}

usort($docentes, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});
?>

<style>
    /* Estilos específicos desta página */
    .sidebar { background: linear-gradient(180deg, #6C5DD3 0%, #4D49BD 100%); min-height: 100vh; }
    .nav-link-docente { color: rgba(255,255,255,0.7); padding: 15px 20px; transition: 0.3s; display: flex; align-items: center; text-decoration: none; }
    .nav-link-docente:hover { color: #fff; background: rgba(255,255,255,0.1); }
    .nav-link-docente.active { background: rgba(255,255,255,0.1); border-right: 4px solid #fff; color: #fff; }
    .nav-link-docente i { width: 25px; text-align: center; margin-right: 10px; }

    .card-docente {
        border: none; border-radius: 16px; background: #fff; transition: all 0.3s ease;
        position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    }
    .card-docente:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(108, 93, 211, 0.15); }
    .card-header-img { height: 80px; background: linear-gradient(45deg, #e0e0e0 0%, #f5f5f5 100%); }
    .avatar-container { margin-top: -40px; text-align: center; }
    .avatar-img { width: 80px; height: 80px; border: 4px solid #fff; background: #fff; object-fit: cover; }
    
    .btn-perfil { background-color: #6C5DD3; color: #fff; border-radius: 50px; padding: 8px 24px; font-size: 0.9rem; transition: 0.2s; text-decoration: none;}
    .btn-perfil:hover { background-color: #5b4ec2; color: #fff; }
</style>

<div class="container-fluid">
    <div class="row">
        
        <nav class="col-md-2 d-none d-md-block sidebar position-fixed" style="top:0; bottom:0; left:0; z-index: 100; padding-top: 60px;"> 
            <div class="text-center mb-4 pt-3">
                <h4 class="text-white mt-2">SISTEMA</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link-docente" href="index.php?p=home">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link-docente active" href="#">
                        <i class="fas fa-users"></i> Lista de Docentes
                    </a>
                </li>
            </ul>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Docentes Cadastrados</h2>
                    <p class="text-muted mb-0">Gerencie e visualize os currículos Lattes.</p>
                </div>
                
                <div class="position-relative" style="width: 300px;">
                    <input type="text" id="searchInput" class="form-control border-0 shadow-sm py-2 ps-4" 
                           placeholder="Buscar docente..." style="border-radius: 30px;" onkeyup="filtrarDocentes()">
                    <i class="fas fa-search text-muted position-absolute" style="right: 15px; top: 10px;"></i>
                </div>
            </div>

            <div class="row g-4" id="gridDocentes">
                <?php if(empty($docentes)): ?>
                    <div class="col-12 text-center py-5">
                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="100" style="opacity:0.5">
                        <h4 class="text-muted mt-3">Nenhum arquivo XML encontrado.</h4>
                        <p class="text-muted small">Adicione arquivos .xml na pasta <code>data/xml/</code></p>
                    </div>
                <?php else: ?>
                    
                    <?php foreach($docentes as $doc): ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 card-item">
                        <div class="card-docente h-100 pb-3">
                            <div class="card-header-img" style="background: <?php echo 'hsl('.rand(0,360).', 70%, 85%)'; ?>"></div>
                            
                            <div class="avatar-container">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($doc['nome']); ?>&background=random&size=128" 
                                     class="rounded-circle shadow-sm avatar-img" alt="Avatar">
                            </div>
                            
                            <div class="card-body text-center pt-2">
                                <h5 class="fw-bold text-dark mb-1 nome-docente text-truncate px-2" title="<?php echo $doc['nome']; ?>">
                                    <?php echo $doc['nome']; ?>
                                </h5>
                                <p class="small text-muted mb-3 text-truncate">
                                    <?php echo $doc['area']; ?>
                                </p>
                                
                                <a href="index.php?p=perfil&file=<?php echo $doc['arquivo']; ?>" class="btn btn-perfil shadow-sm">
                                    Ver Perfil <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
    function filtrarDocentes() {
        let input = document.getElementById('searchInput');
        let filter = input.value.toUpperCase();
        let grid = document.getElementById('gridDocentes');
        let cards = grid.getElementsByClassName('card-item');

        for (let i = 0; i < cards.length; i++) {
            let nome = cards[i].getElementsByClassName('nome-docente')[0];
            if (nome) {
                let txtValue = nome.textContent || nome.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }
    }
</script>