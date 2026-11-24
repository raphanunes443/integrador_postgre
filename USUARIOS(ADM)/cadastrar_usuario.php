<?php
// ============================================================
// ARQUIVO: USUARIOS(ADM)/cadastrar_usuario.php (MODIFICADO PARA MULTI-TENANT E MULTI-UNIDADES)
// FUNÇÃO: Cadastrar novo usuário
// ============================================================

// ============================================================
// 1. INICIAR SESSÃO E CARREGAR CONEXÃO
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../conexao_banco.php';

// ============================================================
// 2. VERIFICAR LOGIN (NOVO SISTEMA)
// ============================================================

if (!isLoggedIn()) {
    setMessage('error', 'Você precisa estar logado para acessar esta página.');
    redirect('../AUTENTIFICACAO_ACESSO/realizar_login.php');
}

// ============================================================
// 3. VERIFICAR PERMISSÃO (NOVO SISTEMA)
// ============================================================

$tipos_permitidos = ['admin_cliente', 'gerente'];
if (!in_array($_SESSION['tipo_usuario'] ?? '', $tipos_permitidos)) {
    setMessage('error', 'Acesso negado. Apenas administradores e coordenadores podem cadastrar usuários.');
    redirect('../AUTENTIFICACAO_ACESSO/dashboard.php');
}

// ============================================================
// 4. VARIÁVEIS DO SISTEMA (NOVO)
// ============================================================

$id_cliente = getClienteId();
$id_usuario_logado = getUsuarioId();
$tipo_usuario = $_SESSION['tipo_usuario'] ?? '';
$id_unidade_usuario = $_SESSION['usuario_unidade'] ?? null;

// Se não tiver unidade definida, buscar a primeira unidade do cliente (para gerentes)
if ($id_unidade_usuario == 0 || $id_unidade_usuario === null) {
    try {
        $stmt = $conn->prepare("SELECT id_unidade FROM unidades WHERE id_cliente = ? ORDER BY id_unidade LIMIT 1");
        $stmt->execute([$id_cliente]);
        $unidade = $stmt->fetch();
        if ($unidade) {
            $id_unidade_usuario = $unidade['id_unidade'];
            $_SESSION['usuario_unidade'] = $id_unidade_usuario;
        }
    } catch (PDOException $e) {
        $id_unidade_usuario = 0;
    }
}

// ============================================================
// 5. BUSCAR UNIDADES PARA O SELECT (APENAS ADMIN - FILTRADAS POR CLIENTE)
// ============================================================

$unidades = [];
if ($tipo_usuario === 'admin_cliente') {
    try {
        $stmtUnidades = $conn->prepare("SELECT id_unidade, nome_unidade FROM unidades WHERE id_cliente = ? ORDER BY nome_unidade");
        $stmtUnidades->execute([$id_cliente]);
        $unidades = $stmtUnidades->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $unidades = [];
    }
}

// ============================================================
// 6. PROCESSAR CADASTRO (POST)
// ============================================================

$erro = '';
$sucesso = '';
$senha_provisoria = '';
$nome_cadastrado = '';
$email_cadastrado = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $telefone = preg_replace('/\D/', '', $telefone);
    $tipo_usuario_novo = $_POST['tipo_usuario'] ?? '';
    
    // AQUI ESTÁ A MUDANÇA: Agora recebemos um array de unidades
    $unidades_selecionadas = [];

    if ($tipo_usuario === 'gerente') {
        // Gerente só cadastra para a própria unidade
        $unidades_selecionadas = [$id_unidade_usuario];
    } else {
        // Admin pode escolher várias (vem dos checkboxes)
        $unidades_selecionadas = $_POST['unidades'] ?? [];
    }

    // Validações
    if (empty($nome) || empty($email) || empty($tipo_usuario_novo) || empty($unidades_selecionadas)) {
        $erro = 'Preencha todos os campos obrigatórios (incluindo pelo menos uma unidade).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($tipo_usuario === 'gerente' && $tipo_usuario_novo === 'admin_cliente') {
        $erro = 'Coordenadores não podem cadastrar administradores.';
    } elseif (!empty($telefone) && strlen($telefone) < 10) {
        $erro = 'Telefone inválido. Use o formato (99) 99999-9999';
    } else {
        try {
            // Verificar se o e-mail já existe para este cliente
            $check = $conn->prepare("SELECT COUNT(*) FROM usuarios_sistema WHERE email_usuario = :email AND id_cliente = :id_cliente");
            $check->execute([
                ':email' => $email,
                ':id_cliente' => $id_cliente
            ]);
            if ($check->fetchColumn() > 0) {
                $erro = 'Este e-mail já está cadastrado nesta organização.';
            } else {
                // Verificar se TODAS as unidades selecionadas pertencem ao cliente
                $unidades_validas = true;
                foreach ($unidades_selecionadas as $id_unidade_sel) {
                    $checkUnidade = $conn->prepare("SELECT COUNT(*) FROM unidades WHERE id_unidade = :unidade AND id_cliente = :id_cliente");
                    $checkUnidade->execute([
                        ':unidade' => $id_unidade_sel,
                        ':id_cliente' => $id_cliente
                    ]);
                    if ($checkUnidade->fetchColumn() == 0) {
                        $unidades_validas = false;
                        break;
                    }
                }

                if (!$unidades_validas) {
                    $erro = 'Uma ou mais unidades são inválidas ou não pertencem à sua organização.';
                } else {
                    // Gerar senha provisória (função vem de conexao_banco.php)
                    $senha_provisoria = gerarSenhaProvisoria();
                    $senha_hash = password_hash($senha_provisoria, PASSWORD_DEFAULT);

                    // Mapear cargo para tipo de usuário
                    $tipo_mapping = [
                        'admin_cliente' => 'admin_cliente',
                        'coordenador' => 'gerente',
                        'professor' => 'usuario',
                        'auxiliar' => 'usuario',
                        'gerente' => 'gerente',
                        'secretaria' => 'usuario',
                        'portaria' => 'usuario'
                    ];
                    $tipo_usuario_db = $tipo_mapping[$tipo_usuario_novo] ?? 'usuario';

                    $conn->beginTransaction();

                    // ATENÇÃO: O 'id_unidade' foi removido deste INSERT principal
                    $sqlInsert = "INSERT INTO usuarios_sistema (
                        id_cliente,
                        nome_usuario,
                        tipo_usuario,
                        email_usuario,
                        telefone_usuario,
                        senha_usuario,
                        status_usuario,
                        data_cadastro
                    ) VALUES (
                        :id_cliente,
                        :nome,
                        :tipo,
                        :email,
                        :telefone,
                        :senha,
                        'inativo',
                        NOW()
                    )";
                    $stmtInsert = $conn->prepare($sqlInsert);
                    $stmtInsert->execute([
                        ':id_cliente' => $id_cliente,
                        ':nome' => $nome,
                        ':tipo' => $tipo_usuario_db,
                        ':email' => $email,
                        ':telefone' => $telefone,
                        ':senha' => $senha_hash
                    ]);

                    $idNovoUsuario = $conn->lastInsertId();
                    $nome_cadastrado = $nome;
                    $email_cadastrado = $email;

                    // ============================================================
                    // NOVO: SALVAR OS VÍNCULOS NA TABELA usuarios_unidades
                    // ============================================================
                    $sqlVinculo = "INSERT INTO usuarios_unidades (id_usuario, id_unidade) VALUES (:id_usuario, :id_unidade)";
                    $stmtVinculo = $conn->prepare($sqlVinculo);
                    
                    foreach ($unidades_selecionadas as $id_unidade_sel) {
                        $stmtVinculo->execute([
                            ':id_usuario' => $idNovoUsuario,
                            ':id_unidade' => $id_unidade_sel
                        ]);
                    }

                    // Registrar no histórico
                    try {
                        $sqlHistorico = "INSERT INTO historico_sistema (
                            id_funcionario,
                            tabela_afetada,
                            id_registro_afetado,
                            acao,
                            dados_novos,
                            ip_origem,
                            data_acao
                        ) VALUES (
                            :id_funcionario,
                            'usuarios_sistema',
                            :id_registro,
                            'INSERT',
                            :dados,
                            :ip,
                            NOW()
                        )";
                        $stmtHistorico = $conn->prepare($sqlHistorico);
                        $stmtHistorico->execute([
                            ':id_funcionario' => $id_usuario_logado,
                            ':id_registro' => $idNovoUsuario,
                            ':dados' => json_encode([
                                'usuario' => $nome,
                                'email' => $email,
                                'tipo' => $tipo_usuario_novo,
                                'unidades' => $unidades_selecionadas
                            ]),
                            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
                        ]);
                    } catch (PDOException $e) {
                        error_log('Erro ao registrar histórico: ' . $e->getMessage());
                    }

                    $conn->commit();
                    $sucesso = 'Usuário cadastrado com sucesso!';
                }
            }
        } catch (PDOException $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            // [CORRIGIDO] Não expor detalhes do banco ao usuário.
            // A mensagem real vai apenas para o log do servidor.
            error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
            $erro = 'Erro ao cadastrar usuário. Tente novamente ou contate o suporte.';
        }
    }
}

// ============================================================
// 7. TÍTULO DA PÁGINA
// ============================================================

$titulo = 'Cadastrar Usuário - Gerenciamento de Ambientes';

// Mensagens da sessão
$message = getMessage();
if ($message && $message['tipo'] === 'error') {
    $erro = $message['mensagem'];
} elseif ($message && $message['tipo'] === 'success') {
    $sucesso = $message['mensagem'];
}

// [NOVO] Define se os campos devem manter os valores antigos (apenas em caso de erro)
$manter_valores = !empty($erro) && $_SERVER['REQUEST_METHOD'] === 'POST';
?>
<?php include_once __DIR__ . '/../INCLUDES/head.php'; ?>
<!-- [NOVO] CSS do seletor de unidades (carregado após o estilo.css do head.php) -->
<link rel="stylesheet" href="unidades_picker.css">
<?php include_once __DIR__ . '/../INCLUDES/sidebar.php'; ?>

<main class="main">
    <header class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-user-plus"></i> Cadastrar Usuário</h1>
            <p class="page-subtitle">Preencha os dados para cadastrar um novo usuário</p>
        </div>
        <div style="font-size: 13px; color: #7a8aa0;">
            <i class="fas fa-building"></i> <?php echo htmlspecialchars($_SESSION['nome_cliente'] ?? ''); ?>
        </div>
    </header>

    <?php if ($erro): ?>
        <!-- [CORRIGIDO] Removido htmlspecialchars() para permitir HTML (ex.: <strong>) vindo das mensagens -->
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?></div>
    <?php endif; ?>
    
    <?php if ($sucesso): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
        </div>
    <?php endif; ?>

    <!-- [NOVO] Bloco da senha provisória movido para cá (entre o alerta e o formulário) -->
    <?php if ($senha_provisoria): ?>
        <div style="margin-bottom: 24px; padding: 16px 20px; background: #fff8e1; border-left: 6px solid #ff9800; border-radius: 8px; box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);">
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <i class="fas fa-key" style="font-size: 28px; color: #ff9800;"></i>
                <div>
                    <strong style="font-size: 15px; color: #e65100;">Senha provisória:</strong>
                    <span style="display: inline-block; margin-left: 12px; background: #ffffff; padding: 6px 18px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 22px; font-weight: 700; letter-spacing: 2px; color: #1a237e; border: 2px dashed #ff9800;">
                        <?php echo $senha_provisoria; ?>
                    </span>
                </div>
            </div>
            <p style="margin-top: 10px; margin-bottom: 0; color: #5a6a7e; font-size: 14px;">
                <i class="fas fa-info-circle" style="color: #ff9800;"></i> 
                Entregue esta senha ao usuário. Ele deve alterá-la no primeiro acesso.
            </p>
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 14px;">
                <button onclick="copiarCredenciais()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-copy"></i> Copiar Credenciais
                </button>
            </div>
        </div>

        <script>
            function copiarCredenciais() {
                const texto = 
                    'Usuário: <?php echo htmlspecialchars($nome_cadastrado); ?>\n' +
                    'E-mail: <?php echo htmlspecialchars($email_cadastrado); ?>\n' +
                    'Senha provisória: <?php echo $senha_provisoria; ?>\n\n' +
                    'Instruções:\n' +
                    '1. Acesse o sistema com seu e-mail e esta senha.\n' +
                    '2. No primeiro acesso, altere sua senha.';
                
                navigator.clipboard.writeText(texto).then(function() {
                    const btn = document.querySelector('button[onclick="copiarCredenciais()"]');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                    }, 2000);
                }).catch(function() {
                    alert('Copie manualmente as informações abaixo:\n\n' + texto);
                });
            }
        </script>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- FORMULÁRIO DE CADASTRO                     -->
    <!-- ========================================== -->
    <div class="card-panel">
        <form method="POST" action="">
            <input type="hidden" name="id_cliente" value="<?php echo $id_cliente; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <!-- [CORRIGIDO] value só é preenchido se for erro -->
                    <input type="text" name="nome" id="nome" value="<?php echo $manter_valores ? htmlspecialchars($_POST['nome'] ?? '') : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <!-- [CORRIGIDO] value só é preenchido se for erro -->
                    <input type="email" name="email" id="email" value="<?php echo $manter_valores ? htmlspecialchars($_POST['email'] ?? '') : ''; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <!-- [CORRIGIDO] value só é preenchido se for erro -->
                    <input type="text" name="telefone" id="telefone" 
                           value="<?php echo $manter_valores ? htmlspecialchars(formatarTelefone($_POST['telefone'] ?? '')) : ''; ?>" 
                           placeholder="(99) 99999-9999"
                           maxlength="17">
                </div>
                <div class="form-group">
                    <label for="tipo_usuario">Cargo *</label>
                    <select name="tipo_usuario" id="tipo_usuario" required>
                        <option value="">Selecione o cargo</option>
                        
                        <?php if ($tipo_usuario === 'admin_cliente'): ?>
                            <!-- [CORRIGIDO] selected só é aplicado se for erro -->
                            <option value="admin_cliente" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'admin_cliente') ? 'selected' : ''; ?>>Administrador</option>
                        <?php endif; ?>
                        
                        <option value="coordenador" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'coordenador') ? 'selected' : ''; ?>>Coordenador</option>
                        <option value="professor" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'professor') ? 'selected' : ''; ?>>Professor</option>
                        <option value="auxiliar" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'auxiliar') ? 'selected' : ''; ?>>Auxiliar</option>
                        <option value="gerente" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'gerente') ? 'selected' : ''; ?>>Gerente</option>
                        <option value="secretaria" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'secretaria') ? 'selected' : ''; ?>>Secretaria</option>
                        <option value="portaria" <?php echo ($manter_valores && ($_POST['tipo_usuario'] ?? '') === 'portaria') ? 'selected' : ''; ?>>Portaria</option>
                    </select>
                    
                    <?php if ($tipo_usuario === 'gerente'): ?>
                        <small style="color: #7a8aa0;"><i class="fas fa-info-circle"></i> Coordenadores não podem cadastrar administradores.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="width: 100%;">
                    <label for="unidade">Unidade(s) *</label>
                    
                    <?php if ($tipo_usuario === 'admin_cliente'): ?>
                        <?php
                            // Sanitiza a seleção atual (só se for erro)
                            $unidades_selecionadas_post = $manter_valores ? array_map('intval', $_POST['unidades'] ?? []) : [];
                        ?>
                        <!-- [NOVO] Seletor de unidades com busca -->
                        <div class="unidades-picker" id="unidadesPicker">
                            <div class="unidades-picker-search">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <input
                                    type="text"
                                    id="unidadesBusca"
                                    class="unidades-picker-input"
                                    placeholder="Buscar unidade..."
                                    autocomplete="off"
                                    aria-label="Buscar unidade"
                                >
                                <button
                                    type="button"
                                    class="unidades-picker-clear"
                                    id="unidadesBuscaLimpar"
                                    aria-label="Limpar busca"
                                    hidden
                                >
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="unidades-picker-list" id="unidadesLista" role="group" aria-label="Unidades disponíveis">
                                <?php if (empty($unidades)): ?>
                                    <div class="unidades-picker-empty">
                                        <i class="fas fa-building" aria-hidden="true"></i>
                                        <span>Nenhuma unidade cadastrada.</span>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($unidades as $unidade): ?>
                                        <?php
                                            $id = (int)$unidade['id_unidade'];
                                            $checked = in_array($id, $unidades_selecionadas_post, true) ? 'checked' : '';
                                        ?>
                                        <label class="unidade-item" data-nome="<?php echo htmlspecialchars(mb_strtolower($unidade['nome_unidade'])); ?>">
                                            <input type="checkbox" name="unidades[]" value="<?php echo $id; ?>" <?php echo $checked; ?>>
                                            <span class="unidade-item-check" aria-hidden="true">
                                                <i class="fas fa-check"></i>
                                            </span>
                                            <span class="unidade-item-nome"><?php echo htmlspecialchars($unidade['nome_unidade']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                
                                <!-- [CORRIGIDO] Mensagem fora do foreach para o JS controlar sempre via display -->
                                <div class="unidades-picker-empty" id="unidadesSemResultado" style="display: none;">
                                    <i class="fas fa-search" aria-hidden="true"></i>
                                    <span>Nenhuma unidade encontrada para esta busca.</span>
                                </div>
                            </div>

                            <div class="unidades-picker-footer">
                                <span class="unidades-picker-count" id="unidadesCount">0 selecionadas</span>
                                <button type="button" class="unidades-picker-toggle" id="unidadesToggleTodas">
                                    Selecionar todas
                                </button>
                            </div>
                        </div>
                        <small class="unidades-picker-hint">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            O administrador pode gerenciar múltiplas unidades.
                        </small>
                    <?php else: ?>
                        <?php $nomeUnidadeCoordenador = buscarNomeUnidade($conn, $id_unidade_usuario, $id_cliente); ?>
                        <input type="hidden" name="unidades[]" value="<?php echo $id_unidade_usuario; ?>">
                        <input type="text" 
                               value="<?php echo htmlspecialchars($nomeUnidadeCoordenador); ?>" 
                               disabled
                               style="background: #f0f4fb; color: #5a6a7e; padding: 8px 12px; border: 1px solid #e2e9f3; border-radius: 6px; width: 100%;">
                        <small style="color: #7a8aa0;"><i class="fas fa-info-circle"></i> Unidade definida automaticamente.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 16px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Cadastrar Usuário</button>
                <a href="listar_usuarios.php" class="btn btn-outline">Cancelar</a>
            </div>
        </form>
    </div>

    <?php include_once __DIR__ . '/../INCLUDES/footer.php'; ?>
</main>

<!-- ========================================== -->
<!-- SCRIPT DA MÁSCARA DE TELEFONE (JavaScript Puro) -->
<!-- ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var telefoneInput = document.getElementById('telefone');
    
    if (telefoneInput) {
        telefoneInput.addEventListener('input', function(e) {
            var value = this.value.replace(/\D/g, '');
            var formatted = '';
            
            if (value.length > 0) {
                if (value.length <= 2) {
                    formatted = '(' + value;
                } else if (value.length <= 6) {
                    formatted = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                } else {
                    formatted = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
                }
                
                if (value.length >= 11) {
                    formatted = formatted.substring(0, 17);
                }
            }
            
            this.value = formatted;
        });
        
        telefoneInput.addEventListener('blur', function() {
            var numeros = this.value.replace(/\D/g, '');
            if (numeros.length > 0 && numeros.length < 10) {
                this.style.borderColor = '#ff6b6b';
                this.style.borderWidth = '2px';
                this.style.borderStyle = 'solid';
            } else {
                this.style.borderColor = '';
                this.style.borderWidth = '';
                this.style.borderStyle = '';
            }
        });
        
        telefoneInput.addEventListener('focus', function() {
            this.style.borderColor = '';
            this.style.borderWidth = '';
            this.style.borderStyle = '';
        });
    }
});
</script>

<!-- ========================================== -->
<!-- SCRIPT DO SELETOR DE UNIDADES (DEFINITIVO) -->
<!-- ========================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('unidadesPicker');
    if (!picker) return; // Se não for admin, não tem o seletor

    const busca        = document.getElementById('unidadesBusca');
    const buscaLimpar  = document.getElementById('unidadesBuscaLimpar');
    const count        = document.getElementById('unidadesCount');
    const toggleTodas  = document.getElementById('unidadesToggleTodas');
    const semResultado = document.getElementById('unidadesSemResultado');

    // Pega os itens APENAS os que são labels (ignora a div de "sem resultado")
    const itens = Array.from(picker.querySelectorAll('.unidade-item'));

    /* ---------- Contador ---------- */
    function atualizarContador() {
        let total = 0;
        itens.forEach(function (item) {
            const cb = item.querySelector('input[type="checkbox"]');
            if (cb && cb.checked) total++;
        });

        if (!count) return;

        if (total === 0) {
            count.textContent = 'Nenhuma selecionada';
            count.classList.add('is-empty');
        } else if (total === 1) {
            count.textContent = '1 unidade selecionada';
            count.classList.remove('is-empty');
        } else {
            count.textContent = total + ' unidades selecionadas';
            count.classList.remove('is-empty');
        }

        /* ---------- Texto do botão "selecionar todas" ---------- */
        if (toggleTodas && itens.length > 0) {
            const visiveis = itens.filter(function (item) {
                return item.style.display !== 'none';
            });
            const todasMarcadas = visiveis.length > 0 && visiveis.every(function (item) {
                const cb = item.querySelector('input[type="checkbox"]');
                return cb && cb.checked;
            });
            toggleTodas.textContent = todasMarcadas ? 'Limpar seleção' : 'Selecionar todas';
        }
    }

    /* ---------- Filtro de busca ---------- */
    function filtrarLista() {
        if (!busca) return;

        // [NOVO] Normaliza o termo digitado (remove acentos e deixa minúsculo)
        const termo = busca.value.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        let visiveis = 0;

        itens.forEach(function (item) {
            // [NOVO] Normaliza o nome da unidade da mesma forma
            const nomeOriginal = (item.dataset.nome || '').toLowerCase();
            const nomeNormalizado = nomeOriginal.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            
            // Compara os dois textos sem acento
            const corresponde = termo === '' || nomeNormalizado.includes(termo);
            
            // Força o display none via JS (vence o CSS display:flex)
            if (corresponde) {
                item.style.display = ''; 
                visiveis++;
            } else {
                item.style.display = 'none';
            }
        });

        // Mostra "Nenhuma encontrada" apenas se tiver termo e 0 visíveis
        if (semResultado) {
            if (termo !== '' && visiveis === 0 && itens.length > 0) {
                semResultado.style.display = 'flex'; // Força exibição
            } else {
                semResultado.style.display = 'none'; // Esconde
            }
        }

        if (buscaLimpar) {
            buscaLimpar.hidden = (termo === '');
        }

        atualizarContador();
    }

    /* ---------- Eventos ---------- */
    if (busca) {
        // O evento 'input' dispara a cada letra digitada
        busca.addEventListener('input', filtrarLista);
        
        // Impede que apertar Enter envie o formulário sem querer
        busca.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Não envia o form
                filtrarLista(); // Força a busca (por segurança)
            }
        });
    }

    if (buscaLimpar) {
        buscaLimpar.addEventListener('click', function () {
            busca.value = '';
            filtrarLista();
            busca.focus();
        });
    }

    itens.forEach(function (item) {
        const cb = item.querySelector('input[type="checkbox"]');
        if (cb) {
            cb.addEventListener('change', atualizarContador);
        }
    });

    if (toggleTodas) {
        toggleTodas.addEventListener('click', function () {
            /* Alterna apenas os itens visíveis (respeita a busca) */
            const visiveis = itens.filter(function (item) {
                return item.style.display !== 'none';
            });
            const todasMarcadas = visiveis.length > 0 && visiveis.every(function (item) {
                const cb = item.querySelector('input[type="checkbox"]');
                return cb && cb.checked;
            });

            visiveis.forEach(function (item) {
                const cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = !todasMarcadas;
            });

            atualizarContador();
        });
    }

    /* ---------- Estado inicial ---------- */
    filtrarLista(); // Chama o filtro ao carregar para esconder o "Nenhum encontrado"
    if (buscaLimpar) buscaLimpar.hidden = true;
});
</script>

</body>
</html>