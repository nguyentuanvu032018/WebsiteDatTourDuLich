<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="VivuTour - hệ thống tìm kiếm và đặt tour du lịch Việt Nam.">
  <title><?= $this->e($title ?? APPNAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css?v=20260723-3">
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <?= $this->section('page_specific_css') ?>
</head>

<body class="bg-light-subtle d-flex flex-column min-vh-100">
  <header class="site-header bg-white border-bottom sticky-top">
    <nav class="navbar navbar-expand-lg py-2" aria-label="Điều hướng chính">
      <div class="container-fluid px-3 px-lg-5" style="max-width:1536px">
        <a href="/" class="navbar-brand d-flex align-items-center gap-2">
          <span class="brand-logo-frame">
            <img
              src="/images/logo.jpg"
              alt="Logo VivuTour"
              class="rounded-circle object-fit-cover"
              width="50"
              height="50">
          </span>
          <span class="brand-wordmark" aria-hidden="true">
            <span>vivu</span><strong>tour</strong>
          </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Mở menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
            <li class="nav-item"><a href="/" class="nav-link">Trang chủ</a></li>
            <li class="nav-item"><a href="/tours" class="nav-link">Tours</a></li>
            <li class="nav-item"><a href="/about" class="nav-link">Về chúng tôi</a></li>
            <li class="nav-item"><a href="/contact" class="nav-link">Liên hệ</a></li>
          </ul>

          <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0">
            <?php if (!AUTHGUARD()->isUserLoggedIn()) : ?>
              <a href="/register" class="btn btn-outline-warning text-dark fw-semibold px-3">Tạo tài khoản</a>
              <a href="/login" class="btn btn-warning px-4 fw-bold">Đăng nhập</a>
            <?php else : ?>
              <div class="dropdown">
                <button class="btn user-account-button border rounded-pill dropdown-toggle d-flex align-items-center gap-2 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                  <span class="user-initial rounded-circle">
                    <?= $this->e(mb_strtoupper(mb_substr($currentUser?->name ?? 'U', 0, 1))) ?>
                  </span>
                  <span class="text-truncate" style="max-width:180px"><?= $this->e($currentUser?->name ?? 'Người dùng') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                  <?php if (AUTHGUARD()->isAdmin()) : ?>
                    <li><a class="dropdown-item py-2" href="/admin"><i class="bi bi-speedometer2 me-2"></i>Trang quản trị</a></li>
                  <?php else : ?>
                    <li><a class="dropdown-item py-2" href="/profile"><i class="bi bi-person me-2"></i>Hồ sơ cá nhân</a></li>
                    <li><a class="dropdown-item py-2" href="/bookings/history"><i class="bi bi-clock-history me-2"></i>Lịch sử đặt tour</a></li>
                    <li>
                      <a class="dropdown-item py-2 d-flex align-items-center" href="/contacts/my">
                        <i class="bi bi-chat-square-text me-2"></i>Liên hệ của tôi
                        <?php if (($unreadContactReplies ?? 0) > 0) : ?>
                          <span class="badge bg-danger rounded-pill ms-auto"><?= (int)$unreadContactReplies ?></span>
                        <?php endif; ?>
                      </a>
                    </li>
                  <?php endif; ?>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li class="px-2">
                    <form action="/logout" method="POST">
                      <?= csrf_field() ?>
                      <button class="dropdown-item text-danger rounded py-2" type="submit">
                        <i class="bi bi-box-arrow-right me-2"></i>Đăng xuất
                      </button>
                    </form>
                  </li>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>
  </header>

  <?php if ($flashSuccess || $flashError) : ?>
    <div class="container mt-3" style="max-width:1320px">
      <?php if ($flashSuccess) : ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-check-circle-fill me-2"></i><?= $this->e($flashSuccess) ?>
          <button class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
      <?php endif; ?>
      <?php if ($flashError) : ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $this->e($flashError) ?>
          <button class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <main class="flex-grow-1">
    <?= $this->section('page') ?>
  </main>

  <footer class="site-footer text-dark pt-5 pb-3">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <a href="/" class="footer-brand d-flex align-items-center gap-2 text-decoration-none mb-3">
            <span class="brand-logo-frame brand-logo-footer">
              <img
                src="/images/logo.jpg"
                alt="Logo VivuTour"
                class="rounded-circle object-fit-cover"
                width="50"
                height="50">
            </span>
            <span class="brand-wordmark"><span>vivu</span><strong>tour</strong></span>
          </a>
          <p class="footer-muted">
            Cất cánh khám phá, chắp cánh đam mê. Đồng hành cùng bạn trên mọi hành trình Việt Nam.
          </p>
        </div>
        <div class="col-6 col-lg-2">
          <h2 class="h6 text-warning fw-bold">Về chúng tôi</h2>
          <ul class="list-unstyled small d-grid gap-2">
            <li><a href="/about">Giới thiệu</a></li>
            <li><a href="/tours">Tuyển chọn tour</a></li>
            <li><a href="/contact">Liên hệ</a></li>
          </ul>
        </div>
        <div class="col-6 col-lg-3">
          <h2 class="h6 text-warning fw-bold">Hỗ trợ</h2>
          <ul class="list-unstyled small d-grid gap-2">
            <li><a href="/contact">Câu hỏi thường gặp</a></li>
            <li><a href="/contact">Hướng dẫn đặt tour</a></li>
          </ul>
        </div>
        <div class="col-lg-3">
          <h2 class="h6 text-warning fw-bold">Liên hệ</h2>
          <p class="small footer-muted mb-2"><i class="bi bi-geo-alt me-2"></i>Cần Thơ, Việt Nam</p>
          <p class="small mb-2"><a href="tel:+84123456789"><i class="bi bi-telephone me-2"></i>+84 123 456 789</a></p>
          <p class="small"><a href="mailto:info@vivutour.com"><i class="bi bi-envelope me-2"></i>info@vivutour.com</a></p>
        </div>
      </div>
      <div class="footer-bottom footer-muted mt-4 pt-3 text-center small">© 2026 VivuTour - Vi vu trọn vẹn.</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.VIVUTOUR_CSRF = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  </script>
  <script src="/js/main.js?v=20260724-1"></script>
  <?= $this->section('page_specific_js') ?>
</body>

</html>
