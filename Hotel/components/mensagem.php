<?php
session_start();
include('../db/dbHotel.php');

// Verifica se é uma requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Se for uma submissão de formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && ($_POST['acao'] === 'cadastrar' || $_POST['acao'] === 'editar')) {
    try {
        // Validações básicas
        if (empty($_POST['numero'])) {
            throw new Exception("Número do quarto é obrigatório");
        }
        
        if (empty($_POST['preco'])) {
            throw new Exception("Preço é obrigatório");
        }

        if (empty($_POST['status']) || !in_array($_POST['status'], ['Disponível', 'Ocupado', 'Manutenção'])) {
            throw new Exception("Status inválido");
        }

        // Processamento da imagem principal
        $imagemPrincipal = null;
        
        // 1. Mantém imagem existente (para edição)
        if ($_POST['acao'] === 'editar' && !empty($_POST['imagem_existente'])) {
            $imagemPrincipal = $_POST['imagem_existente'];
            
            // Remove imagem se marcada para remoção e houver novo upload
            if (!empty($_POST['remover_imagem']) && $_POST['remover_imagem'] === '1' && !empty($_FILES['imagem']['name'])) {
                $caminhoImagem = "../uploads/quartos/" . $imagemPrincipal;
                if (file_exists($caminhoImagem)) {
                    unlink($caminhoImagem);
                }
                $imagemPrincipal = null;
            }
        }
        
        // 2. Processa nova imagem (se enviada)
        if (!empty($_FILES['imagem']['name'])) {
            $diretorio = "../uploads/quartos/";
            $nomeArquivo = uniqid() . '_' . basename($_FILES['imagem']['name']);
            $caminhoCompleto = $diretorio . $nomeArquivo;
            
            // Validação do tipo de arquivo
            $permitidos = ['image/jpeg', 'image/png', 'image/gif'];
            $tipoArquivo = mime_content_type($_FILES['imagem']['tmp_name']);
            
            if (!in_array($tipoArquivo, $permitidos)) {
                throw new Exception("Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos");
            }
            
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $caminhoCompleto)) {
                // Remove imagem antiga se existir
                if (!empty($imagemPrincipal)) {
                    $caminhoImagemAntiga = "../uploads/quartos/" . $imagemPrincipal;
                    if (file_exists($caminhoImagemAntiga)) {
                        unlink($caminhoImagemAntiga);
                    }
                }
                $imagemPrincipal = $nomeArquivo;
            } else {
                throw new Exception("Erro ao enviar a imagem");
            }
        }
        
        // Valida se tem imagem (para novo cadastro)
        if ($_POST['acao'] === 'cadastrar' && empty($imagemPrincipal)) {
            throw new Exception("Uma imagem do quarto é obrigatória");
        }

        // Prepara os dados
        $dados = [
            'numero' => $_POST['numero'],
            'preco' => str_replace(['.', ','], ['', '.'], $_POST['preco']),
            'camas_solteiro' => $_POST['camas_solteiro'] ?? 0,
            'beliches' => $_POST['beliches'] ?? 0,
            'camas_casal' => $_POST['camas_casal'] ?? 0,
            'descricao' => $_POST['descricao'],
            'regras' => $_POST['regras'] ?? null,
            'status' => $_POST['status'],
            'imagem' => $imagemPrincipal
        ];

        // Decide entre inserir ou atualizar
        if ($_POST['acao'] === 'cadastrar') {
            $sql = "INSERT INTO quartos (
                    numero, preco, camas_solteiro, beliches, camas_casal, 
                    descricao, regras, status, imagem
                ) VALUES (
                    :numero, :preco, :camas_solteiro, :beliches, :camas_casal, 
                    :descricao, :regras, :status, :imagem
                )";
            $mensagemSucesso = "Quarto cadastrado com sucesso!";
        } else {
            $sql = "UPDATE quartos SET 
                    numero = :numero, 
                    preco = :preco, 
                    camas_solteiro = :camas_solteiro, 
                    beliches = :beliches, 
                    camas_casal = :camas_casal, 
                    descricao = :descricao, 
                    regras = :regras,
                    status = :status,
                    imagem = :imagem 
                WHERE id = :id";
            
            $dados['id'] = $_POST['id'];
            $mensagemSucesso = "Quarto atualizado com sucesso!";
        }

        // Executa a query
        $stmt = $pdo->prepare($sql);
        $stmt->execute($dados);

        if ($isAjax) {
            echo json_encode([
                'status' => 'success',
                'message' => $mensagemSucesso,
                'redirect' => ($_POST['origem'] ?? '../consultas/consulta_quartos.php') . ($_POST['acao'] === 'editar' ? '?id=' . $_POST['id'] : '')
            ]);
            exit;
        } else {
            // Armazena os dados para exibir o modal
            $_SESSION['modal_data'] = [
                'title' => 'Sucesso',
                'message' => $mensagemSucesso,
                'type' => 'success',
                'redirect' => ($_POST['origem'] ?? '../consultas/consulta_quartos.php') . ($_POST['acao'] === 'editar' ? '?id=' . $_POST['id'] : '')
            ];
            // Não redireciona ainda - o HTML abaixo vai exibir o modal
        }

    } catch (Exception $e) {
        if ($isAjax) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        } else {
            $_SESSION['modal_data'] = [
                'title' => 'Erro',
                'message' => 'Erro ao salvar quarto: ' . $e->getMessage(),
                'type' => 'error',
                'redirect' => ($_POST['origem'] ?? '../consultas/consulta_quartos.php') . ($_POST['acao'] === 'editar' ? '?id=' . $_POST['id'] : '')
            ];
        }
    }
}

// Se não for POST ou se for POST mas não AJAX (precisa exibir a página com o modal)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['modal_data']) ? htmlspecialchars($_SESSION['modal_data']['title']) : 'Sistema de Quartos'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .modal-backdrop {
            opacity: 0.5 !important;
        }
        body.modal-open {
            overflow: hidden;
            padding-right: 0 !important;
        }
    </style>
</head>
<body class="<?php echo isset($_SESSION['modal_data']) ? 'modal-open' : ''; ?>">
    <?php if (isset($_SESSION['modal_data'])): ?>
        <!-- Modal -->
        <div class="modal fade show" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true" style="display: block; padding-right: 17px;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-<?php echo $_SESSION['modal_data']['type'] === 'success' ? 'success' : 'danger'; ?> text-white">
                        <h5 class="modal-title" id="messageModalLabel"><?php echo htmlspecialchars($_SESSION['modal_data']['title']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo htmlspecialchars($_SESSION['modal_data']['message']); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-<?php echo $_SESSION['modal_data']['type'] === 'success' ? 'success' : 'danger'; ?>" data-bs-dismiss="modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>

        <?php
            // Armazena o redirect antes de limpar a sessão
            $redirect = $_SESSION['modal_data']['redirect'] ?? '../consultas/consulta_quartos.php';
            unset($_SESSION['modal_data']);
        ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('messageModal'));
                modal.show();
                
                document.getElementById('messageModal').addEventListener('hidden.bs.modal', function () {
                    window.location.href = '<?php echo $redirect; ?>';
                });
            });
        </script>
    <?php else: ?>
        <!-- Se não houver modal para mostrar, redireciona para a página principal -->
        <script>
            window.location.href = '../consultas/consulta_quartos.php';
        </script>
    <?php endif; ?>
</body>
</html>