<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '.././db/dbHotel.php';
include '../components/avaliacao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

$usuarioId = $_SESSION['id'];

// Array para armazenar todas as informações
$minhasReservas = [];

// Query principal das reservas
$where = ["r.usuario_id = :usuarioId"];
$params = [':usuarioId' => $usuarioId];

if (!empty($_GET['status'])) {
    $where[] = "r.status = :status";
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['checkin_de'])) {
    $where[] = "r.data_checkin >= :checkin_de";
    $params[':checkin_de'] = $_GET['checkin_de'];
}
if (!empty($_GET['checkin_ate'])) {
    $where[] = "r.data_checkin <= :checkin_ate";
    $params[':checkin_ate'] = $_GET['checkin_ate'];
}
if (!empty($_GET['valor_min'])) {
    $where[] = "r.valor_reserva >= :valor_min";
    $params[':valor_min'] = $_GET['valor_min'];
}
if (!empty($_GET['valor_max'])) {
    $where[] = "r.valor_reserva <= :valor_max";
    $params[':valor_max'] = $_GET['valor_max'];
}

$sqlReservas = "SELECT 
    r.id AS ReservaId, 
    q.numero AS QuartoNumero,
    (q.camas_solteiro + q.beliches * 2 + q.camas_casal * 2) AS capacidade,
    (SELECT COUNT(*) FROM hospedes_secundarios WHERE reserva_id = r.id) + 1 AS qtd_hospedes,
    u.nome_completo, 
    r.valor_reserva AS Preco, 
    r.data_checkin, 
    r.data_checkout, 
    r.status,
    r.observacoes,
    r.forma_pagamento,
    p.parcelas
FROM reservas r 
LEFT JOIN quartos q ON r.quarto_id = q.id 
LEFT JOIN usuarios u ON r.usuario_id = u.id
LEFT JOIN pagamentos p ON p.reserva_id = r.id
WHERE " . implode(' AND ', $where);

try {
    // Busca as reservas principais
    $stmt = $pdo->prepare($sqlReservas);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada reserva, buscar os hóspedes secundários
    foreach ($reservas as &$reserva) {
        $reservaId = $reserva['ReservaId'];

        // Buscar hóspedes secundários
        $stmtHospedes = $pdo->prepare("SELECT nome, documento FROM hospedes_secundarios WHERE reserva_id = :reservaId");
        $stmtHospedes->bindValue(':reservaId', $reservaId);
        $stmtHospedes->execute();
        $reserva['hospedes_secundarios'] = $stmtHospedes->fetchAll(PDO::FETCH_ASSOC);

        // Garantir que é um array
        if (!is_array($reserva['hospedes_secundarios'])) {
            $reserva['hospedes_secundarios'] = [];
        }

        // Buscar avaliação se existir
        $sqlAvaliacao = "SELECT nota, comentario 
                        FROM avaliacoes 
                        WHERE reserva_id = :reservaId";
        $stmtAvaliacao = $pdo->prepare($sqlAvaliacao);
        $stmtAvaliacao->bindValue(':reservaId', $reservaId);
        $stmtAvaliacao->execute();
        $reserva['avaliacao'] = $stmtAvaliacao->fetch(PDO::FETCH_ASSOC);
    }

    $minhasReservas = $reservas;

    // Fecha a sessão para evitar interferências
    session_write_close();
} catch (PDOException $e) {
    die("Erro ao buscar reservas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Reservas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../css/minhas_reservas_cliente.css">
</head>

<body class="bg-light">
    <?php include("../components/navbar.php"); ?>

    <main class="flex-grow-1 mt-5 bg-light">
        <div class="container py-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 fw-bold text-success">
                    <i class="fas fa-calendar-alt me-2"></i>Minhas Reservas
                </h1>
                <a href="./reserva_quartos_cliente.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Nova Reserva
                </a>
            </div>

            <form method="get" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="confirmada" <?= (isset($_GET['status']) && $_GET['status'] == 'confirmada') ? 'selected' : '' ?>>Confirmada</option>
                        <option value="pendente" <?= (isset($_GET['status']) && $_GET['status'] == 'pendente') ? 'selected' : '' ?>>Pendente</option>
                        <option value="em andamento" <?= (isset($_GET['status']) && $_GET['status'] == 'em andamento') ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="finalizada" <?= (isset($_GET['status']) && $_GET['status'] == 'finalizada') ? 'selected' : '' ?>>Finalizada</option>
                        <option value="cancelada" <?= (isset($_GET['status']) && $_GET['status'] == 'cancelada') ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check-in (de)</label>
                    <input type="date" name="checkin_de" class="form-control" value="<?= htmlspecialchars($_GET['checkin_de'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Check-in (até)</label>
                    <input type="date" name="checkin_ate" class="form-control" value="<?= htmlspecialchars($_GET['checkin_ate'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Valor mínimo</label>
                    <input type="number" step="0.01" name="valor_min" class="form-control" value="<?= htmlspecialchars($_GET['valor_min'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Valor máximo</label>
                    <input type="number" step="0.01" name="valor_max" class="form-control" value="<?= htmlspecialchars($_GET['valor_max'] ?? '') ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">Filtrar</button>
                </div>
            </form>

            <?php if (count($minhasReservas) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($minhasReservas as $reserva): ?>
                        <?php
                        $statusClass = match (strtolower(trim($reserva['status']))) {
                            'confirmada' => 'badge-sucesso',
                            'pendente' => 'badge-pendente',
                            'em andamento' => 'badge-primario',
                            'finalizada' => 'badge-info',
                            'cancelada' => 'badge-perigo',
                            default => 'badge-secundario'
                        };
                        ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="reserva-card h-100" data-reserva-id="<?= $reserva['ReservaId'] ?>"
                                data-quarto-numero="<?= htmlspecialchars($reserva['QuartoNumero'], ENT_QUOTES) ?>"
                                data-checkin="<?= $reserva['data_checkin'] ?>" data-checkout="<?= $reserva['data_checkout'] ?>"
                                data-preco="<?= $reserva['Preco'] ?>" data-status="<?= $reserva['status'] ?>"
                                data-capacidade="<?= $reserva['capacidade'] ?>"
                                data-qtd-hospedes="<?= $reserva['qtd_hospedes'] ?>"
                                data-hospedes='<?= isset($reserva['hospedes_secundarios']) ? htmlspecialchars(json_encode($reserva['hospedes_secundarios']), ENT_QUOTES, 'UTF-8') : '[]' ?>'
                                data-pagamento="<?= $reserva['forma_pagamento'] ?>" data-parcelas="<?= $reserva['parcelas'] ?>"
                                data-observacoes='<?= addslashes($reserva['observacoes']) ?>' onclick="abrirModalEdicao(this)">
                                <img src="../uploads/quarto-<?= htmlspecialchars($reserva['QuartoNumero']) ?>.jpg"
                                    class="card-img-top" alt="Quarto <?= htmlspecialchars($reserva['QuartoNumero']) ?>"
                                    onerror="this.onerror=null; this.src='../uploads/1740695620_teste.jpeg';">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h2 class="h5 mb-0">
                                            Quarto #<?= htmlspecialchars($reserva['QuartoNumero']) ?>
                                        </h2>
                                        <button class="status-badge text-white <?= $statusClass ?>" type="button"
                                            onclick="event.stopPropagation(); gerenciarReserva('<?= $reserva['status'] ?>', <?= $reserva['ReservaId'] ?>)">
                                            <?= htmlspecialchars($reserva['status']) ?>
                                        </button>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-wallet info-icon"></i>
                                            <span class="fw-bold">Valor Total:</span>
                                            <span class="ms-auto">R$ <?= number_format($reserva['Preco'], 2, ',', '.') ?></span>
                                        </div>

                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-sign-in-alt info-icon"></i>
                                            <span>Check-in:</span>
                                            <span
                                                class="ms-auto"><?= date('d/m/Y', strtotime($reserva['data_checkin'])) ?></span>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-sign-out-alt info-icon"></i>
                                            <span>Check-out:</span>
                                            <span
                                                class="ms-auto"><?= date('d/m/Y', strtotime($reserva['data_checkout'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="border-top pt-3">
                                        <div class="d-flex align-items-center text-muted small">
                                            <i class="fas fa-user-circle me-2"></i>
                                            <?= htmlspecialchars($reserva['nome_completo']) ?>
                                            <?php if (!empty($reserva['avaliacao'])): ?>
                                                <button class="btn btn-sm btn-warning btn-icon ms-2" disabled>
                                                    <i class="fas fa-star"></i> Avaliado
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-outline-warning btn-icon ms-2"
                                                    onclick="event.stopPropagation(); abrirModalAvaliacao(<?= $reserva['ReservaId'] ?>)">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times empty-state-icon"></i>
                    <h2 class="h4 mb-3">Nenhuma reserva encontrada</h2>
                    <p class="text-muted mb-4">Parece que você ainda não fez nenhuma reserva.</p>
                    <a href="./reserva_quartos_cliente.php" class="btn btn-success btn-lg">
                        <i class="fas fa-plus me-2"></i>Fazer Primeira Reserva
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Edição de Reserva -->
    <div class="modal fade" id="editarReservaModal" tabindex="-1" aria-labelledby="editarReservaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form method="POST" action="./funcoes.php">
                <input type="hidden" name="reserva_id" id="modal_quarto_id">
                <input type="hidden" name="valor_total" id="hidden-valor-total">
                <input type="hidden" name="valor_parcela" id="hidden-valor-parcela">
                <input type="hidden" name="acao" value="update">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h3 class="modal-title fw-bold mb-0"><i class="fas fa-edit me-2"></i>Editar Minha Reserva</h3>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="position-relative rounded-4 overflow-hidden shadow-sm">
                                    <img id="modal-img" src="" class="img-fluid rounded-top-3 w-100" style="height: 250px; object-fit: cover;" alt="Imagem do Quarto">
                                    <div class="position-absolute bottom-0 start-0 w-100 p-3 bg-dark bg-opacity-75">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="text-white mb-0">Quarto #<span id="modal-quarto-numero"></span></h5>
                                            <span class="badge bg-success fs-6">R$ <span id="modal-quarto-preco"></span>/noite</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card border-success h-100">
                                    <div class="card-header bg-success text-white py-3">
                                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Informações da Reserva</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-users text-success me-2"></i>
                                            <span id="modal-quarto-capacidade"></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="fas fa-calendar-check text-success me-2"></i>
                                            <span id="modal-periodo-reserva"></span>
                                        </div>
                                        <hr>
                                        <h6 class="fw-bold text-success mb-3">Status:</h6>
                                        <div class="d-flex align-items-center">
                                            <span class="badge" id="modal-status-reserva"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-success fw-bold">Data de Check-in</label>
                                <input type="date" class="form-control border-success"
                                    id="modal-check-in"
                                    readonly
                                    onfocus="this.blur()">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-success fw-bold">Data de Check-out</label>
                                <input type="date" class="form-control border-success"
                                    id="modal-check-out"
                                    readonly
                                    onfocus="this.blur()">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Valor Total</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white">R$</span>
                                    <input type="text" class="form-control fw-bold border-success"
                                        id="modal-valor-total"
                                        onchange="document.getElementById('hidden-valor-total').value = this.value"
                                        required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-success fw-bold">Forma de Pagamento</label>
                                <select class="form-select border-success" name="pagamento" id="modal-pagamento">
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="pix">PIX</option>
                                    <option value="credito">Cartão de Crédito</option>
                                    <option value="debito">Cartão de Débito</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div id="modal-parcelas-div" style="display: none;">
                                    <label class="form-label text-success fw-bold">Parcelas</label>
                                    <input type="number" class="form-control fw-bold border-success" name="parcelas" id="modal-parcelas" min="1" max="12" value="1">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-success mb-4" id="modal-valor-parcela-div" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="modal-valor-parcela-text"></span>
                        </div>

                        <div class="mb-4" id="hospedes-secundarios-section" style="display: none;">
                            <h5 class="fw-bold text-success mb-3">
                                <i class="fas fa-user-friends me-2"></i>
                                <span>Hóspedes Secundários</span>
                                <small class="text-muted ms-2" id="hospedes-contador"></small>
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nome Completo</th>
                                            <th>Documento</th>
                                            <th width="50px">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hospedes-container">
                                        <!-- Linhas serão adicionadas dinamicamente -->
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-sm btn-success mt-2" id="adicionar-hospede-btn">
                                <i class="fas fa-plus me-1"></i> Adicionar Hóspede
                            </button>
                            <div class="invalid-feedback d-block" id="hospedes-erro"></div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-success fw-bold">Observações</label>
                            <textarea class="form-control border-success" name="observacoes" id="modal-observacoes" rows="3"></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-danger btn-lg py-3" id="modal-cancelar-reserva">
                                <i class="fas fa-times-circle me-2"></i>Cancelar Reserva
                            </button>
                            <button type="submit" class="btn btn-success btn-lg py-3">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include("../components/footer.php"); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variáveis globais
            let reservaAtual = null;
            let hospedesAtuais = 0;
            let capacidadeQuarto = 0;

            // Inicialização dos cards de reserva
            document.querySelectorAll('.reserva-card').forEach(card => {
                card.addEventListener('click', function() {
                    abrirModalEdicao(this);
                });
            });

            // Configuração do formulário do modal
            const formModal = document.querySelector('#editarReservaModal form');
            if (formModal) {
                formModal.addEventListener('submit', function(e) {
                    // Validação do parcelamento para cartão de crédito
                    if (document.getElementById('modal-pagamento').value === 'credito') {
                        const valorParcela = parseFloat(document.getElementById('hidden-valor-parcela').value);
                        const numParcelas = parseInt(document.getElementById('modal-parcelas').value);

                        if (isNaN(valorParcela) || isNaN(numParcelas) || numParcelas < 1 || numParcelas > 12) {
                            e.preventDefault();
                            alert('Configuração de parcelamento inválida');
                            return;
                        }
                    }

                    // Validação dos nomes dos hóspedes
                    const nomesInvalidos = Array.from(document.querySelectorAll('.hospede-nome'))
                        .some(input => !input.value.trim());

                    if (nomesInvalidos) {
                        e.preventDefault();
                        alert('Todos os hóspedes devem ter um nome válido');
                        return;
                    }
                });
            }

            // Função para abrir o modal de edição
            function abrirModalEdicao(elemento) {
                if (!elemento) return;

                let hospedesData = elemento.dataset.hospedes;
                let hospedes = [];

                try {
                    hospedes = JSON.parse(hospedesData || '[]');
                } catch (e) {
                    console.error('Erro ao parsear dados dos hóspedes:', e);
                    hospedes = [];
                }

                // Obter dados da reserva do elemento
                reservaAtual = {
                    id: elemento.dataset.reservaId,
                    quartoNumero: elemento.dataset.quartoNumero,
                    checkin: elemento.dataset.checkin,
                    checkout: elemento.dataset.checkout,
                    preco: parseFloat(elemento.dataset.preco),
                    status: elemento.dataset.status,
                    capacidade: parseInt(elemento.dataset.capacidade),
                    hospedes: hospedes,
                    pagamento: elemento.dataset.pagamento,
                    parcelas: parseInt(elemento.dataset.parcelas || '1'),
                    observacoes: elemento.dataset.observacoes
                };

                // Configurar variáveis globais
                capacidadeQuarto = reservaAtual.capacidade;
                hospedesAtuais = parseInt(elemento.dataset.qtdHospedes) - 1; // Subtrai o hóspede principal

                // Preencher informações básicas
                document.getElementById('modal_quarto_id').value = reservaAtual.id;
                document.getElementById('modal-quarto-numero').textContent = reservaAtual.quartoNumero;
                document.getElementById('modal-quarto-capacidade').textContent = `Capacidade: ${reservaAtual.capacidade} pessoas`;

                // Formatar datas
                const checkinFormatado = formatarData(reservaAtual.checkin);
                const checkoutFormatado = formatarData(reservaAtual.checkout);
                document.getElementById('modal-periodo-reserva').textContent =
                    `${checkinFormatado} a ${checkoutFormatado}`;

                // Configurar status com badge colorido
                const statusBadge = document.getElementById('modal-status-reserva');
                if (statusBadge) {
                    statusBadge.textContent = reservaAtual.status;
                    statusBadge.className = 'badge ' + getStatusClass(reservaAtual.status);
                }

                // Configurar imagem do quarto
                const imgElement = document.getElementById('modal-img');
                if (imgElement) {
                    imgElement.src = `../uploads/quarto-${reservaAtual.quartoNumero}.jpg`;
                    imgElement.onerror = function() {
                        this.src = '../uploads/1740695620_teste.jpeg';
                    };
                }

                // Configurar datas (campos bloqueados)
                const checkinInput = document.getElementById('modal-check-in');
                const checkoutInput = document.getElementById('modal-check-out');

                if (checkinInput && checkoutInput) {
                    checkinInput.value = reservaAtual.checkin;
                    checkoutInput.value = reservaAtual.checkout;

                    // Bloquear edição dos campos de data
                    checkinInput.readOnly = true;
                    checkoutInput.readOnly = true;
                    checkinInput.style.backgroundColor = '#f8f9fa';
                    checkoutInput.style.backgroundColor = '#f8f9fa';
                    checkinInput.style.cursor = 'not-allowed';
                    checkoutInput.style.cursor = 'not-allowed';
                }

                // Preço e pagamento
                const valorTotal = reservaAtual.preco.toFixed(2);
                const valorTotalInput = document.getElementById('modal-valor-total');
                if (valorTotalInput) {
                    valorTotalInput.value =
                        reservaAtual.preco.toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        });
                    document.getElementById('hidden-valor-total').value = valorTotal;
                }

                // Calcular preço por noite
                const diffTime = Math.abs(new Date(reservaAtual.checkout) - new Date(reservaAtual.checkin));
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const precoNoite = reservaAtual.preco / diffDays;
                const precoNoiteElement = document.getElementById('modal-quarto-preco');
                if (precoNoiteElement) {
                    precoNoiteElement.textContent = precoNoite.toFixed(2).replace('.', ',');
                }

                // Configurar pagamento
                const selectPagamento = document.getElementById('modal-pagamento');
                if (selectPagamento) {
                    selectPagamento.value = reservaAtual.pagamento || 'dinheiro';
                    configurarParcelamento(reservaAtual.preco, reservaAtual.parcelas);
                }

                // Configurar seção de hóspedes secundários
                const secaoHospedes = document.getElementById('hospedes-secundarios-section');
                if (secaoHospedes) {
                    secaoHospedes.style.display = capacidadeQuarto > 1 ? 'block' : 'none';
                }

                // Observações
                const observacoesElement = document.getElementById('modal-observacoes');
                if (observacoesElement) {
                    observacoesElement.value = reservaAtual.observacoes || '';
                }

                // Mostrar o modal
                const modalElement = document.getElementById('editarReservaModal');
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);

                    // Configurar eventos após o modal ser exibido
                    modalElement.addEventListener('shown.bs.modal', function() {
                        configurarSistemaHospedes();
                        if (reservaAtual.hospedes.length > 0) {
                            carregarHospedesExistentes(reservaAtual.hospedes);
                        }
                        atualizarContadorHospedes();
                    });

                    modal.show();
                }
            }

            // Função auxiliar para formatar data
            function formatarData(dataString) {
                const data = new Date(dataString);
                return data.toLocaleDateString('pt-BR');
            }

            // Função auxiliar para obter classe CSS do status
            function getStatusClass(status) {
                switch (status.toLowerCase()) {
                    case 'confirmada':
                        return 'bg-success';
                    case 'pendente':
                        return 'bg-warning';
                    case 'em andamento':
                        return 'bg-primary';
                    case 'finalizada':
                        return 'bg-info';
                    case 'cancelada':
                        return 'bg-danger';
                    default:
                        return 'bg-secondary';
                }
            }

            // Configurar sistema de hóspedes
            function configurarSistemaHospedes() {
                const modal = document.getElementById('editarReservaModal');
                if (!modal) return;

                const btnAdicionar = modal.querySelector('#adicionar-hospede-btn');
                if (!btnAdicionar) return;

                // Clona o botão para remover listeners antigos
                const novoBtn = btnAdicionar.cloneNode(true);
                btnAdicionar.parentNode.replaceChild(novoBtn, btnAdicionar);

                // Adiciona novo listener
                novoBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    adicionarHospedeSecundario();
                });
            }

            // Adicionar hóspede secundário
            function adicionarHospedeSecundario() {
                const container = document.getElementById('hospedes-container');
                if (!container) return;

                if (hospedesAtuais >= capacidadeQuarto - 1) {
                    alert('Capacidade máxima de hóspedes atingida');
                    return;
                }

                const novoIndex = container.querySelectorAll('tr').length;
                const novaLinha = document.createElement('tr');
                novaLinha.className = 'hospede-row';
                novaLinha.innerHTML = `
            <td>
                <input type="text" class="form-control hospede-nome" 
                       name="hospedes[${novoIndex}][nome]" 
                       placeholder="Nome completo"
                       required>
            </td>
            <td>
                <input type="text" class="form-control hospede-documento" 
                       name="hospedes[${novoIndex}][documento]" 
                       placeholder="CPF ou RG">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm btn-remover-hospede">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

                container.appendChild(novaLinha);
                hospedesAtuais++;

                // Adiciona handler para remoção
                novaLinha.querySelector('.btn-remover-hospede').addEventListener('click', function(e) {
                    e.preventDefault();
                    novaLinha.remove();
                    hospedesAtuais--;
                    atualizarContadorHospedes();
                });

                atualizarContadorHospedes();
            }

            // Carregar hóspedes existentes
            function carregarHospedesExistentes(hospedes) {
                const container = document.getElementById('hospedes-container');
                if (!container) return;

                container.innerHTML = '';
                hospedesAtuais = 0;

                hospedes.forEach((hospede, index) => {
                    if (hospedesAtuais >= capacidadeQuarto - 1) return;

                    const row = document.createElement('tr');
                    row.className = 'hospede-row';
                    row.innerHTML = `
                <td>
                    <input type="text" class="form-control hospede-nome" 
                           name="hospedes[${index}][nome]" 
                           value="${hospede.nome || ''}" 
                           required>
                </td>
                <td>
                    <input type="text" class="form-control hospede-documento" 
                           name="hospedes[${index}][documento]" 
                           value="${hospede.documento || ''}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-remover-hospede">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;

                    container.appendChild(row);
                    hospedesAtuais++;

                    row.querySelector('.btn-remover-hospede').addEventListener('click', function(e) {
                        e.preventDefault();
                        row.remove();
                        hospedesAtuais--;
                        atualizarContadorHospedes();
                    });
                });
            }

            // Atualizar contador de hóspedes
            function atualizarContadorHospedes() {
                const contador = document.getElementById('hospedes-contador');
                const msgErro = document.getElementById('hospedes-erro');

                if (contador) {
                    contador.textContent = `(${hospedesAtuais} de ${capacidadeQuarto - 1} vagas utilizadas)`;
                }

                if (msgErro) {
                    msgErro.textContent = hospedesAtuais >= capacidadeQuarto - 1 ?
                        'Capacidade máxima de hóspedes atingida' :
                        '';
                }
            }

            // Configurar parcelamento
            function configurarParcelamento(valorTotal, parcelas = 1) {
                const selectPagamento = document.getElementById('modal-pagamento');
                if (!selectPagamento) return;

                const parcelasDiv = document.getElementById('modal-parcelas-div');
                const valorParcelaDiv = document.getElementById('modal-valor-parcela-div');

                // Função para calcular e atualizar as parcelas
                const atualizarParcelas = () => {
                    const numParcelas = parseInt(document.getElementById('modal-parcelas').value) || 1;
                    const valorParcela = valorTotal / numParcelas;

                    // Atualiza campos visíveis
                    const valorParcelaText = document.getElementById('modal-valor-parcela-text');
                    if (valorParcelaText) {
                        valorParcelaText.textContent =
                            `${valorParcela.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'})} em ${numParcelas}x`;
                    }

                    // Atualiza campos hidden para envio no POST
                    document.getElementById('hidden-valor-parcela').value = valorParcela.toFixed(2);
                };

                // Evento de mudança na forma de pagamento
                selectPagamento.addEventListener('change', function() {
                    if (this.value === 'credito') {
                        if (parcelasDiv) parcelasDiv.style.display = 'block';
                        if (valorParcelaDiv) valorParcelaDiv.style.display = 'block';
                        atualizarParcelas();
                    } else {
                        if (parcelasDiv) parcelasDiv.style.display = 'none';
                        if (valorParcelaDiv) valorParcelaDiv.style.display = 'none';
                        // Limpa valores quando não é crédito
                        document.getElementById('hidden-valor-parcela').value = '';
                    }
                });

                // Evento de mudança no número de parcelas
                const inputParcelas = document.getElementById('modal-parcelas');
                if (inputParcelas) {
                    inputParcelas.addEventListener('input', function() {
                        if (this.value > 12) this.value = 12;
                        if (this.value < 1) this.value = 1;
                        atualizarParcelas();
                    });
                }

                // Inicializar
                if (selectPagamento.value === 'credito') {
                    if (parcelasDiv) parcelasDiv.style.display = 'block';
                    if (valorParcelaDiv) valorParcelaDiv.style.display = 'block';
                    if (inputParcelas) inputParcelas.value = parcelas;
                    atualizarParcelas();
                }
            }

            // Configurar botão de cancelar reserva
            const btnCancelar = document.getElementById('modal-cancelar-reserva');
            if (btnCancelar) {
                btnCancelar.addEventListener('click', function() {
                    if (confirm('Tem certeza que deseja cancelar esta reserva?')) {
                        if (!reservaAtual || !reservaAtual.id) return;

                        fetch(`../hospedes/cancelar_reserva.php?id=${reservaAtual.id}`, {
                                method: 'POST'
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Reserva cancelada com sucesso!');
                                    location.reload();
                                } else {
                                    alert('Erro: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Erro:', error);
                                alert('Erro ao cancelar reserva');
                            });
                    }
                });
            }
        });
    </script>
</body>

</html>