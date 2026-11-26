<?php
$this->layout('layouts/admin', [
    'title' => 'Khuyến mãi',
    'pageTitle' => 'Quản lý khuyến mãi',
    'activeMenu' => 'promotion',
]);

$promotions = $promotions ?? [];
$today = strtotime(date('Y-m-d'));
$activeCount = count(array_filter(
    $promotions,
    static fn (array $promotion): bool => strtotime((string)$promotion['expiryDate']) >= $today
));
$expiredCount = count($promotions) - $activeCount;
?>

<?php $this->start('page') ?>
<section aria-labelledby="promotionPageHeading">
    <!-- Thống kê nhanh -->
    <div class="row g-3 g-xl-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-warning-subtle text-warning-emphasis fs-3 p-3">
                        <i class="bi bi-tags"></i>
                    </span>
                    <div>
                        <div class="text-secondary">Tổng số mã</div>
                        <div class="h2 fw-bold mb-0"><?= count($promotions) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-success-subtle text-success fs-3 p-3">
                        <i class="bi bi-check-circle"></i>
                    </span>
                    <div>
                        <div class="text-secondary">Còn hiệu lực</div>
                        <div class="h2 fw-bold text-success mb-0"><?= $activeCount ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center gap-3 p-4">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-4 bg-danger-subtle text-danger fs-3 p-3">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <div>
                        <div class="text-secondary">Đã hết hạn</div>
                        <div class="h2 fw-bold text-danger mb-0"><?= $expiredCount ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách mã -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                <div>
                    <h2 id="promotionPageHeading" class="h5 fw-bold text-dark mb-1">
                        <i class="bi bi-ticket-perforated-fill me-2 text-warning"></i>Danh sách mã khuyến mãi
                    </h2>
                    <p class="text-secondary small mb-0">Quản lý và theo dõi các mã giảm giá của hệ thống.</p>
                </div>
                <a href="/admin/promotions/create" class="btn btn-warning fw-bold rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i>Tạo mã mới
                </a>
            </div>

            <div class="row g-2 mb-4">
                <div class="col-md-7 col-lg-5">
                    <label for="promotionSearch" class="visually-hidden">Tìm mã coupon</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-secondary"></i></span>
                        <input id="promotionSearch" type="search" class="form-control"
                               placeholder="Tìm mã coupon..." autocomplete="off">
                    </div>
                </div>
                <div class="col-md-5 col-lg-3">
                    <label for="promotionStatus" class="visually-hidden">Lọc trạng thái</label>
                    <select id="promotionStatus" class="form-select">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="active">Còn hiệu lực</option>
                        <option value="expired">Hết hạn</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive border rounded-4">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small">
                        <tr>
                            <th class="ps-4 py-3">Mã coupon</th>
                            <th class="text-end py-3">Giá trị giảm</th>
                            <th class="text-end py-3">Đơn tối thiểu</th>
                            <th class="text-center py-3">Ngày hết hạn</th>
                            <th class="text-center py-3">Trạng thái</th>
                            <th class="text-center py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="promotionTableBody">
                        <?php foreach ($promotions as $promo) : ?>
                            <?php $isExpired = strtotime((string)$promo['expiryDate']) < $today; ?>
                            <tr class="promotion-row"
                                data-code="<?= $this->e(strtolower((string)$promo['couponCode'])) ?>"
                                data-status="<?= $isExpired ? 'expired' : 'active' ?>">
                                <td class="ps-4 py-3">
                                    <span class="font-monospace fw-bold text-uppercase bg-light border px-3 py-2 rounded-3">
                                        <?= $this->e($promo['couponCode']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success text-end">
                                    -<?= number_format((float)$promo['discountValue'], 0, ',', '.') ?>₫
                                </td>
                                <td class="text-secondary text-end">
                                    <?php if (!empty($promo['minOrderValue'])) : ?>
                                        <?= number_format((float)$promo['minOrderValue'], 0, ',', '.') ?>₫
                                    <?php else : ?>
                                        <span class="text-muted">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary text-center">
                                    <i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y', strtotime((string)$promo['expiryDate'])) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isExpired) : ?>
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-2">Hết hạn</span>
                                    <?php else : ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2">Còn hiệu lực</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="/admin/promotions/edit/<?= rawurlencode((string)$promo['couponCode']) ?>"
                                           class="btn btn-outline-dark btn-sm rounded-3" title="Chỉnh sửa" aria-label="Chỉnh sửa <?= $this->e($promo['couponCode']) ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <form action="/admin/promotions/delete/<?= rawurlencode((string)$promo['couponCode']) ?>"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này không?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-3"
                                                    title="Xóa" aria-label="Xóa <?= $this->e($promo['couponCode']) ?>">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="promotionEmptyRow" class="<?= $promotions ? 'd-none' : '' ?>">
                            <td colspan="6" class="text-center text-secondary py-5">
                                <i class="bi bi-tags display-6 d-block mb-2 text-warning"></i>
                                <span id="promotionEmptyText">
                                    <?= $promotions ? 'Không tìm thấy mã phù hợp.' : 'Hệ thống chưa có mã khuyến mãi nào.' ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mt-3">
                <small id="promotionResultInfo" class="text-secondary"></small>
                <nav aria-label="Phân trang mã khuyến mãi">
                    <ul id="promotionPagination" class="pagination pagination-sm mb-0"></ul>
                </nav>
            </div>
        </div>
    </div>
</section>
<?php $this->stop() ?>

<?php $this->start('page_specific_js') ?>
<script>
(() => {
    const rows = Array.from(document.querySelectorAll('.promotion-row'));
    const search = document.getElementById('promotionSearch');
    const status = document.getElementById('promotionStatus');
    const emptyRow = document.getElementById('promotionEmptyRow');
    const emptyText = document.getElementById('promotionEmptyText');
    const resultInfo = document.getElementById('promotionResultInfo');
    const pagination = document.getElementById('promotionPagination');
    const pageSize = 8;
    let currentPage = 1;

    const filteredRows = () => {
        const keyword = search.value.trim().toLocaleLowerCase('vi');
        return rows.filter(row => {
            const matchesCode = row.dataset.code.includes(keyword);
            const matchesStatus = status.value === 'all' || row.dataset.status === status.value;
            return matchesCode && matchesStatus;
        });
    };

    const render = () => {
        const matches = filteredRows();
        const pageCount = Math.max(1, Math.ceil(matches.length / pageSize));
        currentPage = Math.min(currentPage, pageCount);
        const start = (currentPage - 1) * pageSize;
        const visible = new Set(matches.slice(start, start + pageSize));

        rows.forEach(row => row.classList.toggle('d-none', !visible.has(row)));
        emptyRow.classList.toggle('d-none', matches.length !== 0);
        if (emptyText && matches.length === 0) {
            emptyText.textContent = rows.length ? 'Không tìm thấy mã phù hợp.' : 'Hệ thống chưa có mã khuyến mãi nào.';
        }

        resultInfo.textContent = matches.length
            ? `Hiển thị ${start + 1}–${Math.min(start + pageSize, matches.length)} trong ${matches.length} mã`
            : 'Không có mã khuyến mãi phù hợp';

        pagination.innerHTML = '';
        if (pageCount <= 1 || matches.length === 0) return;

        const addButton = (label, page, disabled = false, active = false, ariaLabel = '') => {
            const item = document.createElement('li');
            item.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'page-link';
            button.textContent = label;
            if (ariaLabel) button.setAttribute('aria-label', ariaLabel);
            button.addEventListener('click', () => {
                if (disabled) return;
                currentPage = page;
                render();
            });
            item.appendChild(button);
            pagination.appendChild(item);
        };

        addButton('‹', currentPage - 1, currentPage === 1, false, 'Trang trước');
        for (let page = 1; page <= pageCount; page += 1) {
            addButton(String(page), page, false, page === currentPage, `Trang ${page}`);
        }
        addButton('›', currentPage + 1, currentPage === pageCount, false, 'Trang sau');
    };

    search.addEventListener('input', () => {
        currentPage = 1;
        render();
    });
    status.addEventListener('change', () => {
        currentPage = 1;
        render();
    });
    render();
})();
</script>
<?php $this->stop() ?>
