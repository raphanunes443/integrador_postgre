# SIGA - Sistema de Gerenciamento de Ambientes

![Status](https://img.shields.io/badge/status-em%20desenvolvimento-blue)
![Versão](https://img.shields.io/badge/versão-1.0.0-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15.x-4169E1?logo=postgresql&logoColor=white)
![Supabase](https://img.shields.io/badge/Supabase-3ECF8E?logo=supabase&logoColor=white)

O **SIGA** é uma plataforma web e responsiva desenvolvida para centralizar e organizar o gerenciamento de salas, cursos, cronogramas e reservas de instituições acadêmicas. O sistema resolve problemas de conflitos de agendamento, falta de controle de horários e descentralização de informações, oferecendo uma visão clara e em tempo real da ocupação dos ambientes.

---

## Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação e Configuração](#-instalação-e-configuração)
- [Acesso ao Sistema](#-acesso-ao-sistema)
- [Estrutura de Pastas](#-estrutura-de-pastas)
- [Links do Projeto](#-links-do-projeto)
- [Autores](#-autores)
- [Licença](#-licença)

---

## Sobre o Projeto

O projeto foi desenvolvido como **Trabalho de Conclusão de Curso (TCC)** do curso Técnico em Desenvolvimento de Sistemas da **Faculdade Senac Belo Horizonte** (Turma 0648), sob orientação do docente **Cleiton de Jesus Pereira**.

O sistema adota uma arquitetura **Multi-Tenant**, permitindo que múltiplas organizações (escolas, faculdades, empresas, salões, etc.) utilizem a mesma plataforma de forma isolada e segura, cada uma com seus próprios usuários, ambientes e configurações.

---

## Funcionalidades

### Autenticação e Acesso
- Login e Logout seguro
- Cadastro de organizações e usuários administradores
- Resetar senha
- Controle de perfis (Administrador, Coordenador, Professor)

### Cronograma e Aulas
- Agendar e editar aulas
- Listar todas as aulas
- Visualização por curso
- Alocação de horários sem conflitos

### Gestão de Cursos
- Cadastrar, editar e listar cursos
- Inativar cursos
- Vincular turmas e professores

### Mapa e Ambientes
- Visualização gráfica das salas
- Cadastro de unidades, capacidade e recursos
- Dashboard em tempo real com taxa de ocupação

### Reservas e Manutenções
- Reservas pontuais de salas
- Controle de manutenções
- Registro de períodos de recesso
- Histórico de alterações (Auditoria)

---

## Tecnologias Utilizadas

### Frontend
- **HTML5** e **CSS3** (Design responsivo)
- **JavaScript** (Validações e interações)
- **Font Awesome 6.5** (Ícones)
- **Google Fonts** (Fonte Inter)

### Backend
- **PHP 8.x** (Orientado a objetos e PDO)
- **PostgreSQL 15.x** (Banco de dados relacional)
- **Supabase** (Hospedagem do banco de dados PostgreSQL na nuvem)
- **Arquitetura Multi-Tenant** (Isolamento de dados por cliente)

---

## Pré-requisitos

Antes de começar, você vai precisar ter instalado em sua máquina:

- [XAMPP](https://www.apachefriends.org/pt_br/index.html) (ou WAMP/LAMP) com PHP 8.0 ou superior
- Extensão **pdo_pgsql** habilitada no PHP
- Navegador moderno (Chrome, Firefox, Edge)
- Acesso à internet (para conectar ao Supabase)

Não é necessário instalar o PostgreSQL localmente. O banco de dados está hospedado no Supabase.

---

## Instalação e Configuração

1. **Mova o projeto para o servidor local:**
   - Se estiver usando XAMPP, coloque a pasta do projeto dentro de `C:\xampp\htdocs\`.

2. **Configure a conexão com o Supabase:**
   - Abra o arquivo `conexao_banco.php`.
   - As credenciais já estão configuradas para o projeto no Supabase. Caso precise alterar:
     ```php
     $host = 'aws-0-us-east-2.pooler.supabase.com';
     $port = '5432';
     $dbname = 'postgres';
     $username = 'postgres.SEU_PROJETO';
     $password = 'SIGAPROJ123';