<?php
session_start();
include(__DIR__ . '/.././db/dbHotel.php');

if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

// Verifica se o ID foi passado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../consultas/consulta_reservas.php");
    exit;
}

$reserva_id = $_GET['id'];

// Busca os dados da reserva no banco
$stmt = $pdo->prepare("SELECT r.*, q.numero AS numero_quarto, u.nome_completo AS nome_usuario 
                      FROM reservas r
                      JOIN quartos q ON r.quarto_id = q.id
                      JOIN usuarios u ON r.usuario_id = u.id
                      WHERE r.id = ?");
$stmt->execute([$reserva_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

// Busca os hóspedes secundários
$stmt_hospedes = $pdo->prepare("SELECT hs.*, q.numero as numero_quarto 
                               FROM hospedes_secundarios hs
                               JOIN quartos q ON hs.quarto_id = q.id
                               WHERE hs.reserva_id = ?");
$stmt_hospedes->execute([$reserva_id]);
$hospedes_secundarios = $stmt_hospedes->fetchAll(PDO::FETCH_ASSOC);

// No início do arquivo, após obter os dados da reserva atual
$checkin = $reserva['data_checkin'];
$checkout = $reserva['data_checkout'];

// Consulta para quartos não ocupados no período
$stmt_quartos_disponiveis = $pdo->prepare("
    SELECT q.id, q.numero, 
           CASE 
               WHEN q.camas_casal > 0 THEN 'Casal'
               WHEN q.beliches > 0 THEN 'Beliche'
               WHEN q.camas_solteiro > 0 THEN 'Solteiro'
               ELSE 'Indefinido'
           END AS tipo_cama,
           q.preco
    FROM quartos q
    WHERE q.id NOT IN (
        SELECT r.quarto_id
        FROM reservas r
        WHERE (
            (r.data_checkin < :checkout AND r.data_checkout > :checkin)
            AND r.status NOT IN ('cancelada', 'finalizada')
            AND r.id != :reserva_id
        )
    )
    OR q.id = :quarto_atual  -- Inclui o quarto atual da reserva
    AND q.status = 'Disponível'
    ORDER BY q.numero
");

$stmt_quartos_disponiveis->execute([
    ':checkin' => $checkin,
    ':checkout' => $checkout,
    ':reserva_id' => $reserva_id,
    ':quarto_atual' => $reserva['quarto_id']  // ID do quarto atual da reserva
]);

$quartos_disponiveis = $stmt_quartos_disponiveis->fetchAll(PDO::FETCH_ASSOC);

//print_r($quartos_disponiveis);die();

if (!$reserva) {
    header("Location: ../consultas/consulta_reservas.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $reserva_id = $_POST['reserva_id'] ?? null;
    $quarto_numero = $_POST['numero_quarto'] ?? null;
    $tipo_cama = $_POST['tipo_cama'] ?? null;
    $status = $_POST['status'] ?? null;
    $observacoes = $_POST['observacoes'] ?? null;
    $valor_total = isset($_POST['valorTotal']) ? floatval(str_replace(',', '.', $_POST['valorTotal'])) : null;
    $valor_pago = isset($_POST['valorPago']) ? floatval(str_replace(',', '.', $_POST['valorPago'])) : null;

    if (!$reserva_id || !$quarto_numero || !$tipo_cama || !$status || $valor_total === null || $valor_pago === null) {
        echo "<script>alert('Todos os campos obrigatórios devem ser preenchidos.'); window.history.back();</script>";
        exit;
    }

    $valor_restante = $valor_total - $valor_pago;

    // Verifica se o quarto existe
    $stmt = $conn->prepare("SELECT id FROM quartos WHERE numero = ?");
    $stmt->bindValue(1, $quarto_numero, PDO::PARAM_STR);
    $stmt->execute();
    $quarto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quarto) {
        echo "<script>alert('Quarto não encontrado. Por favor, verifique o número do quarto.'); window.history.back();</script>";
        exit;
    }

    $quarto_id = $quarto['id'];

    $conn->beginTransaction();

    try {
        // Atualiza a reserva
        $sql = "UPDATE reservas SET
                              quarto_id = ?, 
                              tipo_camas = ?, 
                    valor_reserva = ?, 
                              status = ?, 
                              observacoes = ?,
                              valor_pago = ?,
                              valor_restante = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $quarto_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $tipo_cama, PDO::PARAM_STR);
        $stmt->bindValue(3, $valor_total, PDO::PARAM_STR);
        $stmt->bindValue(4, $status, PDO::PARAM_STR);
        $stmt->bindValue(5, $observacoes, PDO::PARAM_STR);
        $stmt->bindValue(6, $valor_pago, PDO::PARAM_STR);
        $stmt->bindValue(7, $valor_restante, PDO::PARAM_STR);
        $stmt->bindValue(8, $reserva_id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar a reserva: " . implode(", ", $stmt->errorInfo()));
        }

        // Remove hóspedes secundários existentes
        $stmt = $conn->prepare("DELETE FROM hospedes_secundarios WHERE reserva_id = ?");
        $stmt->bindValue(1, $reserva_id, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao remover hóspedes secundários: " . implode(", ", $stmt->errorInfo()));
        }

        // Insere novos hóspedes secundários (se houver)
        if (isset($_POST['hospedes_nome']) && is_array($_POST['hospedes_nome'])) {
            $nomes = $_POST['hospedes_nome'];
            $documentos = $_POST['hospedes_documento'];
            $quartos = $_POST['hospedes_quarto'];

            $stmt = $conn->prepare("INSERT INTO hospedes_secundarios (reserva_id, nome, documento, quarto) VALUES (?, ?, ?, ?)");
            foreach ($nomes as $i => $nome) {
                $documento = $documentos[$i] ?? null;
                $quarto_secundario = $quartos[$i] ?? null;

                if (empty($nome) || empty($documento) || empty($quarto_secundario)) {
                    throw new Exception("Todos os campos dos hóspedes secundários devem ser preenchidos.");
                }

                $stmt->bindValue(1, $reserva_id, PDO::PARAM_INT);
                $stmt->bindValue(2, $nome, PDO::PARAM_STR);
                $stmt->bindValue(3, $documento, PDO::PARAM_STR);
                $stmt->bindValue(4, $quarto_secundario, PDO::PARAM_STR);
                if (!$stmt->execute()) {
                    throw new Exception("Erro ao inserir hóspede secundário: " . implode(", ", $stmt->errorInfo()));
                }
            }
        }

        // Finaliza a transação
        $conn->commit();
        echo "<script>alert('Reserva atualizada com sucesso!'); window.location.href='../reservas/listar_reservas.php';</script>";
    } catch (Exception $e) {
        // Caso haja erro, desfaz a transação
        $conn->rollback();
        error_log($e->getMessage());
        echo "<script>alert('Ocorreu um erro ao atualizar a reserva. Tente novamente mais tarde.'); window.history.back();</script>";
    } finally {
        // Libera os recursos
        unset($stmt);
        unset($conn);
    }
} else {
    echo "Requisição inválida.";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reserva</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/editar_reserva.css">
</head>

<body class="bg-light">

    <?php include("../components/navbar.php"); ?>

    <div class="container mt-5 bg-light">
        <h1 class="mb-4"><i class="bi bi-pencil-square me-2"></i>Editar Reserva</h1>
        <form action="./funcoes.php" method="POST">

            <!-- Seção de Dados do Cliente -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="fas fa-user-check me-2"></i>Dados do Cliente</h5>
                <div class="row g-3">
                    <div class="col-md-9">
                        <div class="suggestions-container">
                            <input type="hidden" id="cliente_id" name="cliente_id" value="<?= $reserva['usuario_id'] ?? '' ?>">
                            <input type="hidden" name="tabela" value="reserva">
                            <input type="hidden" name="reserva" value="<?php echo isset($reserva_id) ? htmlspecialchars($reserva_id) : ''; ?>">
                            <div class="input-group cliente-input-group">
                                <span class="input-group-text bg-success text-white">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <div id="clienteSelecionadoContainer" class="cliente-selecionado-container <?= empty($reserva['nome_usuario']) ? 'd-none' : '' ?>">
                                    <span id="nomeClienteSelecionado" class="cliente-selecionado-nome"><?= htmlspecialchars($reserva['nome_usuario'] ?? '') ?></span>
                                    <input type="hidden" id="documento_real" name="documento" value="<?= htmlspecialchars($reserva['cpf_cnpj'] ?? '') ?>">
                                </div>
                                <input type="text" class="form-control cliente-input-visual <?= !empty($reserva['nome_usuario']) ? 'd-none' : '' ?>"
                                    id="documento_visual"
                                    placeholder="Digite o CPF/CNPJ do cliente"
                                    oninput="buscarSugestoes(this.value)"
                                    value="<?= htmlspecialchars($reserva['cpf_cnpj'] ?? '') ?>">
                                <button class="btn btn-outline-secondary cliente-btn-limpar" type="button" onclick="limparCliente()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div id="suggestionsDropdown" class="suggestions-dropdown"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#cadastroCliente">
                            <i class="fas fa-user-plus me-2"></i>Novo Cliente
                        </button>
                    </div>
                </div>
            </div>

            <!-- Seção de Datas -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="fas fa-calendar-alt me-2"></i>Período da Reserva</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="check_in" class="form-label">Check-in:</label>
                        <input type="date" class="form-control bg-light" name="check_in" id="check_in"
                            value="<?= htmlspecialchars($reserva['data_checkin'] ?? '') ?>"
                            min="<?= date('Y-m-d', strtotime('-31 days')) ?>"
                            max="<?= date('Y-m-d', strtotime('+729 days')) ?>" required disabled>
                    </div>
                    <div class="col-md-6">
                        <label for="check_out" class="form-label">Check-out:</label>
                        <input type="date" class="form-control bg-light" name="check_out" id="check_out"
                            value="<?= htmlspecialchars($reserva['data_checkout'] ?? '') ?>"
                            min="<?= date('Y-m-d', strtotime('-30 days')) ?>"
                            max="<?= date('Y-m-d', strtotime('+730 days')) ?>" required disabled>
                    </div>
                </div>
            </div>

            <!-- Seção de Detalhes do Quarto -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="bi bi-house-door me-2"></i>Detalhes do Quarto</h5>
                <div class="row g-3">
                    <div class="col-md-4">
    <label for="quarto_completo" class="form-label">Quarto:</label>
    <select class="form-select" name="quarto_id" id="quarto_completo" required>
        <option value="">Selecione um quarto</option>
        <?php foreach ($quartos_disponiveis as $quarto): ?>
            <option value="<?= $quarto['id'] ?>" 
                data-numero="<?= $quarto['numero'] ?>"
                data-tipo-cama="<?= $quarto['tipo_cama'] ?>"
                data-preco="<?= $quarto['preco'] ?>"
                <?= ($quarto['id'] == $reserva['quarto_id']) ? 'selected' : '' ?>>
                Quarto <?= htmlspecialchars($quarto['numero']) ?> - 
                <?= $quarto['tipo_cama'] ?> 
                (R$ <?= number_format($quarto['preco'], 2, ',', '.') ?>)
            </option>
        <?php endforeach; ?>
    </select>
    <input type="hidden" name="numero_quarto" id="numero_quarto_hidden" value="<?= $reserva['numero_quarto'] ?>">
</div>
                    <div class="col-md-4">
                        <label for="pension" class="form-label">Tipo de Pensão:</label>
                        <select class="form-control bg-light" id="pension" name="pension" required disabled>
                            <option value="cafe">Café da Manhã</option>
                            <option value="completa">Pensão Completa</option>
                            <option value="meia">Meia Pensão</option>
                            <option value="nenhuma">Sem Pensão</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Seção de Valores -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="bi bi-cash-coin me-2"></i>Valores</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valorTotal" name="valorTotal"
                                value="<?= number_format($reserva['valor_reserva'] ?? 0, 2, ',', '.') ?>" readonly>
                        </div>
                        <small class="form-text text-muted">Valor Total</small>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valorPago" name="valorPago"
                                value="<?= number_format($reserva['valor_pago'] ?? 0, 2, ',', '.') ?>">
                        </div>
                        <small class="form-text text-muted">Valor Pago</small>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" class="form-control" id="valorRestante" name="valorRestante"
                                value="<?= number_format(($reserva['valor_reserva'] ?? 0) - ($reserva['valor_pago'] ?? 0), 2, ',', '.') ?>" readonly>
                        </div>
                        <small class="form-text text-muted">Valor Restante</small>
                    </div>
                </div>
            </div>

            <!-- Seção de Hóspedes Secundários -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="bi bi-people-fill me-2"></i>Hóspedes Secundários</h5>
                <button type="button" class="btn btn-outline-success w-100 mb-3" onclick="adicionarHospede()">
                    <i class="bi bi-plus-circle me-2"></i>Adicionar Hóspede
                </button>
                <div class="list-group mb-3" id="hospedesList">
                    <?php if (!empty($hospedes_secundarios)): ?>
                        <?php foreach ($hospedes_secundarios as $index => $hospede): ?>
                            <!-- ...renderiza hóspede... -->
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="bi bi-clipboard-check me-2"></i>Status da Reserva</h5>
                <div class="btn-group w-100" role="group" id="statusGroup">
                    <button type="button" class="btn btn-outline-success btn-status <?= ($reserva['status'] == 'confirmada') ? 'active' : '' ?>"
                        onclick="selecionarStatus('confirmada')">
                        Confirmada
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-status <?= ($reserva['status'] == 'pendente') ? 'active' : '' ?>"
                        onclick="selecionarStatus('pendente')">
                        Pendente
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-status <?= ($reserva['status'] == 'em andamento') ? 'active' : '' ?>"
                        onclick="selecionarStatus('em andamento')">
                        Em Andamento
                    </button>
                    <button type="button" class="btn btn-outline-info btn-status <?= ($reserva['status'] == 'finalizada') ? 'active' : '' ?>"
                        onclick="selecionarStatus('finalizada')">
                        Finalizada
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-status <?= ($reserva['status'] == 'cancelada') ? 'active' : '' ?>"
                        onclick="selecionarStatus('cancelada')">
                        Cancelada
                    </button>
                </div>
                <input type="hidden" name="status" id="statusInput" value="<?= $reserva['status'] ?>" required>
            </div>

            <!-- Observações -->
            <div class="card-section">
                <h5 class="text-green mb-4"><i class="bi bi-chat-left-text"></i> Observações</h5>
                <textarea class="form-control" name="observacoes" rows="3"><?= htmlspecialchars($reserva['observacoes']) ?></textarea>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="bi bi-check-circle"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <?php include("../components/footer.php"); ?>

    <!-- Bootstrap JS e dependências -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <!-- Scripts personalizados -->
    <script>
        // Variável para controlar os índices dos hóspedes
        let hospedeIndex = <?= !empty($hospedes_secundarios) ? count($hospedes_secundarios) : 1 ?>;

        // Função para adicionar novo hóspede
        function adicionarHospede() {
            const hospedesList = document.getElementById('hospedesList');

            // Cria um novo elemento de hóspede
            const novoHospede = document.createElement('div');
            novoHospede.className = 'list-group-item hospede-item';
            novoHospede.innerHTML = `
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-4">
                        <input type="text" class="form-control" 
                            name="hospedes_nome[]" 
                            placeholder="Nome completo" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <input type="text" class="form-control" 
                            name="hospedes_documento[]" 
                            placeholder="CPF/CNPJ" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-select select-quarto" 
                            name="hospedes_quarto[]" required>
                            <option value="">Selecione o quarto</option>
                            <?php foreach ($quartos_disponiveis as $quarto): ?>
                                <option value="<?= $quarto['id'] ?>">
                                    <?= htmlspecialchars($quarto['numero']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 text-center">
                        <button type="button" class="btn btn-sm btn-danger w-100" 
                            onclick="removerHospede(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            hospedesList.appendChild(novoHospede);
            hospedeIndex++;
        }

        // Função para remover hóspede
        function removerHospede(botao) {
            if (confirm('Tem certeza que deseja remover este hóspede?')) {
                const itemHospede = botao.closest('.hospede-item');
                itemHospede.remove();
            }
        }

        // Funções para manipulação de valores monetários
        function parseCurrency(value) {
            if (typeof value === 'number') return value;
            if (!value) return 0;

            return parseFloat(
                value.replace(/[^\d]/g, '')
            ) / 100;
        }

        function formatCurrency(value) {
            if (isNaN(value)) return "0,00";
            return (value / 100).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function calcularRestante() {
            const valorTotal = brToNumber(document.getElementById('valorTotal').value);
            const valorPago = brToNumber(document.getElementById('valorPago').value);
            const valorRestante = Math.max(0, valorTotal - valorPago);

            document.getElementById('valorRestante').value = formatBr(valorRestante);
        }

        // Configuração do campo monetário
        function setupCurrencyField(element) {
            element.addEventListener('input', function(e) {
                let value = this.value.replace(/[^\d]/g, '');
                this.value = formatCurrency(value);
                calcularRestante();
            });

            element.addEventListener('blur', function() {
                let value = parseCurrency(this.value) * 100;
                this.value = formatCurrency(value);
            });

            element.addEventListener('keydown', function(e) {
                if (!/[0-9]|Backspace|Delete|Tab|ArrowLeft|ArrowRight|ArrowUp|ArrowDown/.test(e.key)) {
                    e.preventDefault();
                }
            });
        }

        function initStatusButtons() {
            const statusField = document.getElementById('status');
            const statusGroup = document.getElementById('statusGroup');

            if (!statusField || !statusGroup) return;

            // Atualiza o visual dos botões
            function updateActiveButton() {
                const currentStatus = statusField.value;
                const buttons = statusGroup.querySelectorAll('button');

                buttons.forEach(button => {
                    button.classList.remove('active');
                    if (button.dataset.status === currentStatus) {
                        button.classList.add('active');
                    }
                });
            }

            // Configura os eventos de clique
            statusGroup.querySelectorAll('button').forEach(button => {
                button.addEventListener('click', function() {
                    statusField.value = this.dataset.status;
                    updateActiveButton();
                });
            });

            // Inicializa
            updateActiveButton();
        }

        // Função para o status
        function selecionarStatus(status) {
            document.getElementById('statusInput').value = status;

            // Atualiza a aparência dos botões
            document.querySelectorAll('.btn-status').forEach(btn => {
                btn.classList.remove('active');
            });

            event.target.classList.add('active');
        }

        // Cálculo dos valores
        document.getElementById('valorPago').addEventListener('input', function() {
            const valorTotal = parseFloat(document.getElementById('valorTotal').value.replace('.', '').replace(',', '.'));
            const valorPago = parseFloat(this.value.replace('.', '').replace(',', '.')) || 0;
            const restante = valorTotal - valorPago;

            document.getElementById('valorRestante').value = restante.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });

        // Formatação dos valores monetários
        document.querySelectorAll('#valorTotal, #valorPago').forEach(input => {
            input.addEventListener('blur', function() {
                const value = parseFloat(this.value.replace('.', '').replace(',', '.')) || 0;
                this.value = value.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            });
        });

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!document.getElementById('statusInput').value) {
                e.preventDefault();
                alert('Por favor, selecione um status para a reserva');
                return false;
            }
            return true;
        });

        // Inicializa os eventos quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            // Configura os campos monetários
            setupCurrencyField(document.getElementById('valorTotal'));
            setupCurrencyField(document.getElementById('valorPago'));

            // Atualiza o cálculo do valor restante quando o valor pago muda
            document.getElementById('valorPago').addEventListener('input', calcularRestante);

            // Inicializa os botões de status
            initStatusButtons();
        });

        document.getElementById('quarto_completo').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const numeroQuarto = selectedOption.getAttribute('data-numero');
    const preco = selectedOption.getAttribute('data-preco');
    
    // Atualiza o campo hidden com o número do quarto
    document.getElementById('numero_quarto_hidden').value = numeroQuarto;
    
    // Atualiza o valor total
    document.getElementById('valorTotal').value = parseFloat(preco).toFixed(2).replace('.', ',');
    calcularRestante();
});
    </script>
</body>

</html>
