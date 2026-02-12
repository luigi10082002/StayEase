<?php
include(__DIR__ . '/.././db/dbHotel.php');

define('BASE_URL', '/StayEase-Solutionsv2/Hotel/');


/*if (session_status() == PHP_SESSION_NONE) {
  session_start();
}*/

if (!isset($_SESSION['id'])) {
  header("Location: ../index.php");
  exit;
}
?>

<!-- Importação de fontes e Bootstrap -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<link rel="stylesheet" href="/StayEase-Solutionsv2/Hotel/css/components.css">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <!-- Logo e nome da pousada -->
    <a class="navbar-brand d-flex align-items-center" href="<?= $_SESSION['tipo'] === 'cliente' ? '../hospedes/minhas_reservas_cliente.php' : BASE_URL . 'home.php' ?>">
      <i class="bi bi-building fs-4 me-2"></i>Apê Pousada
    </a>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav mx-auto">
        <?php if ($_SESSION['tipo'] === 'cliente'): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>hospedes/reserva_quartos_cliente.php"><i class="fas fa-bed"></i> Fazer Reserva</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>hospedes/minhas_reservas_cliente.php"><i class="fas fa-hotel"></i> Minhas Reservas</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>home.php"><i class="fas fa-home"></i> Home</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>consultas/consulta_reservas.php"><i class="fas fa-calendar-check"></i> Gerenciar Reservas</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>consultas/consulta_quartos.php"><i class="fas fa-hotel"></i> Gerenciar Quartos</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>acoes_reserva/relatorio_servico_quarto.php"><i class="fas fa-concierge-bell"></i> Serviços de Quarto</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>consultas/consulta_clientes.php"><i class="fas fa-users"></i> Clientes</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>consultas/consulta_funcionarios.php"><i class="fas fa-user-group"></i> Funcionários</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>financeiro/baixas_pagamento.php"><i class="fas fa-money-check-alt"></i> Pagamentos</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>financeiro/relatorio_financeiro.php"><i class="fas fa-chart-line"></i> Financeiro</a></li>
        <?php endif; ?>
      </ul>
      <!-- icone do usuario -->
      <div class="d-flex align-items-center justify-content-end" style="width: 165.590px;">
        <a class="nav-link text-white" href="#" onclick="abrirEditarCadastro()">
          <i class="fas fa-user-circle fa-lg"></i>
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- Modal de Cadastro -->
<?php include("cadastro.php"); ?>
<!-- Modal de Edição -->
<?php include("edicao.php"); ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
