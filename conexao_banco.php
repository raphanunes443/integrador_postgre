
<?php
// ============================================================
// ARQUIVO: conexao_banco.php
// FUNÇÃO: Conexão com o Supabase e funções auxiliares
// ============================================================


// ============================================================
// 1. CONFIGURAÇÕES DO BANCO DE DADOS - SUPABASE
// ============================================================

$host = 'aws-0-sa-east-1.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$username = 'postgres.zjcgawddxfxbbhtqjkof';
$password = 'SIGAPROJ123';


// ============================================================
// 2. CONEXÃO PDO - POSTGRESQL
// ============================================================

try {

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=prefer";

    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);

    // Compatibilidade com código antigo
    $pdo = $conn;

} catch (PDOException $e) {

    error_log("Erro na conexão com o Supabase: " . $e->getMessage());

    die("Não foi possível conectar ao banco de dados.");
}


// ============================================================
// 3. INICIAR SESSÃO (SE NÃO INICIADA)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ============================================================
// 4. FUNÇÕES AUXILIARES BÁSICAS E MULTI-TENANT
// ============================================================

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['id_usuario']) && !empty($_SESSION['id_usuario']);
}

/**
 * Retorna o ID do cliente atual (da sessão)
 */
function getClienteId() {
    return $_SESSION['id_cliente'] ?? null;
}

/**
 * Retorna o ID do usuário logado
 */
function getUsuarioId() {
    return $_SESSION['id_usuario'] ?? null;
}

/**
 * Retorna o tipo do usuário logado
 */
function getTipoUsuario() {
    return $_SESSION['tipo_usuario'] ?? null;
}

/**
 * Retorna o nome do cliente atual
 */
function getNomeCliente() {
    return $_SESSION['nome_cliente'] ?? null;
}

/**
 * Define uma mensagem na sessão
 */
function setMessage($tipo, $mensagem) {
    $_SESSION['mensagem'] = [
        'tipo' => $tipo,
        'mensagem' => $mensagem
    ];
}

/**
 * Recupera e remove a mensagem da sessão
 */
function getMessage() {
    if (isset($_SESSION['mensagem'])) {
        $mensagem = $_SESSION['mensagem'];
        unset($_SESSION['mensagem']);
        return $mensagem;
    }

    return null;
}

/**
 * Redireciona para uma URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Verifica se o usuário tem permissão
 */
function hasPermission($tipos_permitidos = []) {

    if (!isLoggedIn()) return false;

    $tipo = getTipoUsuario();

    if (empty($tipos_permitidos)) return true;

    return in_array($tipo, $tipos_permitidos);
}

/**
 * Verifica permissão e redireciona se não tiver
 */
function requirePermission(
    $tipos_permitidos = [],
    $url_redirect = '../AUTENTIFICACAO_ACESSO/dashboard.php'
) {

    if (!hasPermission($tipos_permitidos)) {

        setMessage(
            'error',
            'Acesso negado. Você não tem permissão para acessar esta página.'
        );

        redirect($url_redirect);
    }
}


// ============================================================
// 5. GERENCIAMENTO DE MÚLTIPLAS UNIDADES
// ============================================================

/**
 * Retorna os IDs das unidades que o usuário logado pode acessar.
 * Administradores têm acesso a todas as unidades do cliente.
 */
function getMinhasUnidades($conn) {

    $id_usuario = getUsuarioId();
    $id_cliente = getClienteId();
    $tipo = getTipoUsuario();

    if (!$id_usuario || !$id_cliente) return [];

    // Administrador: todas as unidades do cliente
    if ($tipo === 'admin_cliente') {

        $stmt = $conn->prepare("
            SELECT id_unidade
            FROM unidades
            WHERE id_cliente = ?
        ");

        $stmt->execute([$id_cliente]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Gerente e usuários comuns
    $stmt = $conn->prepare("
        SELECT id_unidade
        FROM usuarios_unidades
        WHERE id_usuario = ?
    ");

    $stmt->execute([$id_usuario]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}


/**
 * Atualiza a sessão com as unidades atuais.
 */
function atualizarSessaoUnidades($conn) {

    $unidades = getMinhasUnidades($conn);

    $_SESSION['minhas_unidades'] = $unidades;

    if (!empty($unidades)) {
        $_SESSION['usuario_unidade'] = $unidades[0];
    } else {
        $_SESSION['usuario_unidade'] = null;
    }
}


// ============================================================
// 6. OUTRAS FUNÇÕES AUXILIARES DO SISTEMA
// ============================================================

/**
 * Busca o nome de uma unidade pelo ID.
 */
function buscarNomeUnidade($conn, $id_unidade, $id_cliente) {

    try {

        $stmt = $conn->prepare("
            SELECT nome_unidade
            FROM unidades
            WHERE id_unidade = :id
            AND id_cliente = :id_cliente
        ");

        $stmt->execute([
            ':id' => $id_unidade,
            ':id_cliente' => $id_cliente
        ]);

        $nome = $stmt->fetchColumn();

        return $nome ?: 'Unidade não definida';

    } catch (PDOException $e) {

        return 'Unidade não definida';
    }
}


/**
 * Gera uma senha provisória.
 */
function gerarSenhaProvisoria($tamanho = 8) {

    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $senha = '';

    for ($i = 0; $i < $tamanho; $i++) {

        $senha .= $caracteres[
            random_int(0, strlen($caracteres) - 1)
        ];
    }

    return $senha;
}


/**
 * Formata um telefone para exibição.
 */
function formatarTelefone($telefone) {

    if (empty($telefone)) return '';

    $telefone = preg_replace('/\D/', '', $telefone);

    if (strlen($telefone) === 11) {

        return '(' . substr($telefone, 0, 2) . ') ' .
            substr($telefone, 2, 5) . '-' .
            substr($telefone, 7);

    } elseif (strlen($telefone) === 10) {

        return '(' . substr($telefone, 0, 2) . ') ' .
            substr($telefone, 2, 4) . '-' .
            substr($telefone, 6);
    }

    return $telefone;
}


/**
 * Limpa um telefone.
 */
function limparTelefone($telefone) {
    return preg_replace('/\D/', '', $telefone);
}


/**
 * Mapeia cargo para tipo de usuário no sistema.
 */
function mapearCargoParaTipo($cargo) {

    $map = [
        'admin_cliente' => 'admin_cliente',
        'administrador' => 'admin_cliente',
        'coordenador' => 'gerente',
        'gerente' => 'gerente',
        'professor' => 'usuario',
        'auxiliar' => 'usuario',
        'secretaria' => 'usuario',
        'portaria' => 'usuario'
    ];

    return $map[$cargo] ?? 'usuario';
}


/**
 * Mapeia tipo de usuário para label amigável.
 */
function getTipoLabel($tipo) {

    $map = [
        'admin_cliente' => 'Administrador',
        'gerente' => 'Coordenador',
        'usuario' => 'Professor',
        'secretaria' => 'Secretaria',
        'portaria' => 'Portaria'
    ];

    return $map[$tipo] ?? ucfirst($tipo);
}


/**
 * Mapeia tipo de usuário para classe CSS do badge.
 */
function getBadgeClass($tipo) {

    $map = [
        'admin_cliente' => 'badge-danger',
        'gerente' => 'badge-orange',
        'usuario' => 'badge-purple',
        'secretaria' => 'badge-info',
        'portaria' => 'badge-info'
    ];

    return $map[$tipo] ?? 'badge-info';
}


// ============================================================
// 7. VALIDAÇÃO DA SESSÃO E HISTÓRICO
// ============================================================

/**
 * Verifica se a sessão atual é válida.
 */
function validarSessao($conn) {

    if (!isLoggedIn()) return false;

    $id_usuario = getUsuarioId();
    $id_cliente = getClienteId();

    if (!$id_usuario || !$id_cliente) return false;

    try {

        $stmt = $conn->prepare("
            SELECT id_usuario, status_usuario
            FROM usuarios_sistema
            WHERE id_usuario = :id
            AND id_cliente = :id_cliente
        ");

        $stmt->execute([
            ':id' => $id_usuario,
            ':id_cliente' => $id_cliente
        ]);

        $usuario = $stmt->fetch();

        if (!$usuario) return false;

        if ($usuario['status_usuario'] !== 'ativo') return false;

        return true;

    } catch (PDOException $e) {

        return false;
    }
}


/**
 * Registra uma ação no histórico do sistema.
 */
function registrarHistorico(
    $conn,
    $id_funcionario,
    $tabela,
    $id_registro,
    $acao,
    $dados_antigos = null,
    $dados_novos = null,
    $motivo = null
) {

    try {

        $sql = "INSERT INTO historico_sistema (
            id_funcionario,
            tabela_afetada,
            id_registro_afetado,
            acao,
            dados_anteriores,
            dados_novos,
            motivo,
            ip_origem,
            data_acao
        ) VALUES (
            :id_funcionario,
            :tabela,
            :id_registro,
            :acao,
            :dados_antigos,
            :dados_novos,
            :motivo,
            :ip,
            NOW()
        )";

        $stmt = $conn->prepare($sql);

        return $stmt->execute([

            ':id_funcionario' => $id_funcionario,
            ':tabela' => $tabela,
            ':id_registro' => $id_registro,
            ':acao' => $acao,

            ':dados_antigos' => $dados_antigos
                ? json_encode($dados_antigos)
                : null,

            ':dados_novos' => $dados_novos
                ? json_encode($dados_novos)
                : null,

            ':motivo' => $motivo,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        ]);

    } catch (PDOException $e) {

        error_log(
            'Erro ao registrar histórico: ' . $e->getMessage()
        );

        return false;
    }
}


// ============================================================
// 8. COMPATIBILIDADE COM CÓDIGO LEGADO
// ============================================================

// Compatibilidade entre $conn e $pdo
if (!isset($pdo)) {
    $pdo = $conn;
}


// Compatibilidade de variáveis de sessão

if (isset($_SESSION['usuario_id']) && !isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = $_SESSION['usuario_id'];
}

if (isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = $_SESSION['id_usuario'];
}

if (isset($_SESSION['id_usuario']) && !isset($_SESSION['funcionario_id'])) {
    $_SESSION['funcionario_id'] = $_SESSION['id_usuario'];
}

if (isset($_SESSION['funcionario_id']) && !isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = $_SESSION['funcionario_id'];
}


// Compatibilidade com $_SESSION['usuario_cargo']

if (isset($_SESSION['tipo_usuario']) && !isset($_SESSION['usuario_cargo'])) {

    $map_cargo = [
        'admin_cliente' => 'administrador',
        'gerente' => 'coordenador',
        'usuario' => 'professor'
    ];

    $_SESSION['usuario_cargo'] =
        $map_cargo[$_SESSION['tipo_usuario']]
        ?? $_SESSION['tipo_usuario'];
}

?>
