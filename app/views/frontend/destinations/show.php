<?php
$this->layout('layouts/defaultCustomer', [
    'title' => $location->locationName . ' - Điểm đến VivuTour',
]);
$images = array_values($location->images);
$fallback = '/images/tour-placeholder.svg';
$heroImage = $images[0]['imageUrl'] ?? $fallback;
$gallery = [];
for ($index = 0; $index < 4; $index++) {
    $gallery[] = $images[$index]['imageUrl'] ?? ($images[0]['imageUrl'] ?? $fallback);
}
?>

<?php $this->start('page') ?>
<section class="destination-hero position-relative text-white" style="--destination-hero: url('<?= $this->e($heroImage) ?>')">
  <div class="container position-relative py-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb breadcrumb-light mb-5">
        <li class="breadcrumb-item"><a href="/" class="text-white text-decoration-none">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="/#popular-destinations" class="text-white text-decoration-none">Điểm đến</a></li>
        <li class="breadcrumb-item active text-white-75" aria-current="page"><?= $this->e($location->locationName) ?></li>
      </ol>
    </nav>

    <div class="col-lg-8">
      <h1 class="display-2 fw-bold mb-2"><?= $this->e($location->locationName) ?></h1>
      <p class="h3 text-warning fw-bold mb-3"><?= $this->e($guide['tagline']) ?></p>
      <p class="lead col-xl-9"><?= $this->e($guide['summary']) ?></p>
      <div class="d-flex flex-wrap gap-4 mt-4 destination-quick-facts">
        <span><i class="bi bi-thermometer-sun text-warning me-2"></i><strong><?= $this->e($guide['temperature']) ?></strong></span>
        <span><i class="bi bi-calendar3 text-warning me-2"></i><strong><?= $this->e($guide['season']) ?></strong></span>
        <span><i class="bi bi-brightness-high text-warning me-2"></i><strong><?= $this->e($guide['weather']) ?></strong></span>
      </div>
    </div>
  </div>
</section>

<nav class="destination-subnav bg-white border-bottom sticky-top">
  <div class="container">
    <ul class="nav justify-content-center gap-md-5">
      <li class="nav-item"><a class="nav-link active" href="#overview">Tổng quan</a></li>
      <li class="nav-item"><a class="nav-link" href="#highlights">Điểm nổi bật</a></li>
    </ul>
  </div>
</nav>

<section id="overview" class="py-5 py-lg-6 bg-white">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-5">
        <span class="text-warning fw-bold text-uppercase small">Tổng quan điểm đến</span>
        <h2 class="display-6 text-brand-blue fw-bold mt-2">Khám phá <?= $this->e($location->locationName) ?></h2>
        <p class="text-secondary fs-5"><?= $this->e($guide['summary']) ?></p>
        <p class="text-secondary">
          <?= $this->e($location->locationName) ?> là lựa chọn phù hợp cho du khách muốn kết hợp nghỉ ngơi,
          khám phá cảnh quan và tìm hiểu văn hóa tại <?= $this->e($location->city) ?>.
        </p>

        <div class="row g-3 my-4">
          <div class="col-6"><div class="destination-fact"><i class="bi bi-geo-alt"></i><span><small>Vị trí</small><strong><?= $this->e($location->city) ?></strong></span></div></div>
          <div class="col-6"><div class="destination-fact"><i class="bi bi-images"></i><span><small>Album</small><strong><?= count($images) ?> hình ảnh</strong></span></div></div>
          <div class="col-6"><div class="destination-fact"><i class="bi bi-translate"></i><span><small>Ngôn ngữ</small><strong>Tiếng Việt</strong></span></div></div>
          <div class="col-6"><div class="destination-fact"><i class="bi bi-cash"></i><span><small>Tiền tệ</small><strong>VND</strong></span></div></div>
        </div>

        <a href="/tours?location=<?= (int)$location->locationId ?>" class="btn btn-warning fw-bold px-4">
          Xem tour tại <?= $this->e($location->locationName) ?>
        </a>
      </div>

      <div class="col-lg-7">
        <div class="destination-gallery">
          <img src="<?= $this->e($gallery[0]) ?>" alt="<?= $this->e($location->locationName) ?>" class="gallery-main">
          <img src="<?= $this->e($gallery[1]) ?>" alt="Cảnh đẹp <?= $this->e($location->locationName) ?>" class="gallery-small gallery-one">
          <img src="<?= $this->e($gallery[2]) ?>" alt="Trải nghiệm <?= $this->e($location->locationName) ?>" class="gallery-small gallery-two">
          <img src="<?= $this->e($gallery[3]) ?>" alt="Du lịch <?= $this->e($location->locationName) ?>" class="gallery-wide">
        </div>
      </div>
    </div>
  </div>
</section>

<section id="highlights" class="py-5 bg-light-subtle border-top">
  <div class="container">
    <div class="text-center mb-5">
      <span class="text-warning fw-bold text-uppercase small">Gợi ý trải nghiệm</span>
      <h2 class="text-brand-blue fw-bold mt-2">Những trải nghiệm không thể bỏ lỡ</h2>
    </div>

    <div class="row g-4">
      <?php foreach ($guide['experiences'] as $index => $experience) : ?>
        <div class="col-sm-6 col-lg-3">
          <article class="card destination-experience h-100 border-0 shadow-sm overflow-hidden">
            <div class="ratio ratio-4x3 bg-light">
              <img src="<?= $this->e($gallery[$index]) ?>" alt="<?= $this->e($experience) ?>" class="w-100 h-100 object-fit-cover" loading="lazy">
            </div>
            <div class="card-body">
              <h3 class="h5 text-brand-blue fw-bold"><?= $this->e($experience) ?></h3>
              <span class="title-accent"></span>
              <p class="text-secondary small mb-0">
                <?= $this->e($guide['experienceDescriptions'][$index] ?? '') ?>
              </p>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php $this->stop() ?>
