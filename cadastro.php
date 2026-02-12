<?php
session_start();
include __DIR__ . '/db/dbHotel.php';

if (!$pdo) {
    $_SESSION['cadastro_erros'] = ['geral' => 'Erro ao conectar ao banco de dados.'];
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recebendo e sanitizando os dados do formulário
    $dados = [
        'nome_completo' => trim($_POST['nome_completo'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'senha' => $_POST['senha'] ?? '',
        'telefone_fixo' => trim($_POST['telefone_fixo'] ?? null),
        'telefone_celular' => trim($_POST['telefone_celular'] ?? null),
        'data_nascimento' => $_POST['data_nascimento'] ?? '',
        'sexo' => $_POST['sexo'] ?? '',
        'profissao' => trim($_POST['profissao'] ?? null),
        'nacionalidade' => trim($_POST['nacionalidade'] ?? null),
        'tipo_documento' => $_POST['tipo_documento'] ?? '',
        'cpf_cnpj' => trim($_POST['documento'] ?? null),
        'documento_Inde' => trim($_POST['documento_identificacao'] ?? null),
        'cep' => trim($_POST['cep'] ?? ''),
        'logradouro' => trim($_POST['logradouro'] ?? ''),
        'numero' => trim($_POST['numero'] ?? ''),
        'complemento' => trim($_POST['complemento'] ?? null),
        'bairro' => trim($_POST['bairro'] ?? ''),
        'cidade' => trim($_POST['cidade'] ?? ''),
        'estado' => $_POST['estado'] ?? '',
        'empresa_trabalha' => trim($_POST['empresa'] ?? null)
    ];

    // Validações
    $erros = [];

    // Campos obrigatórios
    $camposObrigatorios = [
        'nome_completo' => 'Nome completo é obrigatório',
        'email' => 'E-mail é obrigatório',
        'senha' => 'Senha é obrigatória',
        'telefone_celular' => 'Telefone celular é obrigatório',
        'data_nascimento' => 'Data de nascimento é obrigatória',
        'tipo_documento' => 'Tipo de documento é obrigatório',
        'cpf_cnpj' => 'CPF/CNPJ é obrigatório',
        'cep' => 'CEP é obrigatório',
        'logradouro' => 'Logradouro é obrigatório',
        'numero' => 'Número é obrigatório',
        'bairro' => 'Bairro é obrigatório',
        'cidade' => 'Cidade é obrigatória',
        'estado' => 'Estado é obrigatório'
    ];

    foreach ($camposObrigatorios as $campo => $mensagem) {
        if (empty($dados[$campo])) {
            $erros[$campo] = $mensagem;
        }
    }

    // Validação de e-mail
    if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'E-mail inválido';
    }

    // Validação de senha
    if (!empty($dados['senha']) && strlen($dados['senha']) < 6) {
        $erros['senha'] = 'Senha deve ter pelo menos 6 caracteres';
    }

    // Validação de idade (mínimo 18 anos)
    if (!empty($dados['data_nascimento'])) {
        $hoje = new DateTime();
        $nascimento = new DateTime($dados['data_nascimento']);
        $idade = $hoje->diff($nascimento)->y;
        
        if ($idade < 18) {
            $erros['data_nascimento'] = 'Você deve ter pelo menos 18 anos';
        } elseif ($idade > 120) {
            $erros['data_nascimento'] = 'Data de nascimento inválida';
        }
    }

    // Verifica se e-mail já existe (apenas se não houver outros erros)
    if (empty($erros['email'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$dados['email']]);
            if ($stmt->fetch()) {
                $erros['email'] = 'Este e-mail já está cadastrado';
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar e-mail: " . $e->getMessage());
        }
    }

    // Se não houver erros, procede com o cadastro
    if (empty($erros)) {
        try {
            // Hash da senha
            $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);

            // Preparando a query
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (
                    nome_completo, email, senha, telefone_fixo, telefone_celular, 
                    data_nascimento, sexo, profissao, nacionalidade, tipo_documento, 
                    cpf_cnpj, documento_Inde, cep, logradouro, numero, 
                    complemento, bairro, cidade, estado, empresa_trabalha
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?
                )
            ");

            // Executando a query
            $stmt->execute([
                $dados['nome_completo'],
                $dados['email'],
                $senhaHash,
                $dados['telefone_fixo'],
                $dados['telefone_celular'],
                $dados['data_nascimento'],
                $dados['sexo'],
                $dados['profissao'],
                $dados['nacionalidade'],
                $dados['tipo_documento'],
                $dados['cpf_cnpj'],
                $dados['documento_Inde'],
                $dados['cep'],
                $dados['logradouro'],
                $dados['numero'],
                $dados['complemento'],
                $dados['bairro'],
                $dados['cidade'],
                $dados['estado'],
                $dados['empresa_trabalha']
            ]);

            // Cadastro bem-sucedido
            $_SESSION['cadastro_sucesso'] = 'Cadastro realizado com sucesso! Faça login.';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit();

        } catch (PDOException $e) {
            error_log("Erro no banco de dados: " . $e->getMessage());
            $erros['geral'] = 'Erro ao cadastrar. Por favor, tente novamente.';
        }
    }

    // Se houve erros, armazena na sessão e redireciona
    $_SESSION['cadastro_erros'] = $erros;
    $_SESSION['dados_formulario'] = $dados;

    //print_r($_SESSION['dados_formulario']);die();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Se tentarem acessar diretamente o arquivo sem POST
header('Location: ../index.php');
exit();
?>
