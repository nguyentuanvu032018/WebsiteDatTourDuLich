<?php
$image = !empty($tour['imageUrl']) ? $tour['imageUrl'] : '/images/tour-placeholder.svg';
$rating = (float)($tour['averageRating'] ?? 0);
$tourId = (int)($tour['tourId'] ?? 0);
$detailUrl = '/tours/detail/' . $tourId;
?>
<article class="card h-100 border-0 shadow-sm tour-card overflow-hidden">
  <a href="<?= $detailUrl ?>" class="ratio ratio-16x9 bg-light overflow-hidden" aria-label="Xem chi tiết <?= $this->e($tour['title'] ?? 'tour du lịch') ?>">
    <img src="<?= $this->e($image) ?>" alt="<?= $this->e($tour['title'] ?? 'Tour du lịch') ?>" class="w-100 h-100 object-fit-cover" loading="lazy">
  </a>

  <div class="card-body d-flex flex-column p-4">
    <div class="d-flex justify-content-between gap-2 mb-2">
      <span class="badge text-bg-warning-subtle text-warning-emphasis"><?= $this->e($tour['categoryName'] ?? 'Tour') ?></span>
      <span class="small text-secondary"><i class="bi bi-clock me-1"></i><?= (int)($tour['numberDays'] ?? 1) ?> ngày</span>
    </div>

    <h3 class="h5 fw-bold mb-2">
      <a class="text-dark text-decoration-none tour-title-link" href="<?= $detailUrl ?>"><?= $this->e($tour['title'] ?? '') ?></a>
    </h3>

    <p class="text-secondary small mb-3 line-clamp-2">
      <i class="bi bi-geo-alt-fill text-warning me-1"></i><?= $this->e($tour['locationNames'] ?? 'Đang cập nhật địa điểm') ?>
    </p>

    <div class="mt-auto">
      <div class="d-flex align-items-end justify-content-between gap-3 mb-3">
        <div>
          <span class="text-secondary small d-block">Giá từ / khách</span>
          <strong class="text-danger fs-5"><?= number_format((float)($tour['basePrice'] ?? 0), 0, ',', '.') ?>₫</strong>
        </div>
        <span class="small text-nowrap"><i class="bi bi-star-fill text-warning me-1"></i><?= number_format($rating, 1) ?> <span class="text-secondary">(<?= (int)($tour['reviewCount'] ?? 0) ?>)</span></span>
      </div>

      <a href="<?= $detailUrl ?>" class="btn btn-outline-dark w-100 fw-semibold tour-detail-button">
        Xem chi tiết tour <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>
  </div>
</article>
