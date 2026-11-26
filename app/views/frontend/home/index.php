<?php $this->layout('layouts/defaultCustomer', ['title' => 'VivuTour - Khám phá Việt Nam']) ?>
<?php
$visiblePromotions = array_slice($promotions ?? [], 0, 3);
$promotionCount = count($visiblePromotions);
?>

<?php $this->start('page') ?>
<section class="hero-home position-relative" style="--hero-image: url('/images/tour-placeholder_baner.jpg?v=20260723-1')">
  <div class="container position-relative py-5">
    <div class="row align-items-center g-5 py-lg-5">
      <div class="col-lg-7 text-white">
        <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3">
          <i class="bi bi-airplane-fill"></i> VivuTour Hành trình đáng nhớ bắt đầu tại đây
        </span>
        <h1 class="display-3 fw-bold mb-3">Khám phá Việt Nam<br>theo cách của bạn</h1>
        <p class="lead text-white-75 mb-4 col-xl-9">
          Tour rõ ràng lịch trình, minh bạch chi phí và hỗ trợ xuyên suốt chuyến đi.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="/tours" class="btn btn-warning btn-lg px-4 fw-bold">Xem tất cả tour</a>
          <a href="/contact" class="btn btn-outline-light btn-lg px-4 fw-semibold">
            <i class="bi bi-headset me-2"></i>Tư vấn riêng
          </a>
        </div>
      </div>

      <div class="col-lg-5">
        <form action="/" method="GET" class="card home-search-card border-0 shadow-lg p-4" aria-label="Tìm tour">
          <h2 class="h4 text-brand-blue fw-bold mb-4">Tìm chuyến đi phù hợp</h2>
          <div class="mb-3">
            <label class="form-label fw-semibold" for="keyword">Từ khóa</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
              <input id="keyword" name="keyword" class="form-control" value="<?= $this->e($searchKeyword ?? '') ?>" placeholder="Bạn muốn đi đâu?">
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="category">Danh mục</label>
              <select id="category" name="category" class="form-select">
                <option value="-1">Tất cả danh mục</option>
                <?php foreach ($categories as $category) : ?>
                  <option value="<?= (int)$category['categoryId'] ?>" <?= (int)$selectedCategory === (int)$category['categoryId'] ? 'selected' : '' ?>>
                    <?= $this->e($category['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold" for="location">Địa điểm</label>
              <select id="location" name="location" class="form-select">
                <option value="-1">Tất cả địa điểm</option>
                <?php foreach ($locations as $location) : ?>
                  <option value="<?= (int)$location['locationId'] ?>" <?= (int)$selectedLocation === (int)$location['locationId'] ? 'selected' : '' ?>>
                    <?= $this->e($location['locationName']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <button class="btn btn-warning fw-bold w-100 mt-4 py-2" type="submit">
            <i class="bi bi-search me-2"></i>Tìm tour
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<?php if ($promotionCount > 0) : ?>
  <section class="container home-coupon-wrap">
    <div class="row g-3">
      <?php foreach ($visiblePromotions as $promotion) : ?>
        <div class="<?= $promotionCount === 1 ? 'col-12' : ($promotionCount === 2 ? 'col-12 col-lg-6' : 'col-12 col-lg-4') ?>">
          <article class="home-coupon h-100 border border-warning rounded-4 bg-warning-subtle shadow-sm p-3 p-lg-4">
            <div class="row align-items-center g-3 <?= $promotionCount === 1 ? 'text-center text-lg-start' : '' ?>">
              <div class="<?= $promotionCount === 1 ? 'col-lg-1' : 'col-auto' ?> text-warning">
                <i class="bi bi-ticket-perforated-fill <?= $promotionCount === 1 ? 'display-5' : 'fs-1' ?>"></i>
              </div>

              <div class="<?= $promotionCount === 1 ? 'col-lg-3' : 'col' ?>">
                <div class="fw-bold <?= $promotionCount === 1 ? 'fs-5' : 'h6 mb-1' ?>">Mã giảm giá cho hành trình mới</div>
                <small class="text-secondary">Áp dụng trực tiếp tại bước đặt tour</small>
              </div>

              <div class="<?= $promotionCount === 1 ? 'col-lg-3' : 'col-12' ?>">
                <div class="coupon-code border border-warning border-dashed rounded-3 py-2 px-3 font-monospace fw-bold text-center text-truncate <?= $promotionCount === 1 ? 'fs-4' : 'fs-5' ?>">
                <?= $this->e($promotion['couponCode']) ?>
                </div>
              </div>

              <div class="<?= $promotionCount === 1 ? 'col-lg-2' : 'col' ?>">
                <span class="d-block small text-secondary">Giảm ngay</span>
                <strong class="text-danger fs-4 text-nowrap"><?= number_format((float)$promotion['discountValue'], 0, ',', '.') ?>₫</strong>
              </div>

              <div class="<?= $promotionCount === 1 ? 'col-lg-3 text-lg-end' : 'col-auto' ?>">
                <button
                  class="btn btn-warning fw-bold text-nowrap <?= $promotionCount === 1 ? 'px-4' : '' ?>"
                  type="button"
                  data-copy-coupon="<?= $this->e($promotion['couponCode']) ?>"
                  data-copy-label="Sao chép mã">
                  <i class="bi bi-copy me-2"></i><span>Sao chép mã</span>
                </button>
              </div>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>

<section id="popular-destinations" class="py-5 bg-white">
  <div class="container">
    <div class="section-heading d-flex align-items-end justify-content-between gap-3 mb-4">
      <div>
        <span class="text-warning fw-bold text-uppercase small">Khám phá Việt Nam</span>
        <h2 class="fw-bold text-brand-blue mb-0">Điểm đến phổ biến</h2>
      </div>
      <a href="/tours" class="link-brand fw-semibold text-decoration-none">Xem tất cả điểm đến</a>
    </div>

    <?php if (empty($popularLocations)) : ?>
      <div class="alert alert-light border">Chưa có dữ liệu địa điểm để hiển thị.</div>
    <?php else : ?>
      <div class="row g-3">
        <?php foreach ($popularLocations as $location) : ?>
          <?php $locationImage = $location['firstImage'] ?: '/images/tour-placeholder.svg'; ?>
          <div class="col-6 col-md-4 col-xl-2">
            <a class="destination-card card border-0 shadow-sm overflow-hidden text-decoration-none h-100" href="/destinations/<?= (int)$location['locationId'] ?>">
              <div class="ratio destination-ratio bg-light">
                <img src="<?= $this->e($locationImage) ?>" class="w-100 h-100 object-fit-cover" alt="<?= $this->e($location['locationName']) ?>" loading="lazy">
                <span class="destination-overlay"></span>
                <span class="destination-copy">
                  <strong><?= $this->e($location['locationName']) ?></strong>
                  <small><?= $this->e($location['city']) ?></small>
                </span>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="featured-tours-section bg-white">
  <div class="container">
    <div class="section-heading d-flex align-items-end justify-content-between gap-3 mb-4">
      <div>
        <span class="text-warning fw-bold text-uppercase small">Hành trình được yêu thích</span>
        <h2 class="fw-bold text-brand-blue mb-0">Tour nổi bật</h2>
      </div>
      <a href="/tours" class="btn btn-outline-warning text-dark fw-semibold">Xem tất cả</a>
    </div>

    <?php if (!$tours) : ?>
      <div class="alert alert-info">Chưa có tour phù hợp. Hãy thử bộ lọc khác.</div>
    <?php else : ?>
      <div class="row g-4">
        <?php foreach ($tours as $tour) : ?>
          <div class="col-sm-6 col-lg-4 col-xl-3">
            <?php $this->insert('frontend/partials/tour-card', ['tour' => $tour]) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <hr class="featured-divider">
  </div>
</section>

<section class="py-5 bg-white">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="benefit-card border rounded-4 p-4 h-100 d-flex gap-3">
          <span class="benefit-icon"><i class="bi bi-shield-check"></i></span>
          <div>
            <h2 class="h5 text-brand-blue fw-bold">Đặt tour an toàn</h2>
            <p class="text-secondary mb-0">Thanh toán bảo mật, xác nhận nhanh và thông tin rõ ràng.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="benefit-card border rounded-4 p-4 h-100 d-flex gap-3">
          <span class="benefit-icon"><i class="bi bi-receipt"></i></span>
          <div>
            <h2 class="h5 text-brand-blue fw-bold">Chi phí minh bạch</h2>
            <p class="text-secondary mb-0">Giá rõ ràng, không phát sinh chi phí ẩn trong quá trình đặt tour.</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="benefit-card border rounded-4 p-4 h-100 d-flex gap-3">
          <span class="benefit-icon"><i class="bi bi-headset"></i></span>
          <div>
            <h2 class="h5 text-brand-blue fw-bold">Hỗ trợ nhanh</h2>
            <p class="text-secondary mb-0">Đội ngũ hỗ trợ luôn sẵn sàng đồng hành cùng bạn.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php $this->stop() ?>
