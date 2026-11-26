<?php $this->layout('layouts/admin', ['title' => 'Sửa địa điểm', 'pageTitle' => 'Cập nhật điểm đến', 'activeMenu' => 'location']) ?>

<?php $this->start('page') ?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-lg-5">
                <a href="/admin/locations" class="text-decoration-none text-secondary small fw-semibold">
                    <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách địa điểm
                </a>

                <form action="/admin/locations/update/<?= (int)$location->locationId ?>" method="POST" enctype="multipart/form-data" class="mt-4">
                    <?= csrf_field() ?>
                    <div class="row g-4">
                        <div class="col-md-7">
                            <label for="locationName" class="form-label fw-semibold">Tên địa điểm <span class="text-danger">*</span></label>
                            <input type="text" id="locationName" name="locationName"
                                   class="form-control <?= isset($errors['locationName']) ? 'is-invalid' : '' ?>"
                                   value="<?= $this->e($old['locationName'] ?? $location->locationName) ?>" maxlength="150" required>
                            <?php if (isset($errors['locationName'])) : ?><div class="invalid-feedback"><?= $this->e($errors['locationName']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <label for="city" class="form-label fw-semibold">Tỉnh / Thành phố <span class="text-danger">*</span></label>
                            <input type="text" id="city" name="city"
                                   class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>"
                                   value="<?= $this->e($old['city'] ?? $location->city) ?>" maxlength="100" required>
                            <?php if (isset($errors['city'])) : ?><div class="invalid-feedback"><?= $this->e($errors['city']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label for="images" class="form-label fw-semibold">Tải thêm ảnh</label>
                            <input class="form-control" type="file" id="images" name="images[]"
                                   accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                            <div class="form-text">Ảnh mới được thêm vào album hiện tại, không làm mất ảnh cũ.</div>
                            <div id="imagePreview" class="row g-3 mt-1"></div>
                        </div>
                    </div>

                    <div class="border-top mt-5 pt-4 d-flex justify-content-end gap-2">
                        <a href="/admin/locations" class="btn btn-light px-4 rounded-pill fw-semibold">Hủy</a>
                        <button type="submit" class="btn btn-dark px-4 rounded-pill fw-bold">
                            <i class="bi bi-save2 me-1"></i>Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mt-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Album ảnh</h2>
                        <p class="text-muted small mb-0"><?= count($location->images) ?> ảnh đang được lưu.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <?php foreach ($location->images as $image) : ?>
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="card h-100 border rounded-3 overflow-hidden">
                                <img src="<?= $this->e($image['imageUrl']) ?>" alt="Ảnh <?= $this->e($location->locationName) ?>"
                                     class="card-img-top object-fit-cover" style="height:160px" loading="lazy">
                                <div class="card-body p-2">
                                    <form action="/admin/locations/<?= (int)$location->locationId ?>/images/delete/<?= (int)$image['imageId'] ?>" method="POST"
                                          onsubmit="return confirm('Xóa ảnh này khỏi album?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger btn-sm w-100" type="submit">
                                            <i class="bi bi-trash3 me-1"></i>Xóa ảnh
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$location->images) : ?>
                        <div class="col-12"><div class="alert alert-light border mb-0">Địa điểm chưa có ảnh. Hãy dùng ô tải ảnh ở trên.</div></div>
                    <?php endif; ?>
                </div>
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
