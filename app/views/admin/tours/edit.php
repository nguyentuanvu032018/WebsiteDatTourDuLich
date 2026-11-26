<?php
$this->layout('layouts/admin', ['title' => 'Sửa tour', 'pageTitle' => 'Cập nhật tour', 'activeMenu' => 'tour']);
$detailData = $tourDetail ?? [];
$value = static fn(string $key, mixed $default = ''): mixed => $old[$key] ?? $detailData[$key] ?? $default;
?>
<?php $this->start('page') ?>
<div class="row g-4">
  <div class="col-xl-8">
    <form action="/admin/tours/update/<?= (int)$tour->tourId ?>" method="POST" class="card border-0 shadow-sm rounded-4 p-4 p-lg-5"><?= csrf_field() ?>
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Tour #<?= (int)$tour->tourId ?></h2><a href="/admin/tours" class="btn btn-outline-secondary btn-sm">Hủy</a>
      </div>
      <?php if (isset($errors['tour'])) : ?><div class="alert alert-danger"><?= $this->e($errors['tour']) ?></div><?php endif; ?>
      <div class="row g-3">
        <div class="col-12"><label for="title" class="form-label">Tên tour *</label><input id="title" name="title" class="form-control" value="<?= $this->e($old['title'] ?? $tour->title) ?>" required></div>
        <div class="col-md-4"><label for="categoryId" class="form-label">Danh mục *</label><select id="categoryId" name="categoryId" class="form-select" required><?php foreach ($categories as $category) : ?><option value="<?= (int)$category['categoryId'] ?>" <?= (int)($old['categoryId'] ?? $tour->categoryId) === (int)$category['categoryId'] ? 'selected' : '' ?>><?= $this->e($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label for="basePrice" class="form-label">Giá/khách *</label>
          <div class="input-group has-validation"><input id="basePrice" type="number" min="0.01" step="0.01" name="basePrice" class="form-control <?= isset($errors['basePrice']) ? 'is-invalid' : '' ?>" value="<?= $this->e($old['basePrice'] ?? $tour->basePrice) ?>" placeholder="Ví dụ: 1500000" required><span class="input-group-text">₫</span><?php if (isset($errors['basePrice'])) : ?><div class="invalid-feedback"><?= $this->e($errors['basePrice']) ?></div><?php endif; ?></div>
          <div class="form-text">Nhập số lớn hơn 0, không dùng dấu phân cách hàng nghìn.</div>
        </div>
        <div class="col-md-4"><label for="numberDay" class="form-label">Số ngày *</label><input id="numberDay" type="number" min="1" name="numberDay" class="form-control" value="<?= $this->e($old['numberDay'] ?? $tour->numberDay) ?>" required></div>
        <div class="col-12"><label class="form-label">Địa điểm *</label>
          <div class="row g-2 border rounded-4 p-3"><?php $selected = array_map('intval', $old['locationIds'] ?? $tour->locationIds);
                                                    foreach ($locations as $location) : ?><div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="locationIds[]" value="<?= (int)$location['locationId'] ?>" <?= in_array((int)$location['locationId'], $selected, true) ? 'checked' : '' ?>><span class="form-check-label"><?= $this->e($location['locationName']) ?> <small class="text-secondary">(<?= $this->e($location['city']) ?>)</small></span></label></div><?php endforeach; ?></div>
        </div>
        <div class="col-12"><label for="description" class="form-label">Mô tả chung</label><textarea id="description" name="description" rows="6" class="form-control"><?= $this->e($old['description'] ?? $tour->description) ?></textarea></div>

        <div class="col-12">
          <hr class="my-3">
          <h3 class="h5 fw-bold">Thông tin chi tiết hiển thị cho khách hàng</h3>
        </div>
        <div class="col-md-6"><label for="departurePoint" class="form-label">Điểm khởi hành</label><input id="departurePoint" name="departurePoint" maxlength="150" class="form-control" value="<?= $this->e($value('departurePoint')) ?>" placeholder="Ví dụ: Hà Nội"></div>
        <div class="col-md-6"><label for="transport" class="form-label">Phương tiện</label><input id="transport" name="transport" maxlength="200" class="form-control" value="<?= $this->e($value('transport')) ?>" placeholder="Ô tô, tàu, ca nô..."></div>
        <div class="col-md-6"><label for="accommodation" class="form-label">Lưu trú</label><input id="accommodation" name="accommodation" maxlength="200" class="form-control" value="<?= $this->e($value('accommodation')) ?>"></div>
        <div class="col-md-6"><label for="meals" class="form-label">Bữa ăn</label><input id="meals" name="meals" maxlength="200" class="form-control" value="<?= $this->e($value('meals')) ?>"></div>
        <div class="col-12"><label for="suitableFor" class="form-label">Đối tượng phù hợp</label><input id="suitableFor" name="suitableFor" maxlength="255" class="form-control" value="<?= $this->e($value('suitableFor')) ?>"></div>
        <div class="col-md-6"><label for="highlights" class="form-label">Điểm nổi bật <small class="text-secondary">(mỗi dòng một ý)</small></label><textarea id="highlights" name="highlights" rows="7" class="form-control"><?= $this->e($value('highlights')) ?></textarea></div>
        <div class="col-md-6"><label for="includedServices" class="form-label">Dịch vụ bao gồm <small class="text-secondary">(mỗi dòng một ý)</small></label><textarea id="includedServices" name="includedServices" rows="7" class="form-control"><?= $this->e($value('includedServices')) ?></textarea></div>
        <div class="col-md-6"><label for="excludedServices" class="form-label">Dịch vụ không bao gồm</label><textarea id="excludedServices" name="excludedServices" rows="7" class="form-control"><?= $this->e($value('excludedServices')) ?></textarea></div>
        <div class="col-md-6"><label for="notes" class="form-label">Lưu ý</label><textarea id="notes" name="notes" rows="7" class="form-control"><?= $this->e($value('notes')) ?></textarea></div>
        <div class="col-12"><label for="cancellationPolicy" class="form-label">Chính sách hủy</label><textarea id="cancellationPolicy" name="cancellationPolicy" rows="3" class="form-control"><?= $this->e($value('cancellationPolicy')) ?></textarea></div>
        <div class="col-12"><label for="itineraryText" class="form-label">Lịch trình từng ngày</label><textarea id="itineraryText" name="itineraryText" rows="10" class="form-control font-monospace <?= isset($errors['itineraryText']) ? 'is-invalid' : '' ?>" placeholder="Ngày 1|Hà Nội - Sa Pa|Nội dung lịch trình|Trưa, tối|Khách sạn tại Sa Pa"><?= $this->e($value('itineraryText')) ?></textarea>
          <div class="form-text">Mỗi ngày một dòng, phân cách bằng dấu | theo thứ tự: Ngày | Tiêu đề | Mô tả | Bữa ăn | Lưu trú.</div><?php if (isset($errors['itineraryText'])) : ?><div class="invalid-feedback"><?= $this->e($errors['itineraryText']) ?></div><?php endif; ?>
        </div>

        <div class="col-12"><button class="btn btn-warning fw-bold px-4">Lưu thay đổi</button></div>
      </div>
    </form>
  </div>
  <div class="col-xl-4">
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
      <h2 class="h5 fw-bold">Thêm lịch khởi hành</h2>
      <form action="/admin/tours/schedule" method="POST"><?= csrf_field() ?><input type="hidden" name="tourId" value="<?= (int)$tour->tourId ?>">
        <div class="mb-3"><label class="form-label">Ngày bắt đầu</label><input type="date" name="startDate" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Ngày kết thúc</label><input type="date" name="endDate" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Số khách tối đa</label><input type="number" min="1" name="maxParticipants" class="form-control" required></div><button class="btn btn-dark w-100">Thêm lịch</button>
      </form>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
      <div class="card-header bg-white border-0 p-4">
        <h2 class="h5 fw-bold mb-0">Các lịch đã tạo</h2>
      </div>
      <div class="list-group list-group-flush"><?php foreach ($schedules as $schedule) : ?><div class="list-group-item px-4 py-3"><strong><?= date('d/m/Y', strtotime($schedule['startDate'])) ?> - <?= date('d/m/Y', strtotime($schedule['endDate'])) ?></strong><small class="d-block text-secondary">Tối đa <?= (int)$schedule['maxParticipants'] ?> khách</small></div><?php endforeach; ?><?php if (!$schedules) : ?><div class="p-4 text-secondary">Chưa có lịch khởi hành.</div><?php endif; ?></div>
    </div>
  </div>
</div>
<?php $this->stop() ?>