<?php
// ARQUIVO: src/LattesParser.php

class LattesParser {
    private $xml;

    public function __construct($caminhoArquivo) {
        if (!file_exists($caminhoArquivo)) throw new Exception("Arquivo não encontrado.");
        libxml_use_internal_errors(true);
        $this->xml = simplexml_load_file($caminhoArquivo);
        if (!$this->xml) throw new Exception("XML inválido.");
    }

    public function getDadosGerais() {
        $dados = $this->xml->{'DADOS-GERAIS'} ?? null;
        if (!$dados) return ['nome' => 'Erro na leitura', 'titulacao' => '', 'resumo' => ''];

        $endereco = $dados->{'ENDERECO-PROFISSIONAL'} ?? null;
        $resumo = (string)($dados->{'RESUMO-CV'} ?? $dados->{'RESUMO-CV-EM-INGLES'} ?? 'Resumo não informado.');
        
        $area = "Não Informada";
        if(isset($dados->{'AREAS-DE-ATUACAO'}->{'AREA-DE-ATUACAO'})) {
            $primeiraArea = $dados->{'AREAS-DE-ATUACAO'}->{'AREA-DE-ATUACAO'}[0];
            $area = (string)($primeiraArea['NOME-DA-AREA-DO-CONHECIMENTO'] ?? '');
        }

        return [
            'id_lattes' => (string)($this->xml['NUMERO-IDENTIFICADOR-ON-PLATAFORMA-LATTES'] ?? ''),
            'nome' => (string)($dados['NOME-COMPLETO'] ?? 'Nome não encontrado'),
            'citacao' => (string)($dados['NOME-EM-CITACOES-BIBLIOGRAFICAS'] ?? ''),
            'titulacao' => $this->calcularTitulacaoMaxima(),
            'resumo' => $resumo,
            'instituicao' => (string)($endereco['NOME-INSTITUICAO'] ?? 'Instituição não informada'),
            'unidade' => (string)($endereco['NOME-ORGAO'] ?? ''),
            'area_curso' => ucfirst(mb_strtolower($area)),
            'data_atualizacao' => (string)($this->xml['DATA-ATUALIZACAO'] ?? '')
        ];
    }

    // --- MÉTODOS DE PRODUÇÃO ---

    public function getArtigos() {
        $lista = [];
        if (isset($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'ARTIGOS-PUBLICADOS'}->{'ARTIGO-PUBLICADO'})) {
            foreach ($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'ARTIGOS-PUBLICADOS'}->{'ARTIGO-PUBLICADO'} as $item) {
                $dados = $item->{'DADOS-BASICOS-DO-ARTIGO'} ?? null;
                $detalhe = $item->{'DETALHAMENTO-DO-ARTIGO'} ?? null;
                if ($dados) {
                    $lista[] = [
                        'tipo' => 'Artigo',
                        'titulo' => (string)$dados['TITULO-DO-ARTIGO'],
                        'ano' => (int)$dados['ANO-DO-ARTIGO'],
                        'veiculo' => (string)($detalhe['TITULO-DO-PERIODICO-OU-REVISTA'] ?? '')
                    ];
                }
            }
        }
        return $lista;
    }

    public function getLivros() {
        $lista = [];
        if (isset($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'LIVROS-PUBLICADOS-OU-ORGANIZADOS'}->{'LIVRO-PUBLICADO-OU-ORGANIZADO'})) {
            foreach ($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'LIVROS-PUBLICADOS-OU-ORGANIZADOS'}->{'LIVRO-PUBLICADO-OU-ORGANIZADO'} as $item) {
                $dados = $item->{'DADOS-BASICOS-DO-LIVRO'} ?? null;
                $detalhe = $item->{'DETALHAMENTO-DO-LIVRO'} ?? null;
                if ($dados) {
                    $lista[] = [
                        'tipo' => 'Livro',
                        'titulo' => (string)$dados['TITULO-DO-LIVRO'],
                        'ano' => (int)$dados['ANO'],
                        'veiculo' => 'Ed. ' . (string)($detalhe['NOME-DA-EDITORA'] ?? '')
                    ];
                }
            }
        }
        return $lista;
    }

    // ADICIONADO: CAPÍTULOS DE LIVROS
    public function getCapitulos() {
        $lista = [];
        if (isset($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'CAPITULOS-DE-LIVROS-PUBLICADOS'}->{'CAPITULO-DE-LIVRO-PUBLICADO'})) {
            foreach ($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'LIVROS-E-CAPITULOS'}->{'CAPITULOS-DE-LIVROS-PUBLICADOS'}->{'CAPITULO-DE-LIVRO-PUBLICADO'} as $item) {
                $dados = $item->{'DADOS-BASICOS-DO-CAPITULO'} ?? null;
                $detalhe = $item->{'DETALHAMENTO-DO-CAPITULO'} ?? null;
                if ($dados) {
                    $lista[] = [
                        'tipo' => 'Capítulo',
                        'titulo' => (string)$dados['TITULO-DO-CAPITULO-DO-LIVRO'],
                        'ano' => (int)$dados['ANO'],
                        'veiculo' => 'In: ' . (string)($detalhe['TITULO-DO-LIVRO'] ?? '')
                    ];
                }
            }
        }
        return $this->ordenarPorAno($lista);
    }

    public function getTrabalhosEventos() {
        $lista = [];
        if (isset($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'TRABALHOS-EM-EVENTOS'}->{'TRABALHO-EM-EVENTOS'})) {
            foreach ($this->xml->{'PRODUCAO-BIBLIOGRAFICA'}->{'TRABALHOS-EM-EVENTOS'}->{'TRABALHO-EM-EVENTOS'} as $item) {
                $dados = $item->{'DADOS-BASICOS-DO-TRABALHO'} ?? null;
                $detalhe = $item->{'DETALHAMENTO-DO-TRABALHO'} ?? null;
                if ($dados) {
                    $natureza = strtoupper((string)($dados['NATUREZA'] ?? ''));
                    $tipo = ($natureza === 'COMPLETO') ? 'Trabalho Completo' : (($natureza === 'RESUMO_EXPANDIDO') ? 'Resumo Expandido' : 'Resumo');
                    
                    $lista[] = [
                        'tipo' => $tipo,
                        'titulo' => (string)$dados['TITULO-DO-TRABALHO'],
                        'ano' => (int)$dados['ANO-DO-TRABALHO'],
                        'veiculo' => (string)($detalhe['NOME-DO-EVENTO'] ?? '')
                    ];
                }
            }
        }
        return $lista;
    }

    // ADICIONADO: ORIENTAÇÕES
    public function getOrientacoes() {
        $lista = [];
        $root = $this->xml->{'OUTRA-PRODUCAO'}->{'ORIENTACOES-CONCLUIDAS'} ?? null;
        if(!$root) return [];

        // Mestrado
        if(isset($root->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'})) {
            foreach($root->{'ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'} as $item) {
                $d = $item->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-MESTRADO'};
                $lista[] = ['tipo' => 'Mestrado', 'titulo' => (string)$d['TITULO'], 'ano' => (int)$d['ANO']];
            }
        }
        // Doutorado
        if(isset($root->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'})) {
            foreach($root->{'ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'} as $item) {
                $d = $item->{'DADOS-BASICOS-DE-ORIENTACOES-CONCLUIDAS-PARA-DOUTORADO'};
                $lista[] = ['tipo' => 'Doutorado', 'titulo' => (string)$d['TITULO'], 'ano' => (int)$d['ANO']];
            }
        }
        // Outras
        if(isset($root->{'OUTRAS-ORIENTACOES-CONCLUIDAS'})) {
            foreach($root->{'OUTRAS-ORIENTACOES-CONCLUIDAS'} as $item) {
                $d = $item->{'DADOS-BASICOS-DE-OUTRAS-ORIENTACOES-CONCLUIDAS'};
                $lista[] = ['tipo' => 'TCC/IC', 'titulo' => (string)$d['TITULO'], 'ano' => (int)$d['ANO']];
            }
        }
        return $this->ordenarPorAno($lista);
    }

    // ADICIONADO: PRODUÇÃO TÉCNICA
    public function getProducaoTecnica() {
        $lista = [];
        $root = $this->xml->{'PRODUCAO-TECNICA'} ?? null;
        if(!$root) return [];

        if(isset($root->{'SOFTWARE'})) {
            foreach($root->{'SOFTWARE'} as $item) {
                $d = $item->{'DADOS-BASICOS-DO-SOFTWARE'};
                $lista[] = ['tipo' => 'Software', 'titulo' => (string)$d['TITULO-DO-SOFTWARE'], 'ano' => (int)$d['ANO']];
            }
        }
        if(isset($root->{'TRABALHO-TECNICO'})) {
            foreach($root->{'TRABALHO-TECNICO'} as $item) {
                $d = $item->{'DADOS-BASICOS-DO-TRABALHO-TECNICO'};
                $lista[] = ['tipo' => 'Trabalho Técnico', 'titulo' => (string)$d['TITULO-DO-TRABALHO-TECNICO'], 'ano' => (int)$d['ANO']];
            }
        }
        return $this->ordenarPorAno($lista);
    }

    public function getEstatisticasGrafico($artigos) {
        $contagemPorAno = [];
        foreach ($artigos as $artigo) {
            $ano = $artigo['ano'];
            if($ano > 0) {
                if (!isset($contagemPorAno[$ano])) $contagemPorAno[$ano] = 0;
                $contagemPorAno[$ano]++;
            }
        }
        ksort($contagemPorAno);
        return ['labels' => array_keys($contagemPorAno), 'data' => array_values($contagemPorAno)];
    }

    public function getFormacao() {
        $formacao = [];
        $areaFormacao = $this->xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'} ?? null;
        if (!$areaFormacao) return [];

        $mapa = ['POS-DOUTORADO'=>'Pós-Doc', 'DOUTORADO'=>'Doutorado', 'MESTRADO'=>'Mestrado', 'ESPECIALIZACAO'=>'Especialização', 'GRADUACAO'=>'Graduação'];
        
        foreach ($mapa as $tag => $nomeLegivel) {
            if (isset($areaFormacao->$tag)) {
                foreach ($areaFormacao->$tag as $item) {
                    $nomeCurso = (string)($item['NOME-DO-CURSO'] ?? $item['NOME-CURSO'] ?? '');
                    if(empty($nomeCurso)) {
                        $areaC = (string)($item['NOME-AREA-DO-CONHECIMENTO'] ?? '');
                        $nomeCurso = !empty($areaC) ? "Área: ".$areaC : "Curso não informado";
                    }
                    $formacao[] = [
                        'tipo' => $nomeLegivel,
                        'curso' => $nomeCurso,
                        'ano' => (int)($item['ANO-DE-CONCLUSAO'] ?? 0),
                        'instituicao' => (string)($item['NOME-INSTITUICAO'] ?? '')
                    ];
                }
            }
        }
        return $this->ordenarPorAno($formacao);
    }

    private function ordenarPorAno($array) {
        usort($array, function($a, $b) { return $b['ano'] - $a['ano']; });
        return $array;
    }

    private function calcularTitulacaoMaxima() {
        $f = $this->xml->{'DADOS-GERAIS'}->{'FORMACAO-ACADEMICA-TITULACAO'} ?? null;
        if (!$f) return 'Não informada';
        if (isset($f->{'POS-DOUTORADO'})) return 'Pós-Doutor';
        if (isset($f->{'DOUTORADO'})) return 'Doutor';
        if (isset($f->{'MESTRADO'})) return 'Mestre';
        if (isset($f->{'ESPECIALIZACAO'})) return 'Especialista';
        return 'Graduado';
    }
}
?>