<?php $this->layout('layouts/admin', ['title' => 'Thêm địa điểm', 'pageTitle' => 'Tạo điểm đến mới', 'activeMenu' => 'location']) ?>

<?php $this->start('page') ?>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">
                <a href="/admin/locations" class="text-decoration-none text-secondary small fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách địa điểm
                </a>

                <div class="d-flex align-items-center gap-3 my-4">
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-grid place-items-center" style="width:52px;height:52px">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                    </div>
                    <div>
                        <h2 class="h5 fw-bold mb-1">Thông tin điểm đến</h2>
                        <p class="text-muted small mb-0">Bạn có thể tải nhiều ảnh JPG, PNG, WEBP hoặc GIF, tối đa 5 MB mỗi ảnh.</p>
                    </div>
                </div>

                <?php if (!empty($errors['location'])) : ?>
                    <div class="alert alert-danger"><?= $this->e($errors['location']) ?></div>
                <?php endif; ?>

                <form action="/admin/locations/store" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label for="locationName" class="form-label fw-semibold">Tên địa điểm <span class="text-danger">*</span></label>
                            <input type="text" id="locationName" name="locationName"
                                   class="form-control <?= isset($errors['locationName']) ? 'is-invalid' : '' ?>"
                                   value="<?= $this->e($old['locationName'] ?? '') ?>"
                                   maxlength="150" required autofocus>
                            <?php if (isset($errors['locationName'])) : ?><div class="invalid-feedback"><?= $this->e($errors['locationName']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <label for="city" class="form-label fw-semibold">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <input type="text" id="city" name="city"
                                   class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                                   value="<?= $this->e($old['city'] ?? '') ?>"
                                   maxlength="100" required>
                            <?php if (isset($errors['city'])) : ?><div class="invalid-feedback"><?= $this->e($errors['city']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="images" class="form-label fw-semibold">Album ảnh địa điểm</label>
                            <input class="form-control" type="file" id="images" name="images[]"
                                   accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                            <div class="form-text">Tên tập tin được đổi ngẫu nhiên; máy chủ kiểm tra MIME thực tế để ngăn tải tập tin giả mạo.</div>
                            <div id="imagePreview" class="row g-3 mt-1"></div>
                        </div>
                    </div>

                    <div class="border-top mt-5 pt-4 d-flex justify-content-end gap-2">
                        <a href="/admin/locations" class="btn btn-light px-4 rounded-pill fw-semibold">Hủy</a>
                        <button type="submit" class="btn btn-warning px-4 rounded-pill fw-bold">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i>Tạo địa điểm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>

<?php $this->start('page_specific_js') ?>
<script>
document.getElementById('images')?.addEventListener('change', function () {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    Array.from(this.files).slice(0, 12).forEach(file => {
        const col = document.createElement('div');
        col.className = 'col-6 col-md-4 col-lg-3';
        const img = document.createElement('img');
        img.className = 'img-fluid rounded-3 border w-100 object-fit-cover';
        img.style.height = '130px';
        img.alt = file.name;
        img.src = URL.createObjectURL(file);
        img.onload = () => URL.revokeObjectURL(img.src);
        col.appendChild(img);
        preview.appendChild(col);
    });
});
</script>
<?php $this->stop() ?>
