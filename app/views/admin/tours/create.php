<?php
$this->layout('layouts/admin', ['title' => 'Thêm tour', 'pageTitle' => 'Tạo hành trình mới', 'activeMenu' => 'tour']);
$value = static fn(string $key, mixed $default = ''): mixed => $old[$key] ?? $default;
?>
<?php $this->start('page') ?>
<div class="row justify-content-center">
  <div class="col-xl-10">
    <form action="/admin/tours/store" method="POST" class="card border-0 shadow-sm rounded-4 p-4 p-lg-5"><?= csrf_field() ?>
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Thông tin tour</h2><a href="/admin/tours" class="btn btn-outline-secondary btn-sm">Hủy</a>
      </div>
      <?php if (isset($errors['tour'])) : ?><div class="alert alert-danger"><?= $this->e($errors['tour']) ?></div><?php endif; ?>
      <div class="row g-3">
        <div class="col-12"><label for="title" class="form-label">Tên tour *</label><input id="title" name="title" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" value="<?= $this->e($value('title')) ?>" required><?php if (isset($errors['title'])) : ?><div class="invalid-feedback"><?= $this->e($errors['title']) ?></div><?php endif; ?></div>
        <div class="col-md-4"><label for="categoryId" class="form-label">Danh mục *</label><select id="categoryId" name="categoryId" class="form-select <?= isset($errors['categoryId']) ? 'is-invalid' : '' ?>" required>
            <option value="">Chọn danh mục</option><?php foreach ($categories as $category) : ?><option value="<?= (int)$category['categoryId'] ?>" <?= (int)$value('categoryId', 0) === (int)$category['categoryId'] ? 'selected' : '' ?>><?= $this->e($category['name']) ?></option><?php endforeach; ?>
          </select></div>
        <div class="col-md-4"><label for="basePrice" class="form-label">Giá/khách *</label>
          <div class="input-group has-validation"><input id="basePrice" type="number" min="0.01" step="0.01" name="basePrice" class="form-control <?= isset($errors['basePrice']) ? 'is-invalid' : '' ?>" value="<?= $this->e($value('basePrice')) ?>" placeholder="Ví dụ: 1500000" required><span class="input-group-text">₫</span><?php if (isset($errors['basePrice'])) : ?><div class="invalid-feedback"><?= $this->e($errors['basePrice']) ?></div><?php endif; ?></div>
        </div>
        <div class="col-md-4"><label for="numberDay" class="form-label">Số ngày *</label><input id="numberDay" type="number" min="1" name="numberDay" class="form-control" value="<?= $this->e($value('numberDay', 1)) ?>" required></div>
        <div class="col-12">
          <div class="row g-2">
            <div class="col-lg-6">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="locationSearch" type="search" class="form-control" placeholder="Tìm địa điểm hoặc tỉnh thành..." autocomplete="off">
              </div>
            </div>
            <div class="col-lg-3">
              <select id="locationCityFilter" class="form-select" aria-label="Lọc địa điểm theo tỉnh thành">
                <option value="">Tất cả tỉnh thành</option>
                <?php
                $cities = array_values(array_unique(array_filter(array_map(
                  static fn(array $location): string => trim((string)($location['city'] ?? '')),
                  $locations
                ))));
                natcasesort($cities);
                foreach ($cities as $city) :
                ?>
                  <option value="<?= $this->e($city) ?>"><?= $this->e($city) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-lg-3 d-flex gap-2">
              <button id="showSelectedLocations" type="button" class="btn btn-outline-primary flex-grow-1" aria-pressed="false">
                <i class="bi bi-check2-square me-1"></i>Đã chọn (<span id="selectedLocationCount">0</span>)
              </button>
              <button id="resetLocationFilter" type="button" class="btn btn-outline-secondary" title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="col-12"><label class="form-label">Địa điểm *</label>
          <div id="locationOptions" class="row g-2 border rounded-4 p-3 <?= isset($errors['locationIds']) ? 'border-danger' : '' ?>" style="max-height: 420px; overflow-y: auto;"><?php foreach ($locations as $location) : ?><div class="col-md-6 col-xl-4 location-option" data-location-name="<?= $this->e($location['locationName']) ?>" data-location-city="<?= $this->e($location['city']) ?>"><label class="form-check rounded-2 px-2 py-1"><input class="form-check-input" type="checkbox" name="locationIds[]" value="<?= (int)$location['locationId'] ?>" <?= in_array((int)$location['locationId'], array_map('intval', $old['locationIds'] ?? []), true) ? 'checked' : '' ?>><span class="form-check-label"><?= $this->e($location['locationName']) ?> <small class="text-secondary">(<?= $this->e($location['city']) ?>)</small></span></label></div><?php endforeach; ?><div id="noLocationResult" class="col-12 text-center text-secondary py-4 d-none"><i class="bi bi-search d-block fs-3 mb-2"></i>Không tìm thấy địa điểm phù hợp.</div></div>
          <div class="d-flex justify-content-between mt-2 small text-secondary">
            <span>Hiển thị <strong id="visibleLocationCount"><?= count($locations) ?></strong>/<?= count($locations) ?> địa điểm</span>
            <span>Danh sách tự động lọc khi nhập</span>
          </div>
          <?php if (isset($errors['locationIds'])) : ?><div class="text-danger small mt-1"><?= $this->e($errors['locationIds']) ?></div><?php endif; ?>
        </div>
        <div class="col-12"><label for="description" class="form-label">Mô tả chung</label><textarea id="description" name="description" rows="6" class="form-control"><?= $this->e($value('description')) ?></textarea></div>

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

        <div class="col-12"><button class="btn btn-warning fw-bold px-4">Lưu tour</button></div>
      </div>
    </form>
  </div>
</div>
<?php $this->stop() ?>
<?php $this->start('page_specific_css') ?>
<style>
  #locationOptions .location-option label {
    transition: background-color .15s ease;
  }

  #locationOptions .location-option:has(input:checked) label {
    background-color: rgba(13, 110, 253, .09);
  }
</style>
<?php $this->stop() ?>
<?php $this->start('page_specific_js') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const search = document.getElementById('locationSearch');
  const cityFilter = document.getElementById('locationCityFilter');
  const selectedButton = document.getElementById('showSelectedLocations');
  const resetButton = document.getElementById('resetLocationFilter');
  const selectedCount = document.getElementById('selectedLocationCount');
  const visibleCount = document.getElementById('visibleLocationCount');
  const emptyMessage = document.getElementById('noLocationResult');
  const optionsContainer = document.getElementById('locationOptions');
  const options = Array.from(optionsContainer?.querySelectorAll('.location-option') || []);
  let selectedOnly = false;

  if (!search || !cityFilter || !selectedButton || !resetButton || !optionsContainer) return;

  const normalize = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('vi')
    .replace(/đ/g, 'd');

  const updateFilter = () => {
    const keyword = normalize(search.value.trim());
    const city = cityFilter.value;
    let visible = 0;
    let checked = 0;

    options.forEach((option) => {
      const checkbox = option.querySelector('input[type="checkbox"]');
      if (checkbox.checked) checked++;

      const matchesKeyword = !keyword
        || normalize(option.dataset.locationName).includes(keyword)
        || normalize(option.dataset.locationCity).includes(keyword);
      const matchesCity = !city || option.dataset.locationCity === city;
      const shouldShow = matchesKeyword && matchesCity && (!selectedOnly || checkbox.checked);

      option.classList.toggle('d-none', !shouldShow);
      if (shouldShow) visible++;
    });

    selectedCount.textContent = String(checked);
    visibleCount.textContent = String(visible);
    emptyMessage.classList.toggle('d-none', visible !== 0);
    selectedButton.classList.toggle('btn-primary', selectedOnly);
    selectedButton.classList.toggle('btn-outline-primary', !selectedOnly);
    selectedButton.setAttribute('aria-pressed', String(selectedOnly));
  };

  search.addEventListener('input', updateFilter);
  cityFilter.addEventListener('change', updateFilter);
  optionsContainer.addEventListener('change', (event) => {
    if (event.target.matches('input[name="locationIds[]"]')) updateFilter();
  });
  selectedButton.addEventListener('click', () => {
    selectedOnly = !selectedOnly;
    updateFilter();
  });
  resetButton.addEventListener('click', () => {
    search.value = '';
    cityFilter.value = '';
    selectedOnly = false;
    updateFilter();
    search.focus();
  });

  updateFilter();
});
</script>
<?php $this->stop() ?>
