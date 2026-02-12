<?php
session_start();
require_once '.././db/dbHotel.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit;
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id'])) {
    $_SESSION['erro'] = "Você precisa estar logado para fazer uma reserva";
    header("Location: ../login.php");
    exit;
}

// Determinar se é uma ação de UPDATE
$isUpdate = isset($_POST['acao']) && $_POST['acao'] === 'update';

if ($isUpdate) {
    // Lógica para UPDATE
    try {
        $pdo->beginTransaction();

        // Sanitizar e validar os dados de entrada para UPDATE
        $reserva_id = filter_input(INPUT_POST, 'reserva_id', FILTER_SANITIZE_NUMBER_INT);
        $valor_total = filter_input(INPUT_POST, 'valor_total', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $valor_parcela = filter_input(INPUT_POST, 'valor_parcela', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $pagamento = filter_input(INPUT_POST, 'pagamento', FILTER_SANITIZE_STRING);
        $parcelas = filter_input(INPUT_POST, 'parcelas', FILTER_SANITIZE_NUMBER_INT);
        $observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);

        // Validar dados obrigatórios para UPDATE
        if (empty($reserva_id) || empty($valor_total) || empty($pagamento)) {
            throw new Exception("Preencha todos os campos obrigatórios para atualização");
        }

        // 1. Verificar se a reserva pertence ao usuário
        $stmtVerificaReserva = $pdo->prepare("SELECT id FROM reservas WHERE id = ? AND usuario_id = ?");
        $stmtVerificaReserva->execute([$reserva_id, $_SESSION['id']]);
        
        if (!$stmtVerificaReserva->fetch()) {
            throw new Exception("Reserva não encontrada ou não pertence ao usuário");
        }

        // 2. Atualizar informações da reserva
        $stmtUpdateReserva = $pdo->prepare("UPDATE reservas 
                                         SET valor_reserva = ?, 
                                             forma_pagamento = ?, 
                                             observacoes = ?
                                         WHERE id = ?");
        
        if (!$stmtUpdateReserva->execute([$valor_total, $pagamento, $observacoes, $reserva_id])) {
            throw new Exception("Erro ao atualizar reserva: " . implode(", ", $stmtUpdateReserva->errorInfo()));
        }

        // 3. Remover hóspedes secundários antigos
        $stmtDeleteHospedes = $pdo->prepare("DELETE FROM hospedes_secundarios WHERE reserva_id = ?");
        if (!$stmtDeleteHospedes->execute([$reserva_id])) {
            throw new Exception("Erro ao remover hóspedes antigos: " . implode(", ", $stmtDeleteHospedes->errorInfo()));
        }

        // 4. Inserir novos hóspedes secundários
        if (isset($_POST['hospedes']) && is_array($_POST['hospedes'])) {
            $stmtHospede = $pdo->prepare("INSERT INTO hospedes_secundarios 
                                        (reserva_id, nome, documento, quarto_id)
                                        VALUES (?, ?, ?, (SELECT quarto_id FROM reservas WHERE id = ?))");
            
            foreach ($_POST['hospedes'] as $hospede) {
                $nome = filter_var($hospede['nome'], FILTER_SANITIZE_STRING);
                $documento = filter_var($hospede['documento'], FILTER_SANITIZE_STRING);
                
                if (empty($nome) || empty($documento)) {
                    throw new Exception("Todos os campos dos hóspedes são obrigatórios");
                }
                
                $stmtHospede->execute([$reserva_id, $nome, $documento, $reserva_id]);
            }
        }

        // 5. Atualizar informações de pagamento
        $metodo_pagamento = strtolower($pagamento);
        $metodos_validos = ['credito', 'pix', 'debito', 'dinheiro'];
        
        if (!in_array($metodo_pagamento, $metodos_validos)) {
            throw new Exception("Método de pagamento inválido: " . $pagamento);
        }

        $stmtUpdatePagamento = $pdo->prepare("UPDATE pagamentos 
                                           SET valor = ?, 
                                               metodo = ?, 
                                               parcelas = ?
                                           WHERE reserva_id = ?");
        
        if (!$stmtUpdatePagamento->execute([$valor_total, $metodo_pagamento, $parcelas, $reserva_id])) {
            throw new Exception("Erro ao atualizar pagamento: " . implode(", ", $stmtUpdatePagamento->errorInfo()));
        }

        $pdo->commit();

        $_SESSION['sucesso'] = "Reserva atualizada com sucesso!";
        header("Location: minhas_reservas_cliente.php");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro na atualização da reserva: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        $_SESSION['erro'] = "Erro ao atualizar reserva: " . $e->getMessage();
        header("Location: editar_reserva.php?id=" . $reserva_id);
        exit;
    }
} else {

// Sanitizar e validar os dados de entrada
$quarto_id = filter_input(INPUT_POST, 'quarto_id', FILTER_SANITIZE_NUMBER_INT);
$check_in = filter_input(INPUT_POST, 'check_in', FILTER_SANITIZE_STRING);
$check_out = filter_input(INPUT_POST, 'check_out', FILTER_SANITIZE_STRING);
$pagamento = filter_input(INPUT_POST, 'pagamento', FILTER_SANITIZE_STRING);
$observacoes = filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING);
$parcelas = filter_input(INPUT_POST, 'parcelas', FILTER_SANITIZE_NUMBER_INT);

// Validar dados obrigatórios
if (empty($quarto_id) || empty($check_in) || empty($check_out) || empty($pagamento)) {
    $_SESSION['erro'] = "Preencha todos os campos obrigatórios";
    header("Location: reserva_quartos_cliente.php");
    exit;
}

// Validar datas
if (strtotime($check_out) <= strtotime($check_in)) {
    $_SESSION['erro'] = "Data de check-out deve ser posterior ao check-in";
    header("Location: reserva_quartos_cliente.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Verificar disponibilidade do quarto
    $stmtDisponibilidade = $pdo->prepare("SELECT COUNT(*) FROM reservas 
                                        WHERE quarto_id = ? 
                                        AND status NOT IN ('cancelada', 'finalizada')
                                        AND (
                                            (data_checkin <= ? AND data_checkout >= ?) OR
                                            (data_checkin <= ? AND data_checkout >= ?) OR
                                            (data_checkin >= ? AND data_checkout <= ?)
                                        )");
    $stmtDisponibilidade->execute([
        $quarto_id,
        $check_out,
        $check_in,
        $check_in,
        $check_out,
        $check_in,
        $check_out
    ]);

    if ($stmtDisponibilidade->fetchColumn() > 0) {
        throw new Exception("Quarto não disponível no período selecionado");
    }

    // 2. Obter informações do quarto
    $stmtQuarto = $pdo->prepare("SELECT preco, numero, camas_solteiro, beliches, camas_casal FROM quartos WHERE id = ?");
    $stmtQuarto->execute([$quarto_id]);
    $quarto = $stmtQuarto->fetch(PDO::FETCH_ASSOC);

    if (!$quarto) {
        throw new Exception("Quarto não encontrado");
    }

    // 3. Determinar o tipo de cama
    $tipo_cama = 'Casal'; // padrão
    if ($quarto['camas_solteiro'] > 0) {
        $tipo_cama = 'Solteiro';
    } elseif ($quarto['beliches'] > 0) {
        $tipo_cama = 'Beliche';
    }

    // 4. Calcular valor total da reserva
    $dias = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $valor_total = $quarto['preco'] * $dias;

    // 5. Obter informações do usuário
    $stmtUsuario = $pdo->prepare("SELECT nome_completo, cpf_cnpj FROM usuarios WHERE id = ?");
    $stmtUsuario->execute([$_SESSION['id']]);
    $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        throw new Exception("Usuário não encontrado");
    }

    // 6. Inserir a reserva
    $stmtReserva = $pdo->prepare("INSERT INTO reservas 
                                (usuario_id, quarto_id, cpf_cnpj, data_checkin, hora_checkin, 
                                data_checkout, hora_checkout, tipo_camas, valor_reserva, 
                                forma_pagamento, status, observacoes)
                                VALUES (?, ?, ?, ?, '14:00:00', ?, '12:00:00', ?, ?, ?, 'pendente', ?)");
    
    $dados_reserva = [
        $_SESSION['id'],
        $quarto_id,
        $usuario['cpf_cnpj'],
        $check_in,
        $check_out,
        $tipo_cama,
        $valor_total,
        $pagamento,
        $observacoes
    ];
    
    error_log("Dados da reserva: " . print_r($dados_reserva, true));
    
    if (!$stmtReserva->execute($dados_reserva)) {
        throw new Exception("Erro ao inserir reserva: " . implode(", ", $stmtReserva->errorInfo()));
    }

    $reserva_id = $pdo->lastInsertId();
    error_log("ID da reserva criada: " . $reserva_id);

    // 7. Verificar se a reserva foi realmente criada
    $stmtVerificaReserva = $pdo->prepare("SELECT id FROM reservas WHERE id = ? LIMIT 1");
    $stmtVerificaReserva->execute([$reserva_id]);
    
    if (!$stmtVerificaReserva->fetch()) {
        throw new Exception("Falha ao verificar reserva criada - ID: $reserva_id");
    }

    // 8. Inserir hóspedes secundários (CORREÇÃO SOLICITADA)
    if (isset($_POST['hospedes_secundarios']) && is_array($_POST['hospedes_secundarios'])) {
        $stmtHospede = $pdo->prepare("INSERT INTO hospedes_secundarios 
                                    (reserva_id, nome, documento, quarto_id)
                                    VALUES (?, ?, ?, ?)");
        
        foreach ($_POST['hospedes_secundarios'] as $hospede) {
            // Sanitizar os dados de cada hóspede
            $nome = filter_var($hospede['nome'], FILTER_SANITIZE_STRING);
            $documento = filter_var($hospede['documento'], FILTER_SANITIZE_STRING);
            
            // Validar campos obrigatórios
            if (empty($nome) || empty($documento)) {
                throw new Exception("Todos os campos dos hóspedes secundários são obrigatórios");
            }
            
            // Inserir no banco de dados
            $stmtHospede->execute([
                $reserva_id,
                $nome,
                $documento,
                $quarto_id
            ]);
            
            error_log("Hóspede secundário inserido: " . $nome . " - " . $documento);
        }
    }

    // 9. Registrar o pagamento
    $metodo_pagamento = strtolower($pagamento);
    $metodos_validos = ['credito', 'pix', 'debito', 'dinheiro'];
    
    if (!in_array($metodo_pagamento, $metodos_validos)) {
        throw new Exception("Método de pagamento inválido: " . $pagamento);
    }

    $num_parcelas = !empty($parcelas) ? (int)$parcelas : 1;

    $stmtPagamento = $pdo->prepare("INSERT INTO pagamentos 
                                  (reserva_id, valor, metodo, parcelas, status) 
                                  VALUES (?, ?, ?, ?, 'pendente')");
    
    $dados_pagamento = [
        $reserva_id,
        $valor_total,
        $metodo_pagamento,
        $num_parcelas
    ];
    
    error_log("Dados do pagamento: " . print_r($dados_pagamento, true));
    
    if (!$stmtPagamento->execute($dados_pagamento)) {
        $errorInfo = $stmtPagamento->errorInfo();
        error_log("Erro ao inserir pagamento: " . print_r($errorInfo, true));
        throw new Exception("Falha ao registrar pagamento: " . $errorInfo[2]);
    }

    $pdo->commit();

    $_SESSION['sucesso'] = "Reserva realizada com sucesso! Número: " . $quarto['numero'];
    header("Location: minhas_reservas_cliente.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro na reserva: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
    $_SESSION['erro'] = "Erro ao realizar reserva: " . $e->getMessage();
    //print_r($_SESSION['erro']);die();
    //header("Location: reserva_quartos_cliente.php");
    //exit;
}
}
