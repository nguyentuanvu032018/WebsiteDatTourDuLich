<?php
if (!isset($presence) || !is_array($presence)) {
  try {
    $presence = (new \App\Models\UserPresence(PDO()))->snapshot();
  } catch (\Throwable) {
    $presence = ['totalOnline' => 0];
  }
}
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $this->e($title ?? 'Quản trị') ?> - VivuTour Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/css/admin.css?v=20260723-5" rel="stylesheet">
  <link rel="icon" type="image/jpeg" href="/images/logo.jpg">
  <?= $this->section('page_specific_css') ?>
</head>

<body class="bg-light d-flex min-vh-100">
  <aside id="adminSidebar" class="offcanvas-lg offcanvas-start bg-dark text-white d-flex flex-column shadow" tabindex="-1" style="width:280px" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header border-bottom border-secondary">
      <a
        id="adminSidebarLabel"
        href="/admin/dashboard"
        class="offcanvas-title d-flex align-items-center gap-2 text-white text-decoration-none">
        <img
          src="/images/logo.jpg"
          alt="Logo VivuTour"
          class="rounded-circle border border-warning border-2 bg-white p-1 object-fit-contain flex-shrink-0"
          width="50"
          height="50">

        <span class="fs-5 fw-bold">
          <span class="text-warning">Vivu</span><span class="text-white">Admin</span>
        </span>
      </a>

      <button
        type="button"
        class="btn-close btn-close-white d-lg-none"
        data-bs-dismiss="offcanvas"
        aria-label="Đóng"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3 overflow-auto">
      <?php
      $items = [
        ['dashboard', '/admin/dashboard', 'bi-grid-1x2-fill', 'Tổng quan'],
        ['tour', '/admin/tours', 'bi-compass-fill', 'Quản lý tour'],
        ['category', '/admin/categories', 'bi-folder-fill', 'Danh mục tour'],
        ['location', '/admin/locations', 'bi-geo-alt-fill', 'Địa điểm & ảnh'],
        ['customer', '/admin/customers', 'bi-people-fill', 'Khách hàng'],
        ['booking', '/admin/bookings', 'bi-receipt-cutoff', 'Đơn đặt tour'],
        ['payment', '/admin/payments', 'bi-credit-card-fill', 'Thanh toán'],
        ['promotion', '/admin/promotions', 'bi-tags-fill', 'Khuyến mãi'],
        ['review', '/admin/reviews', 'bi-chat-square-heart-fill', 'Đánh giá'],
        ['contact', '/admin/contacts', 'bi-envelope-paper-fill', 'Liên hệ'],
      ];
      ?>
      <nav class="nav nav-pills flex-column gap-1">
        <?php foreach ($items as [$key, $href, $icon, $label]) : ?>
          <a href="<?= $href ?>" class="nav-link px-3 py-2 <?= ($activeMenu ?? '') === $key ? 'active bg-warning text-dark fw-bold' : 'text-white-50' ?>">
            <i class="bi <?= $icon ?> me-2"></i><?= $label ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="mt-auto pt-4">
        <a href="/" class="btn btn-outline-light w-100 mb-2"><i class="bi bi-house-door me-2"></i>Xem website</a>
        <form action="/logout" method="POST" onsubmit="return confirm('Đăng xuất khỏi hệ thống?')">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-warning w-100 fw-bold"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</button>
        </form>
      </div>
    </div>
  </aside>

  <div class="d-flex flex-column flex-grow-1" style="min-width:0">
    <header class="bg-white border-bottom shadow-sm sticky-top d-flex align-items-center px-3 px-lg-4" style="height:64px;z-index:1020">
      <button class="btn btn-outline-dark d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"><i class="bi bi-list"></i></button>
      <h1 class="h6 fw-bold text-uppercase mb-0 text-truncate"><?= $this->e($pageTitle ?? $title ?? 'Quản trị') ?></h1>
      <div class="ms-auto d-flex align-items-center gap-2">
        <span id="adminPresencePill" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2">
          <span class="presence-dot me-1"></span><span id="adminPresenceCount"><?= (int)($presence['totalOnline'] ?? 0) ?></span> đang online
          <i class="bi bi-broadcast ms-1"></i>
        </span>
        <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-person-circle me-1"></i><?= $this->e($currentUser?->name ?? 'Quản trị viên') ?></span>
      </div>
    </header>

    <main class="p-3 p-lg-4 flex-grow-1">
      <?php if ($flashSuccess) : ?><div class="alert alert-success alert-dismissible fade show shadow-sm"><i class="bi bi-check-circle-fill me-2"></i><?= $this->e($flashSuccess) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?php if ($flashError) : ?><div class="alert alert-danger alert-dismissible fade show shadow-sm"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $this->e($flashError) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
      <?= $this->section('page') ?>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    window.VIVUTOUR_CSRF = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  </script>
  <script src="/js/admin.js?v=20260725-1"></script>
  <?= $this->section('page_specific_js') ?>
</body>

</html>
