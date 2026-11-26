<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $this->e($title ?? 'Xác Thực - Vivutour') ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/css/style.css?v=20260723-3">
  <?= $this->section('page_specific_css') ?>
</head>

<body class="bg-light min-vh-100 d-flex flex-column">

  <!-- Header tối giản -->
  <nav class="navbar bg-white border-bottom shadow-sm">
    <div class="container">
      <a
        href="/"
        class="navbar-brand d-flex align-items-center gap-2 fw-bold">
        <img
          src="/images/logo.jpg"
          alt="Logo VivuTour"
          class="rounded-circle border border-warning border-2 object-fit-cover " width="50"
          height="50">

        <span>

          <span class=" text-warning">Vivu</span><span class="text-dark">Tour</span>
        </span>
      </a>
      <a href="/" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Trang chủ
      </a>
    </div>
  </nav>

  <!-- Nội dung form -->
  <main class="flex-grow-1 py-5">
    <?php if ($flashSuccess || $flashError) : ?>
      <div class="container mb-3" style="max-width:560px">
        <?php if ($flashSuccess) : ?><div class="alert alert-success shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><?= $this->e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($flashError) : ?><div class="alert alert-danger shadow-sm" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $this->e($flashError) ?></div><?php endif; ?>
      </div>
    <?php endif; ?>
    <?= $this->section('page') ?>
  </main>

  <!-- Footer tối giản -->
  <footer class="bg-white border-top py-3 text-center">
    <small class="text-muted">© 2026 VivuTour – Hệ thống đặt tour chất lượng cao.</small>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <?= $this->section('page_specific_js') ?>
</body>

</html>
