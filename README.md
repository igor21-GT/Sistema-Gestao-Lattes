# 📊 Sistema de Gestão de Currículos Lattes

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Status](https://img.shields.io/badge/Status-Concluído-success?style=for-the-badge)

## 💻 Sobre o Projeto

Este sistema é uma solução de **Business Intelligence (BI) Acadêmico**. Ele automatiza a leitura de arquivos XML exportados da **Plataforma Lattes (CNPq)**, transformando dados não estruturados em dashboards gerenciais para tomada de decisão.

O objetivo é permitir que coordenadores e gestores visualizem a produtividade do corpo docente sem precisar abrir currículo por currículo.

## 🚀 Funcionalidades

- **Dashboard Interativo:** KPIs de titulação (Doutores, Mestres), produção bibliográfica recente e rankings.
- **Leitura de XML (Parser):** Algoritmo próprio em PHP para varrer a estrutura complexa do Lattes.
- **Filtros Avançados:** Busca dinâmica por Nome, Curso, Unidade e Ano de Atualização.
- **Perfil do Docente:** Página individual com timeline de formação, gráficos de produção anual e listagem de orientações.
- **Indicadores de Qualidade:** Cálculo automático de pontuação baseada em critérios da CAPES/MEC.

## 🛠️ Tecnologias Utilizadas

- **Back-end:** PHP 8 (POO, XML Parsing)
- **Front-end:** HTML5, CSS3, Bootstrap 5
- **Scripts:** JavaScript (Manipulação de DOM e Filtros)
- **Gráficos:** Chart.js
- **Versionamento:** Git/GitHub

## 📂 Estrutura do Projeto

O sistema não utiliza banco de dados SQL. Ele funciona como um **Leitor de Arquivos**:
1. O usuário coloca os arquivos `.xml` na pasta `data/xml/`.
2. O sistema lê, processa e exibe os dados em tempo real.

## 👣 Como Usar

1. Clone este repositório.
2. Configure um servidor local (XAMPP, WAMP ou Docker).
3. Coloque os arquivos XML dos docentes na pasta `data/xml/`.
4. Acesse `http://localhost/Sistema_Docentes` no navegador.

---
Desenvolvido por Igor Johnson - (https://www.linkedin.com/in/igor-pacheco-5a315b310/)