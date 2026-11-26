<?php
// AUTENTIFICACAO_ACESSO/dashboard.php

// ============================================================
// 1. CARREGAR CONEXÃO
// ============================================================
require_once __DIR__ . '/../conexao_banco.php';

// ============================================================
// 2. INICIAR SESSÃO 
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// 3. VERIFICAR SE ESTÁ LOGADO - 
// ============================================================
if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['id_cliente'])) {
    header('Location: realizar_login.php');
    exit;
}

// ============================================================
// 4. SE CHEGOU AQUI, ESTÁ LOGADO
// ============================================================
$nome_usuario = $_SESSION['nome_usuario'] ?? 'Usuário';
$nome_cliente = $_SESSION['nome_cliente'] ?? '';
$tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
$id_cliente = $_SESSION['id_cliente'] ?? 0;
$id_unidade_sessao = $_SESSION['usuario_unidade'] ?? 0;

// ============================================================
// 5. BUSCAR ESTATÍSTICAS 
// ============================================================
$stats = [
    'total_unidades' => 0,
    'total_cursos' => 0,
    'total_funcionarios' => 0,
    'total_salas' => 0
];

$lista_unidades = [];

if (isset($conn) && $id_cliente > 0) {
    try {
        // Estatísticas
        $stmt = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM unidades WHERE id_cliente = ?) as total_unidades,
                (SELECT COUNT(*) FROM cursos WHERE id_cliente = ?) as total_cursos,
                (SELECT COUNT(*) FROM funcionarios WHERE id_cliente = ?) as total_funcionarios,
                (SELECT COUNT(*) FROM salas WHERE id_cliente = ?) as total_salas
        ");
        $stmt->execute([$id_cliente, $id_cliente, $id_cliente, $id_cliente]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar unidades para o modal de relatório (Apenas se for admin ou gerente)
        if (in_array($tipo_usuario, ['admin_cliente', 'gerente'])) {
            $stmt_unidades = $conn->prepare("SELECT id_unidade, nome_unidade FROM unidades WHERE id_cliente = ? AND status_unidade = 'ativo' ORDER BY nome_unidade ASC");
            $stmt_unidades->execute([$id_cliente]);
            $lista_unidades = $stmt_unidades->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        // Ignora erro de estatísticas
    }
}

// ============================================================
// 6. TÍTULO DA PÁGINA
// ============================================================
$titulo = 'Dashboard - Gerenciamento de Ambientes';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet"/>
    
    <style>
        /* ======================================================
           RESET & BASE
        ====================================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f0f4fb;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* ======================================================
           SIDEBAR - ESTILOS BÁSICOS
        ====================================================== */
        .sidebar {
            width: 270px;
            background: #ffffff;
            border-right: 1px solid #e8edf5;
            padding: 28px 20px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            flex-shrink: 0;
            overflow-y: auto;
            height: 100vh;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0f4fb;
        }

        .logo-icon {
            background: linear-gradient(145deg, #1a73e8, #0d47a1);
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            box-shadow: 0 8px 16px -6px rgba(26, 115, 232, 0.3);
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #1a2639;
        }
        .logo-text span { color: #1a73e8; }
        .logo-text small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: #7a8aa0;
            margin-top: 2px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .menu-label {
            font-size: 11px;
            font-weight: 600;
            color: #9aabbf;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px 6px;
        }

        .menu-item-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 16px;
            border-radius: 10px;
            color: #5a6a7e;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .menu-item-link i {
            width: 20px;
            font-size: 16px;
            color: #8a9bb5;
            transition: color 0.15s;
        }
        .menu-item-link:hover {
            background: #f0f6ff;
            color: #1a2639;
        }
        .menu-item-link:hover i { color: #1a73e8; }
        .menu-item-link.active {
            background: #1a73e8;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
        }
        .menu-item-link.active i { color: #ffffff; }

        .sidebar-footer {
            border-top: 1px solid #edf2f9;
            padding-top: 16px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
            margin-top: auto;
        }

        .sidebar-footer .user-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(145deg, #eef2f9, #dce3ef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            color: #2d3a4f;
        }

        .user-info { line-height: 1.3; }
        .user-info .name { font-weight: 600; font-size: 13px; color: #1a2639; }
        .user-info .role { font-size: 12px; color: #8a9bb5; }
        .user-info .cliente {
            font-size: 11px;
            color: #1a73e8;
            font-weight: 500;
            display: block;
            margin-top: 2px;
        }

        .logout-btn-sidebar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #dc3545;
            color: #ffffff;
            border: none;
            border-radius: 60px;
            padding: 10px 16px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
            width: 100%;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
            cursor: pointer;
        }
        .logout-btn-sidebar:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.35);
        }

        /* ======================================================
           MAIN CONTENT
        ====================================================== */
        .main {
            flex: 1;
            padding: 28px 36px 20px;
            overflow-y: auto;
            background: #f0f4fb;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #0e1a2b;
        }
        .page-title i {
            color: #1a73e8;
            margin-right: 10px;
        }
        .page-subtitle {
            font-size: 14px;
            color: #7a8aa0;
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 9px 20px;
            border-radius: 60px;
            border: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-danger {
            background: #dc3545;
            color: #ffffff;
            border: none;
        }
        .btn-danger:hover {
            background: #c82333;
        }

        .btn-primary {
            background: #1a73e8;
            color: #ffffff;
            border: none;
            box-shadow: 0 6px 16px -4px rgba(26, 115, 232, 0.35);
        }
        .btn-primary:hover {
            background: #1557b0;
            transform: translateY(-1px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 16px;
            border: 1px solid #ebf0f8;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            color: #0e1a2b;
        }
        .stat-card .label {
            font-size: 14px;
            color: #7a8aa0;
            margin-top: 4px;
        }
        .stat-card .icon {
            float: right;
            font-size: 28px;
            color: #1a73e8;
            opacity: 0.2;
        }

        .card-panel {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #ebf0f8;
            padding: 24px 28px;
            margin-bottom: 20px;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .menu-item {
            background: #f8faff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #1a2639;
            transition: all 0.2s;
            border: 1px solid #eef3fa;
        }
        .menu-item:hover {
            background: #e3f2fd;
            border-color: #1a73e8;
            transform: translateY(-2px);
        }
        .menu-item .icon {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
        }
        .menu-item .name {
            font-weight: 500;
            font-size: 14px;
        }

        .footer-system {
            text-align: center;
            font-size: 12px;
            color: #8a9bb5;
            padding: 16px 0 8px;
            border-top: 1px solid #e2e9f3;
            margin-top: auto;
            background: transparent;
            flex-shrink: 0;
        }

        /* ======================================================
           MODAL - NOVO
        ====================================================== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(10, 25, 47, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: #ffffff;
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3);
            animation: modalIn 0.2s ease-out;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eef3fa;
            padding-bottom: 12px;
        }
        .modal-header h3 {
            font-size: 18px;
            color: #0e1a2b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .modal-header h3 i { color: #1a73e8; }
        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: #7a8aa0;
            cursor: pointer;
            transition: color 0.2s;
        }
        .modal-close:hover { color: #dc3545; }

        .modal-form-group {
            margin-bottom: 20px;
        }
        .modal-form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1a2639;
            margin-bottom: 6px;
        }
        .modal-form-group select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #e2e9f3;
            background: #fafcff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #1a2639;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .modal-form-group select:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .btn-cancel {
            background: #f0f4fb;
            color: #5a6a7e;
        }
        .btn-cancel:hover {
            background: #e2e9f3;
        }

        /* ======================================================
           RESPONSIVIDADE
        ====================================================== */
        @media (max-width: 820px) {
            .sidebar { display: none; }
            .main { padding: 16px 18px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .header-actions { width: 100%; margin-top: 10px; }
            .header-actions .btn { flex: 1; justify-content: center; }
        }

        @media (max-width: 540px) {
            .main { padding: 12px 14px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .stats-grid { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr 1fr; }
            .modal-box { padding: 20px; margin: 15px; }
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1000;
            background: #1a73e8;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 22px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(26, 115, 232, 0.3);
            transition: background 0.2s;
        }
        .menu-toggle:hover {
            background: #1557b0;
        }
        .menu-toggle i {
            font-size: 24px;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
        body.menu-open {
            overflow: hidden;
        }

        @media (max-width: 820px) {
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>

    <?php include_once __DIR__ . '/../INCLUDES/sidebar.php'; ?>

    <main class="main">
        <header class="page-header">
            <div>
                <h1 class="page-title"><i class="fas fa-chart-pie"></i> Dashboard</h1>
                <p class="page-subtitle">Bem-vindo(a), <?php echo htmlspecialchars($nome_usuario); ?>!</p>
            </div>
            <div class="header-actions">
                <span style="font-size: 13px; color: #7a8aa0; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($nome_cliente); ?>
                </span>
                <span style="background: #e3f2fd; padding: 4px 12px; border-radius: 12px; color: #0d47a1; font-weight: 500; font-size: 13px;">
                    <?php 
                    $tipos = [
                        'admin_cliente' => 'Administrador',
                        'gerente' => 'Coordenador',
                        'usuario' => 'Usuário',
                        'visualizador' => 'Visualizador'
                    ];
                    echo $tipos[$tipo_usuario] ?? $tipo_usuario; 
                    ?>
                </span>

                <!-- BOTÃO GERAR RELATÓRIO DE OCUPAÇÃO -->
                <?php if (in_array($tipo_usuario, ['admin_cliente', 'gerente'])): ?>
                    <?php if ($tipo_usuario === 'admin_cliente'): ?>
                        <!-- Para Admin: Abre o modal para escolher a unidade -->
                        <button type="button" class="btn btn-primary" onclick="abrirModalRelatorio()">
                            <i class="fas fa-file-alt"></i> Gerar Relatório de Ocupação
                        </button>
                    <?php else: ?>
                        <!-- Para Coordenador: Vai direto para a página de relatório com a unidade vinculada -->
                        <a href="../RELATORIOS/relatorio_ocupacao.php?id_unidade=<?php echo $id_unidade_sessao; ?>" class="btn btn-primary">
                            <i class="fas fa-file-alt"></i> Gerar Relatório de Ocupação
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </header>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="icon"><i class="fas fa-building"></i></span>
                <div class="number"><?php echo $stats['total_unidades'] ?? 0; ?></div>
                <div class="label">Unidades</div>
            </div>
            <div class="stat-card">
                <span class="icon"><i class="fas fa-book"></i></span>
                <div class="number"><?php echo $stats['total_cursos'] ?? 0; ?></div>
                <div class="label"> Cursos</div>
            </div>
            <div class="stat-card">
                <span class="icon"><i class="fas fa-users"></i></span>
                <div class="number"><?php echo $stats['total_funcionarios'] ?? 0; ?></div>
                <div class="label"> Funcionários</div>
            </div>
            <div class="stat-card">
                <span class="icon"><i class="fas fa-door-open"></i></span>
                <div class="number"><?php echo $stats['total_salas'] ?? 0; ?></div>
                <div class="label"> Salas</div>
            </div>
        </div>

        <!-- Menu rápido -->
        <div class="card-panel">
            <h3 style="font-size: 16px; color: #0e1a2b; margin-bottom: 12px;">
                <i class="fas fa-compass" style="color: #1a73e8;"></i> Navegação Rápida
            </h3>
            <div class="menu-grid">
                <a href="../UNIDADES/listar_unidade.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Unidades</span>
                </a>
                <a href="../CURSOS/listar_cursos.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Cursos</span>
                </a>
                <a href="../SALAS/listar_salas.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Salas</span>
                </a>
                <a href="../CRONOGRAMA_AULAS/listar_aulas.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Cronograma</span>
                </a>
                <a href="../MAPA/mapa_salas_dia.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Mapa de Salas</span>
                </a>
                <a href="../USUARIOS(ADM)/listar_usuarios.php" class="menu-item">
                    <span class="icon"></span>
                    <span class="name">Usuários</span>
                </a>
                <a href="../AUTENTIFICACAO_ACESSO/realizar_logout.php" class="menu-item" style="border-color: #dc3545;">
                    <span class="icon"></span>
                    <span class="name" style="color: #dc3545;">Sair</span>
                </a>
            </div>
        </div>

        <?php include_once __DIR__ . '/../INCLUDES/footer.php'; ?>
    </main>

    <!-- ======================================================
         MODAL DE SELEÇÃO DE UNIDADE (Apenas para Admin)
    ====================================================== -->
    <div class="modal-overlay" id="modalRelatorio">
        <div class="modal-box">
            <div class="modal-header">
                <h3><i class="fas fa-building"></i> Selecione a Unidade</h3>
                <button type="button" class="modal-close" onclick="fecharModalRelatorio()">&times;</button>
            </div>
            
            <form action="../MAPA/relatorio.php" method="GET">
                <div class="modal-form-group">
                    <label for="select_unidade">Escolha de qual unidade deseja gerar o relatório:</label>
                    <select name="id_unidade" id="select_unidade" required>
                        <option value="">-- Selecione uma unidade --</option>
                        <?php foreach ($lista_unidades as $unidade): ?>
                            <option value="<?php echo $unidade['id_unidade']; ?>">
                                <?php echo htmlspecialchars($unidade['nome_unidade']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" onclick="fecharModalRelatorio()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Gerar Relatório
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalRelatorio() {
            document.getElementById('modalRelatorio').classList.add('active');
        }

        function fecharModalRelatorio() {
            document.getElementById('modalRelatorio').classList.remove('active');
        }

        // Fechar modal ao clicar fora da caixa
        document.getElementById('modalRelatorio').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalRelatorio();
            }
        });
    </script>

</body>
</html>