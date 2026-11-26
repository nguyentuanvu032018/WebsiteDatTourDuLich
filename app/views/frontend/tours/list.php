<?php $this->layout('layouts/defaultCustomer', ['title' => 'Danh sách tour - VivuTour']) ?>
<?php $this->start('page') ?>
<section class="page-hero page-hero-image py-5" style="--page-hero-image:url('/images/banner-tour.png?v=2');background-image:linear-gradient(90deg,rgba(3,32,56,.78),rgba(3,32,56,.42)),url('/images/banner-tour.png?v=2');background-position:center;background-size:cover;min-height:360px"><div class="container py-3 text-center text-white"><h1 class="display-5 fw-bold">Chọn hành trình tiếp theo</h1><p class="lead mb-0">Khám phá các hành trình từ Bắc vào Nam, phân loại theo danh mục và địa điểm.</p></div></section>
<div class="container py-5">
  <form action="/tours" method="GET" class="card border-0 shadow-sm rounded-4 p-3 p-lg-4 mb-5"><div class="row g-3 align-items-end">
    <div class="col-lg-4"><label for="keyword" class="form-label">Từ khóa</label><input id="keyword" name="keyword" class="form-control" value="<?= $this->e($keyword ?? '') ?>" placeholder="Tên tour hoặc mô tả"></div>
    <div class="col-md-5 col-lg-3"><label for="category" class="form-label">Danh mục vùng</label><select id="category" name="category" class="form-select"><option value="-1">Tất cả danh mục</option><?php foreach ($categories as $category) : ?><option value="<?= (int)$category['categoryId'] ?>" <?= (int)$selectedCategory === (int)$category['categoryId'] ? 'selected' : '' ?>><?= $this->e($category['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-5 col-lg-3"><label for="location" class="form-label">Địa điểm</label><select id="location" name="location" class="form-select"><option value="-1">Tất cả địa điểm</option><?php foreach ($locations as $location) : ?><option value="<?= (int)$location['locationId'] ?>" <?= (int)$selectedLocation === (int)$location['locationId'] ? 'selected' : '' ?>><?= $this->e($location['locationName']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2 col-lg-2 d-grid"><button class="btn btn-warning fw-semibold"><i class="bi bi-search me-1"></i>Tìm</button></div>
  </div></form>

  <div class="d-flex justify-content-between align-items-center mb-4"><h2 class="h4 fw-bold mb-0"><?= (int)($totalTours ?? count($tours)) ?> tour phù hợp</h2><a href="/tours" class="link-secondary text-decoration-none">Xóa bộ lọc</a></div>
  <?php if (!$tours) : ?><div class="text-center py-5 bg-white rounded-4 border"><i class="bi bi-compass display-3 text-secondary"></i><h2 class="h4 mt-3">Không tìm thấy tour</h2><p class="text-secondary">Hãy thay đổi điều kiện tìm kiếm.</p></div><?php else : ?><div class="row g-4"><?php foreach ($tours as $tour) : ?><div class="col-md-6 col-xl-4"><?php $this->insert('frontend/partials/tour-card', ['tour' => $tour]) ?></div><?php endforeach; ?></div><?php endif; ?>

  <?php if (($totalPages ?? 1) > 1) : ?>
    <?php $query = ['keyword' => $keyword ?? '', 'category' => $selectedCategory ?? -1, 'location' => $selectedLocation ?? -1]; ?>
    <nav class="mt-5" aria-label="Phân trang tour"><ul class="pagination justify-content-center flex-wrap">
      <li class="page-item <?= ($page ?? 1) <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= http_build_query($query + ['page' => max(1, ($page ?? 1) - 1)]) ?>">Trước</a></li>
      <?php for ($p = 1; $p <= $totalPages; $p++) : ?><li class="page-item <?= $p === (int)$page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query($query + ['page' => $p]) ?>"><?= $p ?></a></li><?php endfor; ?>
      <li class="page-item <?= ($page ?? 1) >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= http_build_query($query + ['page' => min($totalPages, ($page ?? 1) + 1)]) ?>">Sau</a></li>
    </ul></nav>
  <?php endif; ?>
</div>
<?php $this->stop() ?>
