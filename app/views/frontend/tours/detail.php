<?php
$this->layout('layouts/defaultCustomer', ['title' => ($tour['title'] ?? 'Chi tiết tour') . ' - VivuTour']);
$image = !empty($tour['imageUrl']) ? $tour['imageUrl'] : '/images/tour-placeholder.svg';
$detail = $tourDetail ?? [];
$lines = static function (?string $value): array {
    return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$value) ?: [])));
};
?>
<?php $this->start('page') ?>
<section class="bg-white border-bottom">
  <div class="container py-3">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="/">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="/tours">Tours</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?= $this->e($tour['title']) ?></li>
    </ol></nav>
  </div>
</section>

<div class="container py-5">
  <div class="row g-5">
    <div class="col-lg-8">
      <img src="<?= $this->e($image) ?>" class="w-100 rounded-4 shadow-sm object-fit-cover tour-detail-hero" alt="<?= $this->e($tour['title']) ?>">

      <div class="mt-4">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge text-bg-warning-subtle text-warning-emphasis"><?= $this->e($tour['categoryName'] ?? 'Tour') ?></span>
          <span class="badge text-bg-light"><i class="bi bi-clock me-1"></i><?= (int)$tour['numberDays'] ?> ngày</span>
          <span class="badge text-bg-light"><i class="bi bi-star-fill text-warning me-1"></i><?= number_format((float)$avgRating, 1) ?>/5</span>
        </div>
        <h1 class="display-6 fw-bold"><?= $this->e($tour['title']) ?></h1>
        <p class="text-secondary"><i class="bi bi-geo-alt-fill text-warning me-1"></i><?= $this->e($tour['locationNames'] ?? '') ?></p>
      </div>

      <section class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4 p-lg-5">
          <h2 class="h4 fw-bold">Giới thiệu hành trình</h2>
          <div class="text-secondary preserve-lines"><?= nl2br($this->e($tour['description'] ?: 'Thông tin chi tiết đang được cập nhật.')) ?></div>
        </div>
      </section>

      <section class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4 p-lg-5">
          <h2 class="h4 fw-bold mb-4">Thông tin tour</h2>
          <div class="row g-4 tour-facts">
            <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-geo fs-4 text-warning"></i><div><div class="small text-secondary">Khởi hành</div><strong><?= $this->e($detail['departurePoint'] ?: 'Đang cập nhật') ?></strong></div></div></div>
            <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-bus-front fs-4 text-warning"></i><div><div class="small text-secondary">Phương tiện</div><strong><?= $this->e($detail['transport'] ?: 'Đang cập nhật') ?></strong></div></div></div>
            <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-building fs-4 text-warning"></i><div><div class="small text-secondary">Lưu trú</div><strong><?= $this->e($detail['accommodation'] ?: 'Đang cập nhật') ?></strong></div></div></div>
            <div class="col-md-6"><div class="d-flex gap-3"><i class="bi bi-cup-hot fs-4 text-warning"></i><div><div class="small text-secondary">Bữa ăn</div><strong><?= $this->e($detail['meals'] ?: 'Theo chương trình') ?></strong></div></div></div>
            <div class="col-12"><div class="d-flex gap-3"><i class="bi bi-people fs-4 text-warning"></i><div><div class="small text-secondary">Phù hợp</div><strong><?= $this->e($detail['suitableFor'] ?: 'Nhiều nhóm khách') ?></strong></div></div></div>
          </div>
        </div>
      </section>

      <?php if ($lines($detail['highlights'] ?? '')) : ?>
      <section class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4 p-lg-5">
          <h2 class="h4 fw-bold mb-4">Điểm nổi bật</h2>
          <div class="row g-3"><?php foreach ($lines($detail['highlights']) as $highlight) : ?>
            <div class="col-md-6"><div class="d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span><?= $this->e($highlight) ?></span></div></div>
          <?php endforeach; ?></div>
        </div>
      </section>
      <?php endif; ?>

      <section class="mt-5">
        <h2 class="h4 fw-bold mb-4">Lịch trình chi tiết</h2>
        <?php if (empty($detail['itinerary'])) : ?>
          <div class="alert alert-light border">Lịch trình đang được cập nhật.</div>
        <?php else : ?>
          <div class="accordion shadow-sm rounded-4 overflow-hidden" id="tourItinerary">
            <?php foreach ($detail['itinerary'] as $index => $day) : ?>
              <div class="accordion-item border-0 border-bottom">
                <h3 class="accordion-header">
                  <button class="accordion-button <?= $index ? 'collapsed' : '' ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#day<?= (int)$day['dayNumber'] ?>" aria-expanded="<?= $index ? 'false' : 'true' ?>">
                    <span class="badge rounded-pill text-bg-warning me-3">Ngày <?= (int)$day['dayNumber'] ?></span><?= $this->e($day['title']) ?>
                  </button>
                </h3>
                <div id="day<?= (int)$day['dayNumber'] ?>" class="accordion-collapse collapse <?= !$index ? 'show' : '' ?>" data-bs-parent="#tourItinerary">
                  <div class="accordion-body p-4">
                    <p class="text-secondary preserve-lines"><?= nl2br($this->e($day['description'])) ?></p>
                    <div class="d-flex flex-wrap gap-3 small">
                      <?php if (!empty($day['meals'])) : ?><span><i class="bi bi-cup-hot text-warning me-1"></i>Bữa ăn: <?= $this->e($day['meals']) ?></span><?php endif; ?>
                      <?php if (!empty($day['accommodation'])) : ?><span><i class="bi bi-moon-stars text-warning me-1"></i>Lưu trú: <?= $this->e($day['accommodation']) ?></span><?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

      <section class="mt-5"><h2 class="h4 fw-bold mb-4">Điểm đến trong tour</h2>
        <?php if (!$tourLocations) : ?><p class="text-secondary">Chưa có thông tin địa điểm.</p><?php else : ?>
        <div class="row g-4"><?php foreach ($tourLocations as $location) : ?>
          <div class="col-md-6"><article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
            <?php $locImage = $location['images'][0]['imageUrl'] ?? '/images/tour-placeholder.svg'; ?>
            <img src="<?= $this->e($locImage) ?>" class="card-img-top object-fit-cover" alt="<?= $this->e($location['locationName']) ?>">
            <div class="card-body"><h3 class="h5 fw-bold mb-1"><?= $this->e($location['locationName']) ?></h3><p class="text-secondary mb-0"><i class="bi bi-pin-map me-1"></i><?= $this->e($location['city']) ?></p></div>
          </article></div>
        <?php endforeach; ?></div><?php endif; ?>
      </section>

      <section class="row g-4 mt-4">
        <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 fw-bold text-success"><i class="bi bi-check2-circle me-2"></i>Dịch vụ bao gồm</h2><ul class="mb-0 ps-3"><?php foreach ($lines($detail['includedServices'] ?? '') as $item) : ?><li class="mb-2"><?= $this->e($item) ?></li><?php endforeach; ?></ul></div></div></div>
        <div class="col-md-6"><div class="card h-100 border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Không bao gồm</h2><ul class="mb-0 ps-3"><?php foreach ($lines($detail['excludedServices'] ?? '') as $item) : ?><li class="mb-2"><?= $this->e($item) ?></li><?php endforeach; ?></ul></div></div></div>
      </section>

      <?php if (!empty($detail['notes']) || !empty($detail['cancellationPolicy'])) : ?>
      <section class="card border-0 shadow-sm rounded-4 mt-4"><div class="card-body p-4 p-lg-5">
        <h2 class="h4 fw-bold">Lưu ý và chính sách</h2>
        <?php if (!empty($detail['notes'])) : ?><h3 class="h6 fw-bold mt-4">Lưu ý trước chuyến đi</h3><ul><?php foreach ($lines($detail['notes']) as $item) : ?><li class="mb-2"><?= $this->e($item) ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if (!empty($detail['cancellationPolicy'])) : ?><h3 class="h6 fw-bold mt-4">Chính sách hủy</h3><p class="text-secondary mb-0"><?= $this->e($detail['cancellationPolicy']) ?></p><?php endif; ?>
      </div></section>
      <?php endif; ?>

      <section class="mt-5" id="reviews">
        <div class="d-flex justify-content-between align-items-center mb-4"><h2 class="h4 fw-bold mb-0">Đánh giá khách hàng</h2><span class="badge text-bg-warning fs-6"><i class="bi bi-star-fill me-1"></i><?= number_format((float)$avgRating, 1) ?></span></div>
        <?php if (AUTHGUARD()->isUserLoggedIn() && !AUTHGUARD()->isAdmin()) : ?>
          <form action="/tours/reviews" method="POST" class="card border-0 shadow-sm rounded-4 p-4 mb-4"><?= csrf_field() ?><input type="hidden" name="tourId" value="<?= (int)$tour['tourId'] ?>"><div class="row g-3"><div class="col-md-3"><label class="form-label" for="rating">Số sao</label><select id="rating" name="rating" class="form-select"><?php for ($i = 5; $i >= 1; $i--) : ?><option value="<?= $i ?>"><?= $i ?> sao</option><?php endfor; ?></select></div><div class="col-md-9"><label class="form-label" for="comment">Nhận xét</label><textarea id="comment" name="comment" rows="3" maxlength="2000" class="form-control <?= isset($errors['comment']) ? 'is-invalid' : '' ?>" placeholder="Chia sẻ trải nghiệm của bạn"></textarea><?php if (isset($errors['comment'])) : ?><div class="invalid-feedback"><?= $this->e($errors['comment']) ?></div><?php endif; ?></div><div class="col-12"><button class="btn btn-warning fw-semibold" type="submit">Gửi đánh giá</button></div></div></form>
        <?php elseif (!AUTHGUARD()->isUserLoggedIn()) : ?><div class="alert alert-light border"><a href="/login">Đăng nhập</a> để gửi đánh giá.</div><?php endif; ?>
        <?php if (!$reviews) : ?><p class="text-secondary">Chưa có đánh giá nào.</p><?php else : ?><div class="d-grid gap-3"><?php foreach ($reviews as $review) : ?><article class="bg-white border rounded-4 p-4"><div class="d-flex justify-content-between gap-3"><div><h3 class="h6 fw-bold mb-1"><?= $this->e($review['customerName'] ?? 'Khách hàng') ?></h3><div class="text-warning" aria-label="<?= (int)$review['rating'] ?> sao"><?php for ($i = 1; $i <= 5; $i++) : ?><i class="bi <?= $i <= (int)$review['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i><?php endfor; ?></div></div><small class="text-secondary"><?= date('d/m/Y', strtotime($review['createdAt'])) ?></small></div><p class="mb-0 mt-3 preserve-lines"><?= nl2br($this->e($review['comment'])) ?></p><?php if ((AUTHGUARD()->user()?->userId ?? -1) === (int)$review['customerUserId']) : ?><form action="/tours/reviews/delete/<?= (int)$review['reviewId'] ?>" method="POST" class="mt-3" onsubmit="return confirm('Xóa đánh giá này?')"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Xóa đánh giá</button></form><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
      </section>
    </div>

    <aside class="col-lg-4">
      <div class="card border-0 shadow rounded-4 sticky-lg-top booking-summary"><div class="card-body p-4">
        <span class="text-secondary">Giá từ / khách</span><div class="display-6 text-danger fw-bold mb-3"><?= number_format((float)$tour['basePrice'], 0, ',', '.') ?>₫</div>
        <div class="small text-secondary mb-3">Giá mẫu áp dụng từ <?= $this->e($detail['departurePoint'] ?: 'điểm khởi hành') ?>.</div><hr>
        <h2 class="h6 fw-bold">Lịch khởi hành</h2>
        <?php if (!$schedules) : ?><div class="alert alert-warning small">Chưa có lịch khởi hành sắp tới.</div><?php else : ?><div class="list-group list-group-flush mb-3"><?php foreach (array_slice($schedules, 0, 4) as $schedule) : ?><div class="list-group-item px-0 d-flex justify-content-between"><span><?= date('d/m/Y', strtotime($schedule['startDate'])) ?></span><span class="text-secondary small">còn <?= (int)($schedule['availableSlots'] ?? $schedule['maxParticipants']) ?> chỗ</span></div><?php endforeach; ?></div><?php endif; ?>
        <?php if ($schedules) : ?><a href="/bookings/checkout/<?= (int)$tour['tourId'] ?>" class="btn btn-warning btn-lg w-100 fw-bold"><i class="bi bi-calendar-check me-2"></i>Đặt tour ngay</a><?php endif; ?>
        <a href="/contact" class="btn btn-outline-dark w-100 mt-2">Yêu cầu tư vấn</a>
        <ul class="list-unstyled small text-secondary mt-4 mb-0 d-grid gap-2"><li><i class="bi bi-shield-check text-success me-2"></i>Thông tin được bảo vệ</li><li><i class="bi bi-receipt text-success me-2"></i>Chi phí hiển thị rõ ràng</li><li><i class="bi bi-headset text-success me-2"></i>Hỗ trợ khi cần</li></ul>
      </div></div>
    </aside>
  </div>

  <?php if (!empty($relatedTours)) : ?>
  <section class="mt-5 pt-4 border-top" aria-labelledby="relatedToursTitle">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
      <div>
        <span class="text-warning fw-semibold text-uppercase small">Có thể bạn quan tâm</span>
        <h2 id="relatedToursTitle" class="h3 fw-bold mb-0">Tour cùng danh mục</h2>
      </div>
      <a href="/tours?category=<?= (int)($tour['categoryId'] ?? -1) ?>" class="btn btn-outline-dark rounded-pill">Xem thêm tour</a>
    </div>
    <div class="row g-4">
      <?php foreach ($relatedTours as $relatedTour) : ?>
        <div class="col-md-6 col-xl-4"><?php $this->insert('frontend/partials/tour-card', ['tour' => $relatedTour]) ?></div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
<?php $this->stop() ?>
