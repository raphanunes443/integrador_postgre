# 🎓 SIGA - Sistema de Gerenciamento de Ambientes

![Status](https://img.shields.io/badge/status-em%20desenvolvimento-blue)
![Versão](https://img.shields.io/badge/versão-1.0.0-brightgreen)
![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)

O **SIGA** é uma plataforma web e responsiva desenvolvida para centralizar e organizar o gerenciamento de salas, cursos, cronogramas e reservas de instituições acadêmicas. O sistema resolve problemas de conflitos de agendamento, falta de controle de horários e descentralização de informações, oferecendo uma visão clara e em tempo real da ocupação dos ambientes.

---

## 📋 Índice

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

## 🚀 Sobre o Projeto

O projeto foi desenvolvido como **Trabalho de Conclusão de Curso (TCC)** do curso Técnico em Desenvolvimento de Sistemas da **Faculdade Senac Belo Horizonte** (Turma 0648), sob orientação do docente **Cleiton de Jesus Pereira**.

O sistema adota uma arquitetura **Multi-Tenant**, permitindo que múltiplas organizações (escolas, faculdades, empresas, salões, etc.) utilizem a mesma plataforma de forma isolada e segura, cada uma com seus próprios usuários, ambientes e configurações.

---

## ⚙️ Funcionalidades

### 🔐 Autenticação e Acesso
- Login e Logout seguro
- Cadastro de organizações e usuários administradores
- Resetar senha
- Controle de perfis (Administrador, Coordenador, Professor)

### 📅 Cronograma e Aulas
- Agendar e editar aulas
- Listar todas as aulas
- Visualização por curso
- Alocação de horários sem conflitos

### 🎓 Gestão de Cursos
- Cadastrar, editar e listar cursos
- Inativar cursos
- Vincular turmas e professores

### 🗺️ Mapa e Ambientes
- Visualização gráfica das salas
- Cadastro de unidades, capacidade e recursos
- Dashboard em tempo real com taxa de ocupação

### 🔧 Reservas e Manutenções
- Reservas pontuais de salas
- Controle de manutenções
- Registro de períodos de recesso
- Histórico de alterações (Auditoria)

---

## 🛠 Tecnologias Utilizadas

### Frontend
- **HTML5** e **CSS3** (Design responsivo)
- **JavaScript** (Validações e interações)
- **Font Awesome 6.5** (Ícones)
- **Google Fonts** (Fonte Inter)

### Backend
- **PHP 8.x** (Orientado a objetos e PDO)
- **MySQL 8.x** (Banco de dados relacional)
- **Arquitetura Multi-Tenant** (Isolamento de dados por cliente)

---

## 📦 Pré-requisitos

Antes de começar, você vai precisar ter instalado em sua máquina:

- [XAMPP](https://www.apachefriends.org/pt_br/index.html) (ou WAMP/LAMP)
- PHP 8.0 ou superior
- MySQL 8.0 ou superior
- Navegador moderno (Chrome, Firefox, Edge)

---

## 🔧 Instalação e Configuração

1. **Mova o projeto para o servidor local:**
   - Se estiver usando XAMPP, coloque a pasta do projeto dentro de `C:\xampp\htdocs\`.

2. **Configure o banco de dados:**
   - Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`).
   - Crie um banco de dados chamado `siga_db`.
   - Importe o arquivo `.sql` localizado na pasta `/database/` (se houver).

3. **Configure a conexão:**
   - Abra o arquivo `conexao_banco.php`.
   - Altere as credenciais de acesso ao banco:
     ```php
     $host = 'localhost';
     $dbname = 'projeto_integrador';
     $user = 'root';
     $pass = ''; // Sua senha do MySQL
Inicie o servidor:

Abra o XAMPP e inicie os módulos Apache e MySQL.

Acesse no navegador: http://localhost/nome-da-pasta/

🔑 Acesso ao Sistema
Para facilitar os testes e a avaliação do projeto, disponibilizamos abaixo as credenciais padrão de acesso:

⚠️ Atenção: Altere a senha padrão após o primeiro login em ambiente de produção.

Campo	Valor Padrão
E-mail / Login:	siga@adm.com
Senha:	Senha123@
Perfil	Administrador
💡 Dica: Caso não haja um usuário padrão no banco de dados, utilize a tela de Cadastro (cadastro.php) para criar uma nova organização e um usuário administrador.

📁 Estrutura de Pastas
text
SIGA/
│
├── AUTENTIFICACAO_ACESSO/      # Login, cadastro, logout e recuperação de senha
│   ├── cadastro.php
│   ├── realizar_login.php
│   └── logout.php
│
├── IMAGENS/                    # Imagens e ícones do sistema
│   └── Predio.png
│
├── conexao_banco.php           # Configuração e conexão com o MySQL
├── index.html                  # Página inicial / Landing page
└── README.md                   # Documentação do projeto
🔗 Links do Projeto
Acompanhe o desenvolvimento, o planejamento e a gestão de tarefas do projeto através dos links abaixo:

Plataforma	Link
Repositório GitHub	https://github.com/raphanunes443/integrador_postgre
Quadro Trello	https://trello.com/b/0g9ohsWu/siga-app
👥 Autores
Projeto desenvolvido pelos integrantes da Turma 0648:

Shanaya Nataly — LinkedIn

Raphael Nunes — LinkedIn

Simone Alves — LinkedIn

Aline Martins — LinkedIn

Luiz Felipe — LinkedIn

Carlos Wilson

Orientador: Prof. Cleiton de Jesus Pereira
Instituição: Faculdade Senac Belo Horizonte