<?php
// ARQUIVO: perfil.php

if (!isset($_GET['file'])) {
    echo "<script>window.location.href='index.php?p=docentes';</script>";
    exit;
}

$nomeArquivo = basename($_GET['file']);
$caminhoArquivo = "data/xml/" . $nomeArquivo;

try {
    if (!class_exists('LattesParser')) { require_once 'src/LattesParser.php'; }
    $parser = new LattesParser($caminhoArquivo);
    $perfil = $parser->getDadosGerais();
    
    // 1. DADOS DE PRODUÇÃO
    $artigos = $parser->getArtigos();
    $livros = $parser->getLivros();
    $eventos = $parser->getTrabalhosEventos();
    $producaoTotal = array_merge($artigos, $livros, $eventos);
    usort($producaoTotal, function($a, $b) { return $b['ano'] - $a['ano']; });

    // 2. OUTROS DADOS
    $capitulos = $parser->getCapitulos();
    $orientacoes = $parser->getOrientacoes();
    $tecnica = $parser->getProducaoTecnica();

    $graficoDados = $parser->getEstatisticasGrafico($producaoTotal);
    $listaFormacao = $parser->getFormacao();
    $ultimaFormacaoNome = !empty($listaFormacao) ? $listaFormacao[0]['curso'] : "Não informado";

} catch (Exception $e) {
    echo "<div class='alert alert-danger text-center mt-5'>Erro: " . $e->getMessage() . "</div>";
    return;
}

$jsonAnos = json_encode($graficoDados['labels']); 
$jsonQtd = json_encode($graficoDados['data']);
?>

<style>
    /* DESIGN GERAL */
    .sidebar { background: linear-gradient(180deg, #6C5DD3 0%, #4D49BD 100%); }
    .nav-link-perfil { color: rgba(255,255,255,0.7); text-decoration: none; display: block; } 
    .nav-link-perfil.active { background: rgba(255,255,255,0.1); border-right: 4px solid #fff; color: white; }
    
    /* CARDS */
    .card-custom { 
        border: none; border-radius: 16px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
        transition: transform 0.2s; margin-bottom: 25px; background: #fff;
    }
    
    .timeline-container { padding-left: 20px; border-left: 2px solid #e9ecef; margin-left: 10px; }
    .timeline-item { position: relative; margin-bottom: 25px; }
    .timeline-marker { position: absolute; left: -26px; top: 5px; width: 14px; height: 14px; border-radius: 50%; background: #6C5DD3; border: 3px solid #fff; box-shadow: 0 0 0 2px #e9ecef; }

    .transition-hover { transition: all 0.2s ease; border-left: 3px solid transparent; }
    .transition-hover:hover { background-color: #f8f9fa; border-left: 3px solid #6C5DD3; transform: translateX(5px); }
    .bg-soft-primary { background-color: rgba(108, 93, 211, 0.1) !important; color: #6C5DD3 !important; }
    
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    /* --- NOVOS FILTROS TIPO DROPDOWN (IGUAL SUA IMAGEM) --- */
    .filter-container {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .form-select-custom {
        border: 1px solid #d1d3e2; /* Borda suave cinza */
        border-radius: 8px;        /* Cantos arredondados */
        padding: 8px 12px;
        color: #495057;
        font-size: 0.9rem;
        background-color: #fff;
        cursor: pointer;
        min-width: 180px;          /* Largura mínima para ficar bonito */
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }

    .form-select-custom:focus {
        border-color: #6C5DD3;
        box-shadow: 0 0 0 3px rgba(108, 93, 211, 0.1);
        outline: none;
    }

    /* BADGES DA TABELA */
    .badge-tipo { font-size: 0.7rem; text-transform: uppercase; margin-right: 5px; font-weight: 700; border: 1px solid transparent; }
    .tipo-Artigo { background-color: #e3f2fd; color: #1565c0; }
    .tipo-Livro { background-color: #fff3e0; color: #ef6c00; }
    .tipo-Capítulo { background-color: #ffe0b2; color: #e65100; }
    .tipo-TrabalhoCompleto { background-color: #e8f5e9; color: #2e7d32; }
    .tipo-ResumoExpandido { background-color: #f3e5f5; color: #7b1fa2; }
    .tipo-Resumo, .tipo-Evento { background-color: #f5f5f5; color: #616161; }

    .search-input-group { position: relative; min-width: 250px; flex-grow: 1; }
    .search-input-group input { padding-left: 35px; border-radius: 8px; font-size: 0.9rem; border: 1px solid #d1d3e2; background: #fff; height: 38px; }
    .search-input-group input:focus { box-shadow: 0 0 0 3px rgba(108, 93, 211, 0.1); border-color: #6C5DD3; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0a0a0; font-size: 0.9rem; }
    
    .section-title { font-size: 1.1rem; font-weight: 700; color: #333; margin-bottom: 0; }
</style>

<div class="container-fluid">
    <div class="row">
        
        <nav class="col-md-2 d-none d-md-block sidebar position-fixed" style="top:0; bottom:0; left:0; z-index:100; padding-top: 60px;">
            <div class="text-center mb-4 pt-3">
                <h4 class="text-white mt-2">DOCENTES</h4>
            </div>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link-perfil" href="index.php?p=docentes"><i class="fas fa-arrow-left me-2"></i> Voltar à Lista</a></li>
            </ul>
        </nav>

        <main class="col-md-10 ms-sm-auto px-md-4 py-4">
            
            <div class="card-custom mb-4 bg-white overflow-hidden">
                <div style="height: 100px; background: linear-gradient(90deg, #6C5DD3 0%, #8B78E6 100%);"></div>
                <div class="p-4 d-flex flex-row align-items-end flex-wrap" style="margin-top: -60px;">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($perfil['nome']); ?>&background=random&size=150" 
                         class="rounded-circle shadow me-4" width="120" height="120" style="border: 5px solid #fff; background-color: #fff;">
                    <div class="flex-grow-1 pb-2">
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $perfil['nome']; ?></h2>
                        <p class="text-primary fw-bold mb-1"><?php echo $perfil['titulacao']; ?></p>
                        <small class="text-muted"><i class="fas fa-university me-1"></i> <?php echo $perfil['instituicao']; ?></small>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <div class="p-3 bg-light rounded-3 border-start border-4 border-primary">
                        <p class="text-muted small fst-italic mb-0"><strong>Como citar:</strong> <?php echo $perfil['citacao']; ?></p>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card-custom p-4 d-flex align-items-center h-100 bg-white">
                        <div class="me-3 bg-soft-primary rounded-circle p-3">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1 small text-uppercase fw-bold">Titulação Máxima</h5>
                            <h3 class="fw-bold mb-0 text-dark"><?php echo $perfil['titulacao']; ?></h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card-custom p-4 d-flex align-items-center h-100 bg-white">
                        <div class="me-3 bg-light rounded-circle p-3 text-success">
                            <i class="fas fa-book-reader fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="text-muted mb-1 small text-uppercase fw-bold">Última Formação</h5>
                            <h4 class="fw-bold mb-0 text-success" style="font-size: 1.2rem;">
                                <?php echo mb_strimwidth($ultimaFormacaoNome, 0, 40, "..."); ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7">
                    
                    <div class="card-custom bg-white p-4 mb-4">
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center">
                                <h5 class="mb-0 fw-bold me-3"><i class="fas fa-book me-2 text-primary"></i> Produção Bibliográfica</h5>
                                <span class="badge bg-primary rounded-pill px-3 py-1 fs-7" id="totalProducaoBadge"><?php echo count($producaoTotal); ?></span>
                            </div>
                        </div>
                        
                        <div class="filter-container">
                            <select id="filterType" class="form-select form-select-custom" onchange="applyFilters()">
                                <option value="todos">Todos os Tipos</option>
                                <option value="Artigo">Artigos</option>
                                <option value="Livro">Livros</option>
                                <option value="Trabalho Completo">Trabalhos Completos</option>
                                <option value="Resumo Expandido">Resumos Expandidos</option>
                            </select>

                            <select id="filterYear" class="form-select form-select-custom" onchange="applyFilters()">
                                <option value="todos">Todos os Anos</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="outros">Anteriores</option>
                            </select>

                            <div class="search-input-group">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="searchInput" class="form-control" placeholder="Buscar título..." onkeyup="applyFilters()">
                            </div>
                        </div>
                        
                        <div class="table-responsive custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-borderless align-middle">
                                <tbody>
                                    <?php if(empty($producaoTotal)): ?>
                                        <tr><td class="text-center py-5 text-muted">Nenhuma produção encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($producaoTotal as $item): ?>
                                        <tr class="transition-hover border-bottom item-producao" 
                                            data-tipo="<?php echo $item['tipo']; ?>" data-ano="<?php echo $item['ano']; ?>" data-titulo="<?php echo strtolower($item['titulo']); ?>">
                                            <td style="width: 70px;" class="text-center"><span class="badge bg-soft-primary rounded-pill"><?php echo $item['ano']; ?></span></td>
                                            <td>
                                                <span class="badge badge-tipo tipo-<?php echo str_replace(' ', '', $item['tipo']); ?>"><?php echo $item['tipo']; ?></span>
                                                <span class="fw-bold text-dark d-block mb-1 mt-1"><?php echo $item['titulo']; ?></span>
                                                <small class="text-muted d-block text-truncate" style="max-width: 450px;"><i class="far fa-bookmark me-1"></i> <?php echo !empty($item['veiculo']) ? $item['veiculo'] : 'N/A'; ?></small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <div id="msgSemResultados" class="text-center py-4 text-muted d-none">
                                <i class="fas fa-search fa-2x mb-2 opacity-25"></i><br>Nenhuma publicação encontrada.
                            </div>
                        </div>
                    </div>

                    <?php if(!empty($capitulos)): ?>
                    <div class="card-custom bg-white p-4">
                        <div class="d-flex align-items-center mb-3">
                            <h5 class="section-title"><i class="fas fa-bookmark me-2 text-primary"></i> Capítulos de Livros</h5>
                            <span class="badge bg-light text-dark ms-2 border"><?php echo count($capitulos); ?></span>
                        </div>
                        <div class="table-responsive custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-borderless align-middle">
                                <tbody>
                                    <?php foreach($capitulos as $cap): ?>
                                    <tr class="border-bottom">
                                        <td style="width: 70px;" class="text-center"><span class="badge bg-warning text-dark bg-opacity-25 rounded-pill"><?php echo $cap['ano']; ?></span></td>
                                        <td>
                                            <span class="fw-bold text-dark d-block"><?php echo $cap['titulo']; ?></span>
                                            <small class="text-muted"><i class="fas fa-book-open me-1"></i> <?php echo $cap['veiculo']; ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($orientacoes)): ?>
                    <div class="card-custom bg-white p-4">
                        <div class="d-flex align-items-center mb-3">
                            <h5 class="section-title"><i class="fas fa-users me-2 text-primary"></i> Orientações Concluídas</h5>
                            <span class="badge bg-light text-dark ms-2 border"><?php echo count($orientacoes); ?></span>
                        </div>
                        <ul class="list-group list-group-flush custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            <?php foreach($orientacoes as $ori): ?>
                            <li class="list-group-item px-0 py-3 border-bottom-0 border-top">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-secondary mb-1" style="font-size:0.7rem"><?php echo $ori['tipo']; ?></span>
                                        <span class="d-block fw-bold text-dark"><?php echo $ori['titulo']; ?></span>
                                    </div>
                                    <span class="badge bg-light text-dark border ms-2 align-self-start"><?php echo $ori['ano']; ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($tecnica)): ?>
                    <div class="card-custom bg-white p-4">
                        <div class="d-flex align-items-center mb-3">
                            <h5 class="section-title"><i class="fas fa-cogs me-2 text-primary"></i> Produção Técnica</h5>
                            <span class="badge bg-light text-dark ms-2 border"><?php echo count($tecnica); ?></span>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php foreach($tecnica as $tec): ?>
                            <li class="list-group-item px-0 py-3 border-bottom-0 border-top">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-info text-dark bg-opacity-25 mb-1" style="font-size:0.7rem"><?php echo $tec['tipo']; ?></span>
                                        <span class="d-block fw-bold text-dark"><?php echo $tec['titulo']; ?></span>
                                    </div>
                                    <span class="fw-bold text-secondary ms-2"><?php echo $tec['ano']; ?></span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                </div>

                <div class="col-lg-5">
                    <div class="card-custom bg-primary text-white p-4"> 
                        <h5 class="mb-3 fw-bold"><i class="fas fa-info-circle me-2"></i> Resumo</h5>
                        <p class="small mb-0" style="text-align: justify; opacity: 0.9; line-height: 1.6;">
                            <?php echo substr(strip_tags($perfil['resumo']), 0, 400) . '...'; ?>
                        </p>
                    </div>

                    <div class="card-custom mb-4 bg-white p-4">
                        <h5 class="fw-bold mb-3">Produção Anual</h5>
                        <div style="height: 200px;"><canvas id="graficoProducao"></canvas></div>
                    </div>

                    <div class="card-custom mb-4 bg-white p-4">
                        <h5 class="fw-bold mb-3">Formação Acadêmica</h5>
                        <div class="timeline-container mt-2">
                            <?php foreach($listaFormacao as $form): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <span class="badge bg-light text-dark border mb-1"><?php echo $form['ano']; ?></span>
                                <h6 class="fw-bold mb-0 text-primary" style="font-size: 0.95rem;"><?php echo $form['tipo']; ?></h6>
                                <p class="text-muted small mb-0"><?php echo $form['curso']; ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div> 
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('graficoProducao').getContext('2d');
    new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: <?php echo $jsonAnos; ?>,
            datasets: [{ label: 'Produções', data: <?php echo $jsonQtd; ?>, backgroundColor: '#6C5DD3', borderRadius: 4 }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });

    function applyFilters() {
        const rows = document.querySelectorAll('.item-producao');
        
        // Pega valores dos SELECTS
        const selectedType = document.getElementById('filterType').value;
        const selectedYear = document.getElementById('filterYear').value;
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        
        let visiveis = 0;
        
        rows.forEach(row => {
            const rowType = row.getAttribute('data-tipo');
            const rowYear = parseInt(row.getAttribute('data-ano'));
            const rowTitle = row.getAttribute('data-titulo');
            
            // Lógica Tipo
            const matchType = selectedType === 'todos' || rowType === selectedType;
            
            // Lógica Ano
            let matchYear = false;
            if (selectedYear === 'todos') matchYear = true;
            else if (selectedYear === 'outros') matchYear = rowYear < 2023;
            else matchYear = rowYear == parseInt(selectedYear);
            
            // Lógica Busca
            const matchText = rowTitle.includes(searchTerm);
            
            if (matchType && matchYear && matchText) { 
                row.style.display = ''; 
                visiveis++; 
            } else { 
                row.style.display = 'none'; 
            }
        });
        
        // Atualiza badge de total
        document.getElementById('totalProducaoBadge').innerText = visiveis;

        const msg = document.getElementById('msgSemResultados');
        if (visiveis === 0) msg.classList.remove('d-none'); else msg.classList.add('d-none');
    }
</script>