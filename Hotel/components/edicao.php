<?php

include(__DIR__ . '/.././db/dbHotel.php');

if (!isset($_SESSION['id'])) {
  die("Usuário não autenticado.");
}

$usuarioId = $_SESSION['id'];
$mensagem = '';
$erro = false;

include(__DIR__ . '/.././db/dbHotel.php');

if (!$pdo) {
  die("Erro ao conectar ao banco de dados.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
  header('Content-Type: application/json');
  try {
    $stmt = $pdo->prepare("UPDATE usuarios SET 
      nome_completo = :nome, 
      email = :email, 
      telefone_celular = :telefone_celular, 
      telefone_fixo = :telefone_fixo, 
      data_nascimento = :data_nascimento, 
      sexo = :sexo, 
      profissao = :profissao, 
      empresa = :empresa, 
      nacionalidade = :nacionalidade, 
      cep = :cep, 
      logradouro = :logradouro, 
      numero = :numero, 
      complemento = :complemento, 
      bairro = :bairro, 
      cidade = :cidade, 
      estado = :estado
      WHERE id = :id
    ");
    $stmt->execute([
      ':nome' => $_POST['perfil_nome_completo'] ?? '',
      ':email' => $_POST['perfil_email'] ?? '',
      ':telefone_celular' => $_POST['perfil_telefone_celular'] ?? '',
      ':telefone_fixo' => $_POST['perfil_telefone_fixo'] ?? '',
      ':data_nascimento' => $_POST['perfil_data_nascimento'] ?? '',
      ':sexo' => $_POST['perfil_sexo'] ?? '',
      ':profissao' => $_POST['perfil_profissao'] ?? '',
      ':empresa' => $_POST['perfil_empresa'] ?? '',
      ':nacionalidade' => $_POST['perfil_nacionalidade'] ?? '',
      ':cep' => $_POST['perfil_cep'] ?? '',
      ':logradouro' => $_POST['perfil_logradouro'] ?? '',
      ':numero' => $_POST['perfil_numero'] ?? '',
      ':complemento' => $_POST['perfil_complemento'] ?? '',
      ':bairro' => $_POST['perfil_bairro'] ?? '',
      ':cidade' => $_POST['perfil_cidade'] ?? '',
      ':estado' => $_POST['perfil_estado'] ?? '',
      ':id' => $usuarioId
    ]);
    echo json_encode(['sucesso' => true]);
  } catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
  }
  exit;
}

error_log("Formulário recebido: " . print_r($_POST, true));

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->bindValue(':id', $usuarioId, PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
  die("Usuário não encontrado.");
}
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="/StayEase-Solutionsv2/Hotel/css/components.css">

<!-- Modal de Perfil -->
<div class="modal fade" id="editarCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title text-success"><i class="fas fa-user-edit me-2"></i>Editar Cadastro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="/StayEase-Solutionsv2/Hotel/components/edicao.php" method="POST">
          <!-- Accordion de Seções -->
          <div class="accordion" id="perfilAccordion">

            <!-- Seção Dados Pessoais -->
            <div class="accordion-item border-success mb-3">
              <h2 class="accordion-header">
                <button class="accordion-button bg-success text-white" type="button"
                  data-bs-toggle="collapse" data-bs-target="#perfil_dadosPessoais"
                  aria-expanded="true" aria-controls="perfil_dadosPessoais">
                  <i class="fas fa-user-circle me-2"></i>Dados Pessoais
                </button>
              </h2>
              <div id="perfil_dadosPessoais" class="accordion-collapse collapse show"
                data-bs-parent="#perfilAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Nome Completo</label>
                      <input type="text" class="form-control" name="perfil_nome_completo" required placeholder="Digite seu nome completo"
                        value="<?= htmlspecialchars($usuario['nome_completo'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">E-mail</label>
                      <input type="email" class="form-control" name="perfil_email" required placeholder="exemplo@email.com"
                        value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Telefone Celular</label>
                      <input type="text" class="form-control" name="perfil_telefone_celular" placeholder="(99) 99999-9999"
                        value="<?= htmlspecialchars($usuario['telefone_celular'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Telefone Fixo</label>
                      <input type="text" class="form-control" name="perfil_telefone_fixo" placeholder="(99) 9999-9999"
                        value="<?= htmlspecialchars($usuario['telefone_fixo'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Data de Nascimento</label>
                      <input type="date" class="form-control" name="perfil_data_nascimento" min="1900-01-01" max="<?= date('Y-m-d'); ?>" placeholder="dd/mm/aaaa"
                        value="<?= htmlspecialchars($usuario['data_nascimento'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Sexo</label>
                      <select class="form-select" name="perfil_sexo">
                        <option value="">Selecione...</option>
                        <option value="masculino" <?= (strtolower($usuario['sexo'] ?? '') == 'masculino') ? 'selected' : '' ?>>Masculino</option>
                        <option value="feminino" <?= (strtolower($usuario['sexo'] ?? '') == 'feminino') ? 'selected' : '' ?>>Feminino</option>
                        <option value="outro" <?= (strtolower($usuario['sexo'] ?? '') == 'outro') ? 'selected' : '' ?>>Outro</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Seção Dados Adicionais -->
            <div class="accordion-item border-success mb-3">
              <h2 class="accordion-header">
                <button class="accordion-button bg-success text-white collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#perfil_dadosAdicionais"
                  aria-expanded="false" aria-controls="perfil_dadosAdicionais">
                  <i class="fas fa-id-card me-2"></i>Dados Adicionais
                </button>
              </h2>
              <div id="perfil_dadosAdicionais" class="accordion-collapse collapse"
                data-bs-parent="#perfilAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Profissão</label>
                      <input type="text" class="form-control" name="perfil_profissao" placeholder="Sua profissão"
                        value="<?= htmlspecialchars($usuario['profissao'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Empresa</label>
                      <input type="text" class="form-control" name="perfil_empresa" placeholder="Nome da empresa"
                        value="<?= htmlspecialchars($usuario['empresa'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Nacionalidade</label>
                      <input type="text" class="form-control" name="perfil_nacionalidade" placeholder="Sua nacionalidade"
                        value="<?= htmlspecialchars($usuario['nacionalidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Documento de Identificação</label>
                      <input type="text" class="form-control" name="perfil_documento_identificacao" placeholder="Documento" disabled
                        value="<?= htmlspecialchars($usuario['documento_identificacao'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                      <div class="border p-3 rounded">
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="perfil_tipo_documento"
                            id="perfil_cpf" value="cpf"
                            <?= (isset($usuario['tipo_documento']) && $usuario['tipo_documento'] == 'cpf') ? 'checked' : '' ?> disabled>
                          <label class="form-check-label" for="perfil_cpf">CPF</label>
                        </div>
                        <div class="form-check form-check-inline">
                          <input class="form-check-input" type="radio" name="perfil_tipo_documento"
                            id="perfil_cnpj" value="cnpj"
                            <?= (isset($usuario['tipo_documento']) && $usuario['tipo_documento'] == 'cnpj') ? 'checked' : '' ?> disabled>
                          <label class="form-check-label" for="perfil_cnpj">CNPJ</label>
                        </div>
                        <input type="text" class="form-control mt-2"
                          name="perfil_documento" id="perfil_documento" placeholder="Número do documento" disabled
                          value="<?= htmlspecialchars($usuario['documento'] ?? '') ?>">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Seção Endereço -->
            <div class="accordion-item border-success mb-3">
              <h2 class="accordion-header">
                <button class="accordion-button bg-success text-white collapsed" type="button"
                  data-bs-toggle="collapse" data-bs-target="#perfil_endereco"
                  aria-expanded="false" aria-controls="perfil_endereco">
                  <i class="fas fa-map-marker-alt me-2"></i>Endereço
                </button>
              </h2>
              <div id="perfil_endereco" class="accordion-collapse collapse"
                data-bs-parent="#perfilAccordion">
                <div class="accordion-body">
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label">CEP</label>
                      <input type="text" class="form-control" name="perfil_cep" placeholder="00000-000"
                        value="<?= htmlspecialchars($usuario['cep'] ?? '') ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label">Logradouro</label>
                      <input type="text" class="form-control" name="perfil_logradouro" placeholder="Rua, Avenida..."
                        value="<?= htmlspecialchars($usuario['logradouro'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Número</label>
                      <input type="text" class="form-control" name="perfil_numero" placeholder="Número"
                        value="<?= htmlspecialchars($usuario['numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Complemento</label>
                      <input type="text" class="form-control" name="perfil_complemento" placeholder="Apartamento, bloco..."
                        value="<?= htmlspecialchars($usuario['complemento'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Bairro</label>
                      <input type="text" class="form-control" name="perfil_bairro" placeholder="Bairro"
                        value="<?= htmlspecialchars($usuario['bairro'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Cidade</label>
                      <input type="text" class="form-control" name="perfil_cidade" placeholder="Cidade"
                        value="<?= htmlspecialchars($usuario['cidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Estado</label>
                      <select class="form-select" name="perfil_estado">
                        <option value="">Selecione seu estado...</option>
                        <option value="AC" <?= (isset($usuario['estado']) && $usuario['estado'] == 'AC') ? 'selected' : '' ?>>Acre</option>
                        <option value="AL" <?= (isset($usuario['estado']) && $usuario['estado'] == 'AL') ? 'selected' : '' ?>>Alagoas</option>
                        <option value="AP" <?= (isset($usuario['estado']) && $usuario['estado'] == 'AP') ? 'selected' : '' ?>>Amapá</option>
                        <option value="AM" <?= (isset($usuario['estado']) && $usuario['estado'] == 'AM') ? 'selected' : '' ?>>Amazonas</option>
                        <option value="BA" <?= (isset($usuario['estado']) && $usuario['estado'] == 'BA') ? 'selected' : '' ?>>Bahia</option>
                        <option value="CE" <?= (isset($usuario['estado']) && $usuario['estado'] == 'CE') ? 'selected' : '' ?>>Ceará</option>
                        <option value="DF" <?= (isset($usuario['estado']) && $usuario['estado'] == 'DF') ? 'selected' : '' ?>>Distrito Federal</option>
                        <option value="ES" <?= (isset($usuario['estado']) && $usuario['estado'] == 'ES') ? 'selected' : '' ?>>Espírito Santo</option>
                        <option value="GO" <?= (isset($usuario['estado']) && $usuario['estado'] == 'GO') ? 'selected' : '' ?>>Goiás</option>
                        <option value="MA" <?= (isset($usuario['estado']) && $usuario['estado'] == 'MA') ? 'selected' : '' ?>>Maranhão</option>
                        <option value="MT" <?= (isset($usuario['estado']) && $usuario['estado'] == 'MT') ? 'selected' : '' ?>>Mato Grosso</option>
                        <option value="MS" <?= (isset($usuario['estado']) && $usuario['estado'] == 'MS') ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?= (isset($usuario['estado']) && $usuario['estado'] == 'MG') ? 'selected' : '' ?>>Minas Gerais</option>
                        <option value="PA" <?= (isset($usuario['estado']) && $usuario['estado'] == 'PA') ? 'selected' : '' ?>>Pará</option>
                        <option value="PB" <?= (isset($usuario['estado']) && $usuario['estado'] == 'PB') ? 'selected' : '' ?>>Paraíba</option>
                        <option value="PR" <?= (isset($usuario['estado']) && $usuario['estado'] == 'PR') ? 'selected' : '' ?>>Paraná</option>
                        <option value="PE" <?= (isset($usuario['estado']) && $usuario['estado'] == 'PE') ? 'selected' : '' ?>>Pernambuco</option>
                        <option value="PI" <?= (isset($usuario['estado']) && $usuario['estado'] == 'PI') ? 'selected' : '' ?>>Piauí</option>
                        <option value="RJ" <?= (isset($usuario['estado']) && $usuario['estado'] == 'RJ') ? 'selected' : '' ?>>Rio de Janeiro</option>
                        <option value="RN" <?= (isset($usuario['estado']) && $usuario['estado'] == 'RN') ? 'selected' : '' ?>>Rio Grande do Norte</option>
                        <option value="RS" <?= (isset($usuario['estado']) && $usuario['estado'] == 'RS') ? 'selected' : '' ?>>Rio Grande do Sul</option>
                        <option value="RO" <?= (isset($usuario['estado']) && $usuario['estado'] == 'RO') ? 'selected' : '' ?>>Rondônia</option>
                        <option value="RR" <?= (isset($usuario['estado']) && $usuario['estado'] == 'RR') ? 'selected' : '' ?>>Roraima</option>
                        <option value="SC" <?= (isset($usuario['estado']) && $usuario['estado'] == 'SC') ? 'selected' : '' ?>>Santa Catarina</option>
                        <option value="SP" <?= (isset($usuario['estado']) && $usuario['estado'] == 'SP') ? 'selected' : '' ?>>São Paulo</option>
                        <option value="SE" <?= (isset($usuario['estado']) && $usuario['estado'] == 'SE') ? 'selected' : '' ?>>Sergipe</option>
                        <option value="TO" <?= (isset($usuario['estado']) && $usuario['estado'] == 'TO') ? 'selected' : '' ?>>Tocantins</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Botões de Ação -->
          <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="submit" class="btn btn-success px-5">
              <i class="fas fa-save me-2"></i>Salvar Alterações
            </button>
            <button type="button" class="btn btn-warning px-5" onclick="abrirTrocarSenhaModal()">
              <i class="fas fa-lock me-2"></i>Trocar Senha
            </button>
            <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-danger px-5">
              <i class="fas fa-sign-out-alt me-2"></i>Sair
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Troca de Senha -->
<div class="modal fade" id="trocarSenhaModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          <i class="fas fa-lock me-2"></i>Alterar Senha
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form action="trocar_senha.php" method="POST">
          <!-- Senha Atual -->
          <div class="form-floating mb-4">
            <input type="password"
              class="form-control border-success"
              name="senha_atual"
              id="senha_atual"
              placeholder=" "
              required>
            <label for="senha_atual" class="text-muted">
              <i class="fas fa-key me-2 text-success"></i>Senha Atual
            </label>
          </div>

          <!-- Nova Senha -->
          <div class="form-floating mb-3">
            <input type="password"
              class="form-control border-success"
              name="nova_senha"
              id="nova_senha"
              placeholder=" "
              required
              oninput="validarForcaSenha(this.value)">
            <label for="nova_senha" class="text-muted">
              <i class="fas fa-lock me-2 text-success"></i>Nova Senha
            </label>
          </div>

          <!-- Indicador de Força da Senha -->
          <div class="progress mb-4" style="height: 5px;">
            <div id="forcaSenha" class="progress-bar" role="progressbar"></div>
          </div>

          <!-- Confirmar Senha -->
          <div class="form-floating mb-4">
            <input type="password"
              class="form-control border-success"
              name="confirmar_senha"
              id="confirmar_senha"
              placeholder=" "
              required
              oninput="validarSenhas()">
            <label for="confirmar_senha" class="text-muted">
              <i class="fas fa-check-circle me-2 text-success"></i>Confirmar Nova Senha
            </label>
          </div>

          <!-- Mensagem de Erro -->
          <div id="senhaError" class="alert alert-danger d-none">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span id="mensagemErro"></span>
          </div>

          <!-- Botões -->
          <div class="d-grid gap-2">
            <button type="submit"
              class="btn btn-success btn-lg py-3"
              id="submitSenha"
              disabled>
              <i class="fas fa-sync-alt me-2"></i>Atualizar Senha
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    let $telefoneCelular = $("input[name='perfil_telefone_celular']");
    let $telefoneFixo = $("input[name='perfil_telefone_fixo']");
    let $cep = $("input[name='perfil_cep']");

    // Máscara para telefone celular
    $telefoneCelular.mask("(00) 00000-0000", {
      reverse: false
    });
    $telefoneCelular.attr("maxlength", "15");

    // Máscara para telefone fixo
    $telefoneFixo.mask("(00) 0000-0000", {
      reverse: false
    });
    $telefoneFixo.attr("maxlength", "14");

    // Máscara para CEP
    $cep.mask("00000-000", {
      reverse: false
    });
    $cep.attr("maxlength", "9");

    // Busca automática de endereço via CEP
    $cep.on('blur', function() {
      const cep = $(this).val().replace(/\D/g, '');

      // Verifica se o CEP tem 8 dígitos
      if (cep.length !== 8) {
        return;
      }

      // Mostra um loader enquanto busca
      $(this).addClass('loading');

      // Faz a requisição para a API ViaCEP
      fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
          if (data.erro) {
            throw new Error('CEP não encontrado');
          }

          // Preenche os campos automaticamente
          $("#logradouro").val(data.logradouro || '');
          $("#bairro").val(data.bairro || '');
          $("#cidade").val(data.localidade || '');
          $("#estado").val(data.uf || '');

          // Foca no campo número para facilitar o preenchimento
          $("#numero").focus();
        })
        .catch(error => {
          console.error('Erro ao buscar CEP:', error);
          alert('CEP não encontrado. Por favor, preencha o endereço manualmente.');
        })
        .finally(() => {
          $(this).removeClass('loading');
        });
    });

    // Adiciona estilo para o loader
    const style = document.createElement('style');
    style.textContent = `
      input.loading {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="%23ccc" stroke-width="8" fill="none" stroke-dasharray="60 15" transform="rotate(0 50 50)"><animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="1s" values="0 50 50;360 50 50"/></circle></svg>');
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 20px 20px;
      }
    `;
    document.head.appendChild(style);

    $('#editarCliente form').on('submit', function(e) {
      e.preventDefault(); // Impede o envio tradicional

      var form = this;
      var formData = $(form).serialize();

      $.ajax({
        url: '/StayEase-Solutionsv2/Hotel/components/edicao.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(resp) {
          if (resp.sucesso) {
            // Fecha o modal e mostra mensagem
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editarCliente')).hide();
            alert('Dados atualizados com sucesso!');
            window.location.reload(); // Atualiza a página para refletir as mudanças
          } else {
            alert(resp.mensagem || 'Erro ao atualizar dados.');
          }
        },
        error: function(xhr) {
          let msg = 'Erro ao atualizar dados.';
          if (xhr.responseJSON && xhr.responseJSON.mensagem) {
            msg += '\n' + xhr.responseJSON.mensagem;
          }
          alert(msg);
        }
      });
    });
  });

  document.addEventListener('DOMContentLoaded', () => {
    // Função de validação da idade
    function validarIdade(dataNascimento) {
      if (!dataNascimento) return false;

      const hoje = new Date();
      const nascimento = new Date(dataNascimento);
      let idade = hoje.getFullYear() - nascimento.getFullYear();
      const mes = hoje.getMonth() - nascimento.getMonth();

      // Ajusta a idade se o mês atual for antes do mês de nascimento
      if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
      }

      // Verifica se a idade está dentro do intervalo permitido
      if (idade < 18) {
        alert('Você deve ter pelo menos 18 anos.');
        return false;
      } else if (idade > 140) {
        alert('Idade máxima permitida é de 140 anos.');
        return false;
      }

      return true;
    }
  })

  function abrirCadastro() {
    // Fecha o modal de login (W3.CSS)
    document.getElementById('loginModal').style.display = 'none';

    // Abre o modal de reserva (Bootstrap)
    var cadastroCliente = new bootstrap.Modal(document.getElementById('cadastroCliente'));
    cadastroCliente.show();
  }


  function validarSenhas() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = document.getElementById('confirmar_senha').value;
    const errorDiv = document.getElementById('senhaError');
    const submitBtn = document.getElementById('submitSenha');

    if (novaSenha !== confirmarSenha && confirmarSenha !== '') {
      errorDiv.classList.remove('d-none');
      document.getElementById('mensagemErro').textContent = 'As senhas não coincidem';
      submitBtn.disabled = true;
    } else {
      errorDiv.classList.add('d-none');
      submitBtn.disabled = !(novaSenha.length >= 8 && confirmarSenha.length >= 8);
    }
  }

  function validarForcaSenha(senha) {
    const forcaSenha = document.getElementById('forcaSenha');
    let strength = 0;

    if (senha.length >= 8) strength += 25;
    if (senha.match(/[A-Z]/)) strength += 25;
    if (senha.match(/[0-9]/)) strength += 25;
    if (senha.match(/[^A-Za-z0-9]/)) strength += 25;

    forcaSenha.style.width = strength + '%';
    forcaSenha.classList.remove('bg-danger', 'bg-warning', 'bg-success');

    if (strength < 50) {
      forcaSenha.classList.add('bg-danger');
    } else if (strength < 75) {
      forcaSenha.classList.add('bg-warning');
    } else {
      forcaSenha.classList.add('bg-success');
    }

    validarSenhas();
  }

  // Mostrar/Ocultar Senha
  document.querySelectorAll('.form-floating').forEach((div, index) => {
    const eye = document.createElement('span');
    eye.className = 'position-absolute top-50 end-0 translate-middle-y pe-3';
    eye.innerHTML = '<i class="fas fa-eye-slash text-success cursor-pointer"></i>';
    eye.onclick = () => {
      const input = div.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
      eye.innerHTML = input.type === 'password' ?
        '<i class="fas fa-eye-slash text-success"></i>' :
        '<i class="fas fa-eye text-success"></i>';
    };
    div.appendChild(eye);
  });

  const editarClienteEl = document.getElementById('editarCliente');
  const trocarSenhaEl = document.getElementById('trocarSenhaModal');
  const editarClienteModal = bootstrap.Modal.getOrCreateInstance(editarClienteEl);
  const trocarSenhaModal = bootstrap.Modal.getOrCreateInstance(trocarSenhaEl);

  function limparBackdrops() {
    document.querySelectorAll('.modal-backdrop')
      .forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
  }

  // Garante limpeza sempre que qualquer modal fechar
  [editarClienteEl, trocarSenhaEl].forEach(el => {
    el.addEventListener('hidden.bs.modal', limparBackdrops);
  });

  function abrirTrocarSenhaModal() {
    if (editarClienteEl.classList.contains('show')) {
      editarClienteEl.addEventListener('hidden.bs.modal', () => {
        limparBackdrops();
        trocarSenhaModal.show();
      }, {
        once: true
      });
      editarClienteModal.hide();
    } else {
      limparBackdrops();
      trocarSenhaModal.show();
    }
  }

  function abrirEditarCadastro() {
    limparBackdrops();
    editarClienteModal.show();
  }
</script>