<?php $this->layout('layouts/admin', ['title' => 'Địa điểm', 'pageTitle' => 'Quản lý địa điểm', 'activeMenu' => 'location']) ?>

<?php $this->start('page') ?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h2 class="h5 fw-bold mb-1"><i class="bi bi-geo-alt-fill me-2 text-warning"></i>Danh sách điểm đến</h2>
                <p class="text-muted small mb-0"><?= count($locations) ?> địa điểm trong hệ thống.</p>
            </div>
            <a href="/admin/locations/create" class="btn btn-warning fw-bold rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i>Thêm địa điểm
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light small text-uppercase text-secondary">
                    <tr><th>Ảnh</th><th>Địa điểm</th><th>Tỉnh / Thành phố</th><th>Số ảnh</th><th>Ngày tạo</th><th class="text-end">Thao tác</th></tr>
                </thead>
                <tbody>
                <?php foreach ($locations as $location) : ?>
                    <tr>
                        <td style="width:92px">
                            <?php if (!empty($location['firstImage'])) : ?>
                                <img src="<?= $this->e($location['firstImage']) ?>" class="rounded-3 object-fit-cover border" style="width:72px;height:54px" alt="" loading="lazy">
                            <?php else : ?>
                                <div class="rounded-3 bg-light border d-grid place-items-center text-muted" style="width:72px;height:54px"><i class="bi bi-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="fw-semibold"><?= $this->e($location['locationName']) ?></span><div class="small text-muted">#<?= (int)$location['locationId'] ?></div></td>
                        <td><?= $this->e($location['city']) ?></td>
                        <td><span class="badge text-bg-light border"><?= (int)($location['imageCount'] ?? 0) ?> ảnh</span></td>
                        <td class="text-muted small"><?= !empty($location['createdAt']) ? date('d/m/Y', strtotime($location['createdAt'])) : '-' ?></td>
                        <td class="text-end">
                            <a href="/admin/locations/edit/<?= (int)$location['locationId'] ?>" class="btn btn-outline-dark btn-sm rounded-pill" title="Sửa"><i class="bi bi-pencil-square"></i></a>
                            <form action="/admin/locations/delete/<?= (int)$location['locationId'] ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn xóa địa điểm này?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill" title="Xóa"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$locations) : ?><tr><td colspan="6" class="text-center text-muted py-5">Chưa có địa điểm nào.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $this->stop() ?>
