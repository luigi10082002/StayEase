<?php
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }
?>

<!-- Modal de Cadastro -->
<form action="/StayEase-Solutionsv2/Hotel/cadastro.php" method="POST">
    <input type="hidden" name="cadastro" value="1">
    <div class="modal fade" id="cadastroCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Cadastro em 3 Etapas</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Progresso -->
                    <div class="progress mb-4" style="height: 5px;">
                        <div class="progress-bar" role="progressbar" style="width: 33%" id="progressBar"></div>
                    </div>

                    <!-- Carrossel -->
                    <div id="formCarousel" class="carousel slide" data-bs-interval="false">
                        <div class="carousel-inner">

                            <!-- Etapa 1: Dados de Login -->
                            <div class="carousel-item active">
                                <div class="step-content">
                                    <h6 class="mb-4 text-success"><i class="fas fa-user-lock me-2"></i>Dados de Acesso</h6>
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" name="nome_completo" placeholder="Nome completo" required>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <div class="form-check form-check-inline me-3">
                                                    <input class="form-check-input" type="radio" name="tipo_documento" id="cpf" value="cpf" checked>
                                                    <label class="form-check-label small" for="cpf">CPF</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="tipo_documento" id="cnpj" value="cnpj">
                                                    <label class="form-check-label small" for="cnpj">CNPJ</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <input type="text" class="form-control" name="documento" id="documento" placeholder="CPF/CNPJ" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">E-mail</label>
                                            <input type="email" class="form-control" name="email" id="email" placeholder="E-mail" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Senha</label>
                                            <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Etapa 2: Dados Pessoais -->
                            <div class="carousel-item">
                                <div class="step-content">
                                    <h6 class="mb-4 text-success"><i class="fas fa-id-card me-2"></i>Dados Pessoais</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Telefone Celular</label>
                                            <input type="tel" class="form-control" name="telefone_celular" placeholder="Telefone Celular" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Telefone Fixo</label>
                                            <input type="tel" class="form-control" name="telefone_fixo" placeholder="Telefone Fixo">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Data de Nascimento</label>
                                            <input type="date" class="form-control" name="data_nascimento" min="1900-01-01" max="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Sexo</label>
                                            <select class="form-select" name="sexo">
                                                <option value="">Selecione...</option>
                                                <option value="masculino">Masculino</option>
                                                <option value="feminino">Feminino</option>
                                                <option value="outro">Outro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Profissão</label>
                                            <input type="text" class="form-control" name="profissao" placeholder="Profissão">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Empresa</label>
                                            <input type="text" class="form-control" name="empresa" placeholder="Empresa onde trabalha">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Nacionalidade</label>
                                            <input type="text" class="form-control" name="nacionalidade" placeholder="Nacionalidade">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Documento de Identificação</label>
                                            <input type="text" class="form-control" name="documento_identificacao" placeholder="RG, CNH, Passaporte">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Etapa 3: Endereço -->
                            <div class="carousel-item">
                                <div class="step-content">
                                    <h6 class="mb-4 text-success"><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">CEP</label>
                                            <input type="text" class="form-control" name="cep" id="cep" placeholder="CEP" required>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Logradouro</label>
                                            <input type="text" class="form-control" name="logradouro" id="logradouro" placeholder="Rua/Avenida/etc" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Número</label>
                                            <input type="text" class="form-control" name="numero" id="numero" placeholder="Numero" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Complemento</label>
                                            <input type="text" class="form-control" name="complemento" id="complemento" placeholder="Apto 101, sobrado, etc.">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Bairro</label>
                                            <input type="text" class="form-control" name="bairro" id="bairro" placeholder="Bairro" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Cidade</label>
                                            <input type="text" class="form-control" name="cidade" id="cidade" placeholder="Cidade" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Estado</label>
                                            <select class="form-select" name="estado" id="estado" required>
                                                <option value="">Selecione seu estado...</option>
                                                <option value="AC">Acre</option>
                                                <option value="AL">Alagoas</option>
                                                <option value="AP">Amapá</option>
                                                <option value="AM">Amazonas</option>
                                                <option value="BA">Bahia</option>
                                                <option value="CE">Ceará</option>
                                                <option value="DF">Distrito Federal</option>
                                                <option value="ES">Espírito Santo</option>
                                                <option value="GO">Goiás</option>
                                                <option value="MA">Maranhão</option>
                                                <option value="MT">Mato Grosso</option>
                                                <option value="MS">Mato Grosso do Sul</option>
                                                <option value="MG">Minas Gerais</option>
                                                <option value="PA">Pará</option>
                                                <option value="PB">Paraíba</option>
                                                <option value="PR">Paraná</option>
                                                <option value="PE">Pernambuco</option>
                                                <option value="PI">Piauí</option>
                                                <option value="RJ">Rio de Janeiro</option>
                                                <option value="RN">Rio Grande do Norte</option>
                                                <option value="RS">Rio Grande do Sul</option>
                                                <option value="RO">Rondônia</option>
                                                <option value="RR">Roraima</option>
                                                <option value="SC">Santa Catarina</option>
                                                <option value="SP">São Paulo</option>
                                                <option value="SE">Sergipe</option>
                                                <option value="TO">Tocantins</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer com Controles -->
                <div class="modal-footer">
                    <?php if (strpos($_SERVER['REQUEST_URI'], 'index.php') !== false): ?>
                        <div class="me-auto">
                            <button id="backToLogin" type="button" class="btn text-success">
                                <i class="fas fa-arrow-left me-2"></i>Voltar para Login
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (strpos($_SERVER['REQUEST_URI'], 'index.php') === false && substr($_SERVER['REQUEST_URI'], -1) !== '#'): ?>
                        <div class="me-auto">
                            <button type="button" class="btn btn-warning" id="simplifiedRegister">
                                <i class="fas fa-user-check me-2"></i>Cadastro Simplificado
                            </button>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="btn btn-outline-success" id="prevStep" disabled>
                        <i class="fas fa-chevron-left me-2"></i>Voltar
                    </button>
                    <button type="button" class="btn btn-success" id="nextStep">
                        Continuar<i class="fas fa-chevron-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success" id="submitForm" style="display: none;">
                        Finalizar Cadastro
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function() {
        // Configuração das máscaras para os campos (mantido igual)
        const $documento = $("#documento");
        const $telefoneCelular = $("input[name='telefone_celular']");
        const $telefoneFixo = $("input[name='telefone_fixo']");
        const $cep = $("input[name='cep']");
        const $documentoIdentificacao = $("input[name='documento_identificacao']");

        if ($documento.length && typeof $documento.mask === "function") {
            $documento.mask("000.000.000-00", {
                reverse: false
            });
        }
        $documento.attr("maxlength", "14");

        $documentoIdentificacao.on("input", function() {
            $(this).val($(this).val().replace(/[^a-zA-Z0-9]/g, ""));
        });
        $documentoIdentificacao.attr("maxlength", "10");

        $("input[name='tipo_documento']").change(function() {
            const tipo = $(this).val();
            if (tipo === "cpf") {
                if ($documento.length && $.fn.mask) {
                    $documento.mask("000.000.000-00", {
                        reverse: false
                    });
                }
                $documento.attr("maxlength", "14");
            } else {

                if ($documento.length && $.fn.mask) {
                    $documento.mask("00.000.000/0000-00", {
                        reverse: false
                    });
                }
                $documento.attr("maxlength", "18");
            }
        });

        $telefoneCelular.mask("(00) 00000-0000", {
            reverse: false
        });
        $telefoneCelular.attr("maxlength", "15");
        $telefoneFixo.mask("(00) 0000-0000", {
            reverse: false
        });
        $telefoneFixo.attr("maxlength", "14");
        $cep.mask("00000-000", {
            reverse: false
        });
        $cep.attr("maxlength", "9");

        // Busca automática de endereço via CEP (mantido igual)
        $cep.on('blur', function() {
            const cep = $(this).val().replace(/\D/g, '');
            if (cep.length !== 8) return;
            $(this).addClass('loading');

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (data.erro) throw new Error('CEP não encontrado');
                    $("#logradouro").val(data.logradouro || '');
                    $("#bairro").val(data.bairro || '');
                    $("#cidade").val(data.localidade || '');
                    $("#estado").val(data.uf || '');
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

        // Controle das etapas do cadastro
        const carousel = new bootstrap.Carousel('#formCarousel');
        const progressBar = $('#progressBar');
        const nextBtn = $('#nextStep');
        const prevBtn = $('#prevStep');
        const submitBtn = $('#submitForm');
        let currentStep = 0;
        const totalSteps = 3;

        function updateProgress() {
            const progress = ((currentStep + 1) / totalSteps) * 100;
            progressBar.css('width', `${progress}%`);
            prevBtn.prop('disabled', currentStep === 0);
            nextBtn.toggle(currentStep !== totalSteps - 1);
            submitBtn.toggle(currentStep === totalSteps - 1);
        }

        // Avança para a próxima etapa
        nextBtn.click(function() {
            const validationResult = validateStep(currentStep);
            if (validationResult.isValid) {
                currentStep++;
                carousel.next();
                updateProgress();
            } else {
                // Exibe alerta com todos os campos inválidos
                const errorMessages = validationResult.errors.join("\n• ");
                alert("Os seguintes campos precisam ser corrigidos:\n\n• " + errorMessages);

                // Rola até o primeiro erro
                $('html, body').animate({
                    scrollTop: $(validationResult.firstErrorField).offset().top - 100
                }, 500);
            }
        });

        // Volta para a etapa anterior
        prevBtn.click(function() {
            currentStep--;
            carousel.prev();
            updateProgress();
        });

        // Validação dos campos em cada etapa
        function validateStep(step) {
            const errors = [];
            let firstErrorField = null;
            let isValid = true;

            switch (step) {
                case 0: // Validação Etapa 1 (Dados de Login)
                    const nome = $("input[name='nome_completo']").val().trim();
                    const docType = $("input[name='tipo_documento']:checked").val();
                    const docValue = $("input[name='documento']").val().replace(/\D/g, '');
                    const email = $("#email").val().trim();
                    const senha = $("#senha").val().trim();
                    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;

                    if (!nome || nome.split(/\s+/).length < 2) {
                        errors.push("Nome completo (deve conter pelo menos duas palavras)");
                        if (!firstErrorField) firstErrorField = "input[name='nome_completo']";
                        isValid = false;
                    }

                    if (docType === 'cpf' && !validarCPF(docValue)) {
                        errors.push("CPF inválido");
                        if (!firstErrorField) firstErrorField = "input[name='documento']";
                        isValid = false;
                    } else if (docType === 'cnpj' && !validarCNPJ(docValue)) {
                        errors.push("CNPJ inválido");
                        if (!firstErrorField) firstErrorField = "input[name='documento']";
                        isValid = false;
                    }

                    if (!emailRegex.test(email)) {
                        errors.push("E-mail inválido");
                        if (!firstErrorField) firstErrorField = "#email";
                        isValid = false;
                    }

                    if (!senha || senha.length < 6) {
                        errors.push("Senha (mínimo 6 caracteres)");
                        if (!firstErrorField) firstErrorField = "#senha";
                        isValid = false;
                    }
                    break;

                case 1: // Validação Etapa 2 (Dados Pessoais)
                    const celular = $("input[name='telefone_celular']").val().replace(/\D/g, '');
                    const dataNascimento = $("input[name='data_nascimento']").val().trim();

                    if (!celular || celular.length !== 11) {
                        errors.push("Telefone celular (deve ter 11 dígitos)");
                        if (!firstErrorField) firstErrorField = "input[name='telefone_celular']";
                        isValid = false;
                    }

                    if (!dataNascimento) {
                        errors.push("Data de nascimento obrigatória");
                        if (!firstErrorField) firstErrorField = "input[name='data_nascimento']";
                        isValid = false;
                    } else if (!validarIdade(dataNascimento)) {
                        errors.push("Idade mínima de 18 anos");
                        if (!firstErrorField) firstErrorField = "input[name='data_nascimento']";
                        isValid = false;
                    }
                    break;

                case 2: // Validação Etapa 3 (Endereço)
                    const requiredFields = [{
                            name: 'cep',
                            label: 'CEP'
                        },
                        {
                            name: 'logradouro',
                            label: 'Logradouro'
                        },
                        {
                            name: 'numero',
                            label: 'Número'
                        },
                        {
                            name: 'bairro',
                            label: 'Bairro'
                        },
                        {
                            name: 'cidade',
                            label: 'Cidade'
                        },
                        {
                            name: 'estado',
                            label: 'Estado'
                        }
                    ];

                    for (const field of requiredFields) {
                        const value = $(`[name="${field.name}"]`).val().trim();
                        if (!value) {
                            errors.push(field.label);
                            if (!firstErrorField) firstErrorField = `[name="${field.name}"]`;
                            isValid = false;
                        }
                    }
                    break;
            }

            // Destaca visualmente os campos inválidos
            if (!isValid) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                for (const error of errors) {
                    const field = error.field || firstErrorField;
                    if (field) {
                        $(field).addClass('is-invalid');
                        $(field).after(`<div class="invalid-feedback">${error.message || 'Campo inválido'}</div>`);
                    }
                }
            }

            return {
                isValid,
                errors,
                firstErrorField
            };
        }

        // Funções auxiliares de validação (mantidas iguais)
        function validarIdade(dataNascimento) {
            if (!dataNascimento) return false;
            const hoje = new Date();
            const nascimento = new Date(dataNascimento);
            let idade = hoje.getFullYear() - nascimento.getFullYear();
            const mes = hoje.getMonth() - nascimento.getMonth();

            if (mes < 0 || (mes === 0 && hoje.getDate() < nascimento.getDate())) {
                idade--;
            }

            return idade >= 18 && idade <= 120;
        }

        function validarCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;

            let soma = 0,
                resto;
            for (let i = 1; i <= 9; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.substring(9, 10))) return false;

            soma = 0;
            for (let i = 1; i <= 10; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
            }
            resto = (soma * 10) % 11;
            return resto === parseInt(cpf.substring(10, 11));
        }

        function validarCNPJ(cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
            if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false;

            let tamanho = cnpj.length - 2;
            let numeros = cnpj.substring(0, tamanho);
            let digitos = cnpj.substring(tamanho);
            let soma = 0;
            let pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            if (resultado !== parseInt(digitos.charAt(0))) return false;

            tamanho++;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;

            for (let i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }

            resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
            return resultado === parseInt(digitos.charAt(1));
        }

        // Voltar para o login (mantido igual)
        $('#backToLogin').click(function(e) {
            e.preventDefault();
            const regModal = bootstrap.Modal.getInstance($('#cadastroCliente')[0]);
            regModal.hide();
            $('#loginModal').modal('show');
        });

        // Cadastro simplificado (mantido igual)
        $('#simplifiedRegister').click(function() {
            const nomeCompleto = $("input[name='nome_completo']").val().trim();
            const tipoDocumento = $("input[name='tipo_documento']:checked").val();
            const documento = $("input[name='documento']").val().trim();
            const email = $("#email").val().trim();
            const senha = $("#senha").val().trim();

            if (!nomeCompleto || !documento || !email || !senha) {
                alert('Por favor, preencha todos os campos obrigatórios da primeira etapa.');
                return;
            }

            const validationResult = validateStep(0);
            if (!validationResult.isValid) {
                const errorMessages = validationResult.errors.join("\n• ");
                alert("Os seguintes campos precisam ser corrigidos:\n\n• " + errorMessages);
                return;
            }

            $.ajax({
                url: '/StayEase-Solutionsv2/Hotel/components/cadastro.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    cadastro_simplificado: true,
                    nome_completo: nomeCompleto,
                    tipo_documento: tipoDocumento,
                    documento: documento,
                    email: email,
                    senha: senha
                }),
                success: function(data) {
                    if (data.success) {
                        alert('Cadastro simplificado realizado com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao realizar o cadastro simplificado: ' + (data.message || 'Erro desconhecido'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro no cadastro simplificado:', error);
                    alert('Ocorreu um erro ao realizar o cadastro simplificado.');
                }
            });
        });

        // Remove mensagens de erro ao digitar (mantido igual)
        $('input, select').on('input change', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });

        // Exibir modal automaticamente se houver erros (mantido igual)
        <?php if (isset($_SESSION['cadastro_erros']) && !empty($_SESSION['cadastro_erros'])): ?>
            $(window).on('load', function() {
                const cadastroModal = new bootstrap.Modal(document.getElementById('cadastroCliente'));
                cadastroModal.show();

                <?php if (isset($_SESSION['dados_formulario'])): ?>
                    <?php foreach ($_SESSION['dados_formulario'] as $campo => $valor): ?>
                        $('[name="<?php echo $campo; ?>"]').val('<?php echo addslashes($valor); ?>');
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php foreach ($_SESSION['cadastro_erros'] as $campo => $mensagem): ?>
                    <?php if ($campo !== 'geral'): ?>
                        $('[name="<?php echo $campo; ?>"]').addClass('is-invalid');
                        $('[name="<?php echo $campo; ?>"]').after('<div class="invalid-feedback"><?php echo addslashes($mensagem); ?></div>');
                    <?php endif; ?>
                <?php endforeach; ?>
            });
        <?php endif; ?>
    });
</script>

<?php
// Limpa os dados da sessão após exibição
unset($_SESSION['cadastro_erros'], $_SESSION['dados_formulario']);
?>
</body>

</html>