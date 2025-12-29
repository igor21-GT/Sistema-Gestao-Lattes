<?php
// home.php - Conteúdo do Dashboard
// (Header e LattesParser já carregados pelo index.php)

$pasta = 'data/xml/';
if (!is_dir($pasta)) mkdir($pasta, 0777, true);
$arquivos = array_diff(scandir($pasta), array('.', '..'));

// Arrays para popular os Selects
$filtrosUnidades = [];
$filtrosCursos = []; 
$filtrosAnos = [];
$masterData = []; 

$anoAtual = date("Y"); 

foreach($arquivos as $arquivo) {
    if(pathinfo($arquivo, PATHINFO_EXTENSION) == 'xml') {
        try {
            $path = $pasta . $arquivo;
            // Verifica novamente se a classe existe para segurança
            if (class_exists('LattesParser')) {
                $parser = new LattesParser($path);
                $dados = $parser->getDadosGerais();
                
                // Cálculos
                $fileTime = filemtime($path);
                $diasDesdeAtualizacao = (time() - $fileTime) / (60 * 60 * 24);
                $statusTorre = 5; 
                if ($diasDesdeAtualizacao <= 180) $statusTorre = 1;      
                elseif ($diasDesdeAtualizacao <= 365) $statusTorre = 2;  
                elseif ($diasDesdeAtualizacao <= 730) $statusTorre = 3;  
                elseif ($diasDesdeAtualizacao <= 1460) $statusTorre = 4; 
                
                $isRecente = $diasDesdeAtualizacao < 180;
                
                // Artigos (3 anos)
                $listaArtigos = $parser->getArtigos();
                $qtdArtigosTotal = count($listaArtigos);
                $qtdPublicacoes3Anos = 0;
                $anoLimite = $anoAtual - 3; 
                foreach($listaArtigos as $art) {
                    if($art['ano'] >= $anoLimite) $qtdPublicacoes3Anos++;
                }

                // Produção Detalhada
                if(method_exists($parser, 'getProducaoCompleta')) {
                    $prodDetalhada = $parser->getProducaoCompleta();
                } else {
                    $prodDetalhada = ['livros'=>0, 'trabalhos_anais'=>0, 'resumos_exp'=>0, 'resumos'=>0, 'apresentacoes'=>0, 'producao_tecnica'=>0];
                }
                $totalTrabalhosPub = $qtdArtigosTotal + $prodDetalhada['trabalhos_anais'];

                // Normalização
                $anoAtualizacao = substr($dados['data_atualizacao'], 4, 4); 
                if(empty($anoAtualizacao)) $anoAtualizacao = date("Y", $fileTime);

                $inst = trim($dados['instituicao']) ?: "Não Informada";
                $unidade = trim($dados['unidade']);
                $curso = trim($dados['area_curso']);
                $titulacao = mb_strtolower($dados['titulacao'] ?? '');

                $tipoTitulacao = 'Graduação'; 
                if(str_contains($titulacao, 'doutor')) $tipoTitulacao = 'Doutorado';
                elseif(str_contains($titulacao, 'mestre')) $tipoTitulacao = 'Mestrado';
                elseif(str_contains($titulacao, 'especialista')) $tipoTitulacao = 'Especialização';

                if($unidade) $filtrosUnidades[] = $unidade;
                if($curso) $filtrosCursos[] = $curso;
                if($anoAtualizacao) $filtrosAnos[] = $anoAtualizacao;

                $masterData[] = [
                    'file' => $arquivo,
                    'nome' => $dados['nome'],
                    'id_lattes' => pathinfo($arquivo, PATHINFO_FILENAME),
                    'inst' => $inst,
                    'unidade' => $unidade,
                    'curso' => $curso,
                    'ano' => $anoAtualizacao,
                    'titulacao_tipo' => $tipoTitulacao,
                    'qtd_pubs_recentes' => $qtdPublicacoes3Anos,
                    'status_torre' => $statusTorre,
                    'is_recente' => $isRecente, 
                    'data_formatada' => date("d/m/Y", $fileTime),
                    'status_class' => $isRecente ? 'success' : 'warning',
                    'status_label' => $isRecente ? 'Atualizado' : 'Antigo',
                    'detalhe_livros' => $prodDetalhada['livros'],
                    'detalhe_trabalhos' => $totalTrabalhosPub,
                    'detalhe_resumos_exp' => $prodDetalhada['resumos_exp'],
                    'detalhe_resumos' => $prodDetalhada['resumos'],
                    'detalhe_apresentacoes' => $prodDetalhada['apresentacoes'],
                    'detalhe_tecnica' => $prodDetalhada['producao_tecnica']
                ];
            }

        } catch (Exception $e) { continue; }
    }
}

$filtrosUnidades = array_unique($filtrosUnidades); sort($filtrosUnidades);
$filtrosCursos = array_unique($filtrosCursos); sort($filtrosCursos);
$filtrosAnos = array_unique($filtrosAnos); rsort($filtrosAnos);
?>

<style>
    /* Estilos mantidos, apenas removi html/body */
    .bg-gradient-blue { background: linear-gradient(45deg, #4099ff, #73b4ff); }      
    .bg-gradient-green { background: linear-gradient(45deg, #2ed8b6, #59e0c5); }     
    .bg-gradient-cyan { background: linear-gradient(45deg, #00bcd4, #4dd0e1); }      
    .bg-gradient-orange { background: linear-gradient(45deg, #FFB64D, #ffcb80); }    
    .bg-gradient-red { background: linear-gradient(45deg, #FF5370, #ff869a); }       
    
    .card-stat { border: none; border-radius: 15px; color: white; min-height: 120px; position: relative; overflow: hidden; transition: transform 0.2s; }
    .card-stat:hover { transform: translateY(-5px); }
    .card-stat-content { position: relative; z-index: 2; }
    .card-icon-bg { position: absolute; right: -10px; top: -10px; font-size: 80px; opacity: 0.2; transform: rotate(15deg); z-index: 1; color: #fff; }
    
    .card-dashboard { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .badge-soft-success { background-color: #d4edda; color: #155724; }
    .badge-soft-warning { background-color: #fff3cd; color: #856404; }
    .avatar-circle { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }

    .kpi-box { 
        background: #fff; border-radius: 15px; border: 1px solid #e3e6f0; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; padding: 25px; position: relative; 
    }
    .kpi-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; color: #4e73df; letter-spacing: 0.5px; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
    .big-number-block { text-align: center; padding: 5px; }
    .big-number { font-size: 3.5rem; font-weight: 700; color: #4e73df; line-height: 1; }
    .big-label { font-size: 0.9rem; color: #858796; margin-top: 5px; font-weight: 600; }
    .iqcd-number { color: #2e59d9; } 
    .score-blue { color: #2196F3; } 
    .question-title { font-size: 0.9rem; font-weight: 700; color: #333; margin-bottom: 15px; }

    .stat-list { border-left: 1px solid #e3e6f0; padding-left: 20px; }
    .stat-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; color: #5a5c69; }
    .stat-value { font-weight: 700; color: #333; }
    
    .legend-box { background-color: #f8f9fc; border-radius: 8px; padding: 20px; font-size: 0.8rem; color: #6e707e; height: 100%; border-left: 4px solid #4e73df; }
    .legend-title { font-weight: 700; color: #4e73df; margin-bottom: 8px; display: block; font-size: 0.9rem; }

    .kpi-divider { border-right: 2px solid #2196F3; padding-right: 30px; }
    @media (max-width: 768px) { .kpi-divider { border-right: none; border-bottom: 2px solid #2196F3; padding-bottom: 20px; margin-bottom: 20px; } }

    .filter-bar { background: #fff; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); margin-bottom: 25px; border-left: 5px solid #1cc88a; }
    .form-label-sm { font-size: 0.75rem; font-weight: 800; color: #858796; text-transform: uppercase; margin-bottom: 5px; display: block; }
    .form-select-custom { border: 1px solid #d1d3e2; border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; }
    .form-select-custom:focus { border-color: #1cc88a; box-shadow: 0 0 0 3px rgba(28, 200, 138, 0.1); }
    .sortable-header { cursor: pointer; user-select: none; }
    .sortable-header:hover { background-color: #f1f1f1; color: #4e73df; }
</style>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Monitoramento Acadêmico</h3>
            <p class="text-muted small">Gestão de currículos e indicadores de qualidade.</p>
        </div>
        <button class="btn btn-dark shadow px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-cloud-upload-alt me-2"></i> Importar XML
        </button>
    </div>

    <div class="row row-cols-1 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        <div class="col">
            <div class="card card-stat bg-gradient-blue">
                <div class="card-body card-stat-content">
                    <h5 class="mb-1">Total</h5>
                    <h2 class="mb-0 fw-bold" id="statTotalDocentes">0</h2>
                    <small>Docentes</small>
                </div>
                <i class="fas fa-users card-icon-bg"></i>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat bg-gradient-green">
                <div class="card-body card-stat-content">
                    <h5 class="mb-1">Doutores</h5>
                    <h2 class="mb-0 fw-bold" id="statTotalDoutores">0</h2>
                    <small>Titulação Máxima</small>
                </div>
                <i class="fas fa-graduation-cap card-icon-bg"></i>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat bg-gradient-cyan">
                <div class="card-body card-stat-content">
                    <h5 class="mb-1">Mestres</h5>
                    <h2 class="mb-0 fw-bold" id="statTotalMestres">0</h2>
                    <small>Titulação Média</small>
                </div>
                <i class="fas fa-user-graduate card-icon-bg"></i>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat bg-gradient-orange">
                <div class="card-body card-stat-content">
                    <h5 class="mb-1">Espec.</h5>
                    <h2 class="mb-0 fw-bold" id="statTotalEspecialistas">0</h2>
                    <small>Pós-Graduação</small>
                </div>
                <i class="fas fa-certificate card-icon-bg"></i>
            </div>
        </div>
        <div class="col">
            <div class="card card-stat bg-gradient-red">
                <div class="card-body card-stat-content">
                    <h5 class="mb-1">Graduados</h5>
                    <h2 class="mb-0 fw-bold" id="statTotalGraduados">0</h2>
                    <small>Nível Básico</small>
                </div>
                <i class="fas fa-user-tag card-icon-bg"></i>
            </div>
        </div>
    </div>

    <div class="filter-bar d-flex flex-wrap gap-3 align-items-end">
        <div class="flex-grow-1">
            <label class="form-label-sm"><i class="fas fa-search"></i> Buscar Nome</label>
            <input type="text" id="filtroTexto" class="form-control form-select-custom" placeholder="Digite para pesquisar...">
        </div>
        <div style="min-width: 200px;">
            <label class="form-label-sm"><i class="fas fa-book"></i> Área / Curso</label>
            <select id="filtroCurso" class="form-select form-select-custom">
                <option value="">Todos os Cursos</option>
                <?php foreach($filtrosCursos as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 200px;">
            <label class="form-label-sm"><i class="fas fa-building"></i> Unidade / Dept</label>
            <select id="filtroUnidade" class="form-select form-select-custom">
                <option value="">Todas as Unidades</option>
                <?php foreach($filtrosUnidades as $u): ?>
                    <option value="<?php echo $u; ?>"><?php echo $u; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 150px;">
            <label class="form-label-sm"><i class="fas fa-calendar-alt"></i> Ano Atualiz.</label>
            <select id="filtroAno" class="form-select form-select-custom">
                <option value="">Todos</option>
                <?php foreach($filtrosAnos as $a): ?>
                    <option value="<?php echo $a; ?>"><?php echo $a; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <button class="btn btn-outline-secondary btn-sm" onclick="limparFiltros()">
                <i class="fas fa-eraser"></i> Limpar
            </button>
            <button class="btn btn-success btn-sm ms-2" onclick="exportarExcel()">
                <i class="fas fa-file-excel"></i> Exportar
            </button>
        </div>
    </div>

    <div class="kpi-box">
        <h5 class="kpi-title"><i class="fas fa-chart-line me-2"></i> Indicador (KPI): Titulação <?php echo $anoAtual; ?></h5>
        <div class="row align-items-center">
            <div class="col-md-3 border-end">
                <div class="big-number-block mb-4">
                    <div class="big-number" id="kpiConceito">0</div>
                    <div class="big-label">Conceito Docente</div>
                </div>
                <hr style="width: 50%; margin: 0 auto 20px auto; border-color: #4e73df;">
                <div class="big-number-block">
                    <div class="big-number iqcd-number" id="kpiIQCD">0,00</div>
                    <div class="big-label">IQCD</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-list">
                    <h6 class="text-primary fw-bold small mb-3 text-uppercase">Números Absolutos</h6>
                    <div class="stat-item"><span>Doutores</span> <span class="stat-value" id="countDoutor">0</span></div>
                    <div class="stat-item"><span>Mestres</span> <span class="stat-value" id="countMestre">0</span></div>
                    <div class="stat-item"><span>Especialistas</span> <span class="stat-value" id="countEspecialista">0</span></div>
                    <div class="stat-item"><span>Graduados</span> <span class="stat-value" id="countGraduado">0</span></div>
                    <hr class="my-3">
                    <div class="row text-center">
                        <div class="col-4">
                            <h3 class="fw-bold mb-0 text-dark" id="percDoutor">0%</h3>
                            <small class="text-muted small fw-bold">Doutores</small>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold mb-0 text-dark" id="percMestre">0%</h3>
                            <small class="text-muted small fw-bold">Mestres</small>
                        </div>
                        <div class="col-4">
                            <h3 class="fw-bold mb-0 text-dark" id="percEspecialista">0%</h3>
                            <small class="text-muted small fw-bold">Espec.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="legend-box">
                    <span class="legend-title">Critérios de Análise</span>
                    <strong class="text-dark">Conceito Docente:</strong>
                    <ul class="list-unstyled mb-3 ps-1 mt-1 small">
                    <li><strong>1:</strong> O corpo docente é composto por menos de 25% de mestres e doutores.</li>
                        <li><strong>2:</strong> O corpo docente é composto por ao menos 25% de mestres e doutores</li>
                        <li><strong>3:</strong> O corpo docente é composto por ao menos 40% de mestres e doutores</li>
                        <li><strong>4:</strong> O corpo docente é composto por ao menos 60% de mestres e doutores</li>
                        <li><strong>5:</strong> O corpo docente é composto por ao menos 80% de mestres e doutores</li>
                    </ul>
                    <strong class="text-dark">IQCD (Meta > 3,0):</strong>
                    <p class="mb-0 text-justify small mt-1 text-muted">
                        Média ponderada: Doutor (5), Mestre (3), Especialista (2), Graduado (1).
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="kpi-box">
        <h5 class="kpi-title"><i class="fas fa-book me-2"></i> Indicador (KPI): Publicações <?php echo $anoAtual; ?></h5>
        <div class="row">
            
            <div class="col-md-6 kpi-divider">
                <div class="text-center mb-4">
                    <h6 class="text-muted fw-bold mb-1 text-uppercase small">Indicador 2.16</h6>
                    <div class="big-number score-blue" id="kpiPubScore" style="font-size: 4rem;">0</div>
                    <div class="big-label text-uppercase text-primary" style="font-size: 0.8rem; letter-spacing: 1px;">Pontuação Publicações</div>
                </div>

                <div class="px-3 mt-4">
                    <p class="question-title">28. Quantas publicações você possui nos últimos 03 anos?</p>
                    <div style="height: 220px;">
                        <canvas id="chartPublicacoes"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="ps-md-4 pt-2">
                    <span class="legend-title fs-6 mb-3 border-bottom pb-2">Critérios de Análise (Legenda)</span>
                    
                    <strong class="text-dark d-block mb-3 small">Conceito Produção Científica...</strong>
                    
                    <ul class="list-unstyled ps-0 small text-secondary" style="line-height: 1.8;">
                        <li class="mb-2"><strong class="text-dark">1:</strong> Mais de 50% dos docentes não possuem produção nos últimos 3 anos.</li>
                        <li class="mb-2"><strong class="text-dark">2:</strong> Pelo menos 50% dos docentes possuem, no mínimo, 1 produção.</li>
                        <li class="mb-2"><strong class="text-dark">3:</strong> Pelo menos 50% dos docentes possuem, no mínimo, 4 produções.</li>
                        <li class="mb-2"><strong class="text-dark">4:</strong> Pelo menos 50% dos docentes possuem, no mínimo, 7 produções.</li>
                        <li class="mb-2"><strong class="text-dark">5:</strong> Pelo menos 50% dos docentes possuem, no mínimo, 9 produções.</li>
                    </ul>

                    <div class="mt-4 pt-3 border-top d-flex align-items-center">
                        <div class="me-3 bg-light rounded-circle p-2 text-primary"><i class="fas fa-flag"></i></div>
                        <div>
                            <strong class="text-dark d-block small">Participação no ConCIFA</strong>
                            <span class="small text-muted">Meta Institucional: 35%</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="card card-dashboard mb-4">
        <div class="card-header bg-white border-0 pt-3">
            <h5 class="fw-bold text-dark"><i class="fas fa-chart-bar me-2 text-primary"></i> Detalhamento da Produção (Acumulado)</h5>
        </div>
        <div class="card-body">
            <div style="height: 350px;">
                <canvas id="chartProducaoDetalhada"></canvas>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card card-dashboard h-100">
                <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold text-secondary">Titulação (Gráfico)</h6></div>
                <div class="card-body"><div style="height: 200px;"><canvas id="chartTitulacao"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-dashboard h-100">
                <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold text-secondary">Status Base (Temporal)</h6></div>
                <div class="card-body"><div style="height: 200px;"><canvas id="chartStatus"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-dashboard h-100">
                <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold text-secondary">Top Instituições</h6></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush mt-2" id="listaTopInstituicoes"></ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-dashboard mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-hover" id="mainTable">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="ps-4 sortable-header" onclick="sortColumn('nome')">
                                Docente <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable-header" onclick="sortColumn('curso')">
                                Curso / Unidade <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="text-center sortable-header" onclick="sortColumn('qtd_pubs_recentes')">
                                Pubs (3 Anos) <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="sortable-header" onclick="sortColumn('data_formatada')">
                                Status <i class="fas fa-sort sort-icon"></i>
                            </th>
                            <th class="text-end pe-4">Opções</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="upload.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-gradient-blue text-white">
                    <h5 class="modal-title">Novo Arquivo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <p>Selecione o XML exportado do Lattes</p>
                        <input class="form-control" type="file" name="xmlFile" accept=".xml" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Processar Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    Chart.defaults.font.family = "'Nunito', sans-serif";
    Chart.register(ChartDataLabels);

    const db = <?php echo json_encode($masterData); ?>;

    let chartTitulacao = null;
    let chartStatus = null;
    let chartPublicacoes = null;
    let chartProducaoDetalhada = null; 
    let currentSort = { column: 'nome', direction: 'asc' };

    function initCharts(titulacaoData, statusData, labelsStatus, pubsData, pubsLabels, detalheLabels, detalheData) {
        // ... (Mantive a lógica dos gráficos igual, só encurtei aqui pra resposta caber) ...
        const ctxTit = document.getElementById('chartTitulacao');
        if (chartTitulacao) chartTitulacao.destroy();
        chartTitulacao = new Chart(ctxTit, {
            type: 'doughnut',
            data: {
                labels: ["Doutorado", "Mestrado", "Especialização", "Graduação"],
                datasets: [{ data: titulacaoData, backgroundColor: ['#2ed8b6', '#00bcd4', '#FFB64D', '#FF5370'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10 } }, datalabels: { display: false } }, cutout: '75%' }
        });

        const ctxStat = document.getElementById('chartStatus');
        if (chartStatus) chartStatus.destroy();
        chartStatus = new Chart(ctxStat, {
            type: 'bar',
            data: { labels: labelsStatus, datasets: [{ label: 'Docentes', data: statusData, backgroundColor: ['#2ed8b6', '#4099ff', '#f6c23e', '#e74a3b', '#858796'], borderRadius: 5 }] },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', formatter: (val, ctx) => {
                    let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    return val > 0 ? (val * 100 / sum).toFixed(0) + "%" : "";
                }, font: { weight: 'bold', size: 11 }, color: '#666' } }, 
                scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grace: '10%' } } 
            }
        });

        const ctxPub = document.getElementById('chartPublicacoes');
        if (chartPublicacoes) chartPublicacoes.destroy();
        chartPublicacoes = new Chart(ctxPub, {
            type: 'bar',
            data: { labels: ['1-5 Pubs', 'Nenhuma', '6-10 Pubs', '10-15 Pubs', '> 15 Pubs'], datasets: [{ data: pubsData, backgroundColor: '#2196F3', borderRadius: 3, barPercentage: 0.6 }] },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', formatter: (val, ctx) => {
                    let sum = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    return val > 0 && sum > 0 ? (val * 100 / sum).toFixed(1).replace('.', ',') + "%" : "";
                }, font: { size: 11, weight: 'bold' }, color: '#555' } }, 
                scales: { x: { grid: { display: false } }, y: { display: false, grace: '15%' } } 
            }
        });

        const ctxDet = document.getElementById('chartProducaoDetalhada');
        if (chartProducaoDetalhada) chartProducaoDetalhada.destroy();
        chartProducaoDetalhada = new Chart(ctxDet, {
            type: 'bar',
            data: { labels: detalheLabels, datasets: [{ label: 'Quantidade', data: detalheData, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'], borderRadius: 5, barPercentage: 0.6 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, datalabels: { anchor: 'end', align: 'top', font: { weight: 'bold' }, color: '#444' } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } } }
        });
    }

    function sortColumn(column) {
        if (currentSort.column === column) {
            currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.column = column;
            currentSort.direction = 'desc';
            if(column === 'nome' || column === 'curso') currentSort.direction = 'asc';
        }
        atualizarDashboard();
    }

    function atualizarDashboard() {
        const txt = document.getElementById('filtroTexto').value.toLowerCase();
        const cur = document.getElementById('filtroCurso').value;
        const uni = document.getElementById('filtroUnidade').value;
        const ano = document.getElementById('filtroAno').value;

        let filtrados = db.filter(item => {
            const matchTxt = item.nome.toLowerCase().includes(txt);
            const matchCur = cur === "" || item.curso === cur;
            const matchUni = uni === "" || item.unidade === uni;
            const matchAno = ano === "" || item.ano === ano;
            return matchTxt && matchCur && matchUni && matchAno;
        });

        filtrados.sort((a, b) => {
            let valA = a[currentSort.column];
            let valB = b[currentSort.column];
            if(currentSort.column === 'data_formatada') {
                 valA = valA.split('/').reverse().join('');
                 valB = valB.split('/').reverse().join('');
            }
            if (valA < valB) return currentSort.direction === 'asc' ? -1 : 1;
            if (valA > valB) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });

        renderTable(filtrados);
        calcStats(filtrados);
    }

    function renderTable(data) {
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';
        data.forEach(row => {
            let badgeClass = row.qtd_pubs_recentes > 0 ? 'bg-primary text-white' : 'bg-secondary text-white opacity-50';
            // IMPORTANTE: Atualizei os links para o novo formato index.php?p=...
            let html = `
            <tr class="linha-tabela" data-id="${row.id_lattes}">
                <td class="ps-4">
                    <div class="d-flex align-items-center">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(row.nome)}&background=random&color=fff&size=40" class="rounded-circle me-3 shadow-sm">
                        <div>
                            <div class="fw-bold text-dark">${row.nome}</div>
                            <div class="small text-muted">ID: ${row.id_lattes}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="d-block fw-bold text-primary small">${row.curso.substring(0, 30)}...</span>
                    <small class="text-muted d-block text-truncate" style="max-width: 250px;"><i class="fas fa-building me-1"></i> ${row.unidade}</small>
                </td>
                <td class="text-center">
                    <span class="badge ${badgeClass} rounded-pill px-3 py-2 fw-bold" style="min-width: 40px;">${row.qtd_pubs_recentes}</span>
                </td>
                <td>
                    <span class="badge rounded-pill ${row.status_class == 'success' ? 'badge-soft-success' : 'badge-soft-warning'} px-3">${row.status_label}</span>
                </td>
                <td class="text-end pe-4">
                    <a href="index.php?p=perfil&file=${row.file}" class="btn btn-sm btn-outline-primary rounded-circle" title="Ver"><i class="fas fa-eye"></i></a>
                    <a href="index.php?p=delete&file=${row.file}" class="btn btn-sm btn-outline-danger rounded-circle ms-1" onclick="return confirm('Remover?')"><i class="fas fa-trash"></i></a>
                </td>
            </tr>`;
            tbody.innerHTML += html;
        });
    }

    function calcStats(filtrados) {
        let qtdDoutor = 0, qtdMestre = 0, qtdEsp = 0, qtdGrad = 0;
        let mapInst = {};
        let torres = [0, 0, 0, 0, 0]; 
        let pubNenhuma = 0, pub1_5 = 0, pub6_10 = 0, pub10_15 = 0, pub15_mais = 0;
        let count_at_least_1 = 0, count_at_least_4 = 0, count_at_least_7 = 0, count_at_least_9 = 0;
        let sumLivros = 0, sumTrabalhos = 0, sumResExp = 0, sumRes = 0, sumApres = 0, sumTec = 0;

        filtrados.forEach(d => {
            if(d.titulacao_tipo === 'Doutorado') qtdDoutor++;
            else if(d.titulacao_tipo === 'Mestrado') qtdMestre++;
            else if(d.titulacao_tipo === 'Especialização') qtdEsp++;
            else qtdGrad++;

            if(d.status_torre >= 1 && d.status_torre <= 5) torres[d.status_torre - 1]++;
            let inst = d.inst;
            if(!mapInst[inst]) mapInst[inst] = 0;
            mapInst[inst]++;

            let p = d.qtd_pubs_recentes;
            if (p === 0) pubNenhuma++;
            else if (p <= 5) pub1_5++;
            else if (p <= 10) pub6_10++;
            else if (p <= 15) pub10_15++;
            else pub15_mais++;

            if (p >= 1) count_at_least_1++;
            if (p >= 4) count_at_least_4++;
            if (p >= 7) count_at_least_7++;
            if (p >= 9) count_at_least_9++;

            sumLivros += d.detalhe_livros;
            sumTrabalhos += d.detalhe_trabalhos;
            sumResExp += d.detalhe_resumos_exp;
            sumRes += d.detalhe_resumos;
            sumApres += d.detalhe_apresentacoes;
            sumTec += d.detalhe_tecnica;
        });

        document.getElementById('statTotalDocentes').innerText = filtrados.length;
        document.getElementById('statTotalDoutores').innerText = qtdDoutor;
        document.getElementById('statTotalMestres').innerText = qtdMestre;
        document.getElementById('statTotalEspecialistas').innerText = qtdEsp;
        document.getElementById('statTotalGraduados').innerText = qtdGrad;

        updateKPIs(filtrados.length, qtdDoutor, qtdMestre, qtdEsp, qtdGrad);
        updateKPIPublicacoes(filtrados.length, pubNenhuma, count_at_least_1, count_at_least_4, count_at_least_7, count_at_least_9);
        atualizarTopInstituicoes(mapInst);

        let labelsStatus = [`Excelente`, `Bom`, `Atenção`, `Cuidado`, `Crítico`];
        let pubsDataChart = [pub1_5, pubNenhuma, pub6_10, pub10_15, pub15_mais];
        let pubsLabels = ['1-5 Pubs', 'Nenhuma', '6-10 Pubs', '10-15 Pubs', '> 15 Pubs'];
        let detalheLabels = ['Livros', 'Trabalhos Pub.', 'Resumos Exp.', 'Resumos Pub.', 'Apresentações', 'Técnica'];
        let detalheData = [sumLivros, sumTrabalhos, sumResExp, sumRes, sumApres, sumTec];

        initCharts([qtdDoutor, qtdMestre, qtdEsp, qtdGrad], torres, labelsStatus, pubsDataChart, pubsLabels, detalheLabels, detalheData);
    }

    // Funções auxiliares (KPIs)
    function updateKPIPublicacoes(total, zeroPubs, c1, c4, c7, c9) {
        if (total === 0) { document.getElementById('kpiPubScore').innerText = "0"; return; }
        let score = 1;
        if (c9 / total >= 0.50) score = 5;
        else if (c7 / total >= 0.50) score = 4;
        else if (c4 / total >= 0.50) score = 3;
        else if (c1 / total >= 0.50) score = 2;
        document.getElementById('kpiPubScore').innerText = score;
    }

    function updateKPIs(total, d, m, e, g) {
        if (total === 0) {
            ['kpiConceito','kpiIQCD','countDoutor','countMestre','countEspecialista','countGraduado'].forEach(id => document.getElementById(id).innerText = "0");
            ['percDoutor','percMestre','percEspecialista'].forEach(id => document.getElementById(id).innerText = "0%");
            return;
        }
        let somaMD = m + d;
        let percMD = somaMD / total;
        let conceito = 1;
        if (percMD >= 0.80) conceito = 5;
        else if (percMD >= 0.60) conceito = 4;
        else if (percMD >= 0.40) conceito = 3;
        else if (percMD >= 0.25) conceito = 2;

        let pesoTotal = (d * 5) + (m * 3) + (e * 2) + (g * 1);
        let iqcd = pesoTotal / total;

        document.getElementById('kpiConceito').innerText = conceito;
        document.getElementById('kpiIQCD').innerText = iqcd.toFixed(2).replace('.', ',');
        document.getElementById('countDoutor').innerText = d;
        document.getElementById('countMestre').innerText = m;
        document.getElementById('countEspecialista').innerText = e;
        document.getElementById('countGraduado').innerText = g;
        document.getElementById('percDoutor').innerText = Math.round((d / total) * 100) + "%";
        document.getElementById('percMestre').innerText = Math.round((m / total) * 100) + "%";
        document.getElementById('percEspecialista').innerText = Math.round((e / total) * 100) + "%";
    }

    function atualizarTopInstituicoes(mapa) {
        let sortable = [];
        for (let inst in mapa) sortable.push([inst, mapa[inst]]);
        sortable.sort(function(a, b) { return b[1] - a[1]; });
        let top5 = sortable.slice(0, 5);
        const ul = document.getElementById('listaTopInstituicoes');
        ul.innerHTML = ''; 
        const colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-danger', 'bg-info'];
        top5.forEach(item => {
            let color = colors[Math.floor(Math.random() * colors.length)];
            let letra = item[0].charAt(0).toUpperCase();
            ul.innerHTML += `
            <li class="list-group-item border-0 d-flex align-items-center px-4 mb-2">
                <div class="avatar-circle ${color} text-white me-3">${letra}</div>
                <div class="flex-grow-1 text-truncate">
                    <span class="fw-bold text-dark d-block text-truncate small">${item[0]}</span>
                    <small class="text-muted">${item[1]} docentes</small>
                </div>
            </li>`;
        });
    }

    function limparFiltros() {
        document.getElementById('filtroTexto').value = "";
        document.getElementById('filtroCurso').value = "";
        document.getElementById('filtroUnidade').value = "";
        document.getElementById('filtroAno').value = "";
        atualizarDashboard();
    }

    function exportarExcel() {
        const txt = document.getElementById('filtroTexto').value.toLowerCase();
        const cur = document.getElementById('filtroCurso').value;
        const uni = document.getElementById('filtroUnidade').value;
        const ano = document.getElementById('filtroAno').value;

        const dadosParaExportar = db.filter(item => {
            const matchTxt = item.nome.toLowerCase().includes(txt);
            const matchCur = cur === "" || item.curso === cur;
            const matchUni = uni === "" || item.unidade === uni;
            const matchAno = ano === "" || item.ano === ano;
            return matchTxt && matchCur && matchUni && matchAno;
        });

        if(dadosParaExportar.length === 0) {
            Swal.fire('Atenção', 'Nenhum dado para exportar.', 'warning');
            return;
        }

        let csvContent = "\uFEFF"; 
        csvContent += "Nome;ID Lattes;Instituição;Unidade;Curso;Titulação;Publicações (3 anos);Atualização;Status\n";

        dadosParaExportar.forEach(row => {
            let linha = [
                row.nome, "'" + row.id_lattes, row.inst, row.unidade, row.curso,
                row.titulacao_tipo, row.qtd_pubs_recentes, row.data_formatada, row.status_label
            ];
            csvContent += linha.join(";") + "\n";
        });

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        const url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "relatorio_lattes_completo.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    ['filtroTexto','filtroCurso','filtroUnidade','filtroAno'].forEach(id => {
        let el = document.getElementById(id);
        if(el) el.addEventListener(id==='filtroTexto'?'keyup':'change', atualizarDashboard);
    });

    window.onload = function() {
        atualizarDashboard();
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status) {
            let t='Sucesso', txt='Operação realizada.', ic='success';
            if(status.includes('error')) { t='Erro'; ic='error'; }
            Swal.fire({ title: t, text: txt, icon: ic, confirmButtonColor: '#4e73df' })
            .then(() => window.history.replaceState(null, null, window.location.pathname));
        }
    };
</script>