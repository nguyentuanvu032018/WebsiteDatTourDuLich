<?php $this->layout('layouts/admin', ['title' => 'Tổng quan', 'pageTitle' => 'Bảng điều khiển', 'activeMenu' => 'dashboard']) ?>
<?php
$presence = $presence ?? [
    'totalOnline' => 0,
    'loggedInOnline' => 0,
    'guestOnline' => 0,
    'recent' => [],
    'onlineWindowSeconds' => 60,
];
?>
<?php $this->start('page') ?>

<!-- Giữ nguyên bốn thẻ thống kê hiện tại -->
<div class="row g-4 mb-4">
    <?php
    $stats = [
        ['Doanh thu', number_format((float)$totalRevenue, 0, ',', '.') . '₫', 'bi-cash-stack', 'success'],
        ['Đơn đặt', (int)$totalBookings, 'bi-receipt', 'primary'],
        ['Khách hàng', (int)$totalCustomers, 'bi-people', 'warning'],
        ['Tour', (int)$totalTours, 'bi-compass', 'danger'],
    ];
    foreach ($stats as [$label, $value, $icon, $color]) :
    ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4 d-flex align-items-center gap-3">
                    <span class="stat-icon bg-<?= $color ?>-subtle text-<?= $color ?>"><i class="bi <?= $icon ?>"></i></span>
                    <div><small class="text-secondary"><?= $label ?></small><div class="h4 fw-bold mb-0"><?= $value ?></div></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Khối online có thể thu gọn, không thay đổi các khu vực Dashboard cũ -->
<section class="card border-0 shadow-sm rounded-4 mb-4" aria-labelledby="presenceHeading">
    <div class="card-header bg-white border-0 p-3 p-lg-4 d-flex flex-wrap align-items-center gap-2">
        <h2 id="presenceHeading" class="h5 fw-bold mb-0 me-2">
            <i class="bi bi-broadcast-pin text-success me-2"></i>Người dùng đang hoạt động
        </h2>
        <span id="presenceConnectionBadge" class="badge text-bg-secondary-subtle text-secondary border rounded-pill">
            <span class="presence-dot me-1"></span>Đang kết nối SSE
        </span>
        <small class="text-secondary">Online = hoạt động trong <?= (int)$presence['onlineWindowSeconds'] ?> giây gần nhất</small>
        <button class="btn btn-sm btn-outline-secondary ms-auto" type="button"
                data-bs-toggle="collapse" data-bs-target="#presencePanel"
                aria-expanded="true" aria-controls="presencePanel">
            <i class="bi bi-chevron-up me-1"></i>Thu gọn
        </button>
    </div>
    <div id="presencePanel" class="collapse show">
        <div class="card-body border-top p-3 p-lg-4">
            <div class="row g-3 align-items-stretch">
                <div class="col-sm-4 col-lg-2">
                    <div class="bg-success-subtle rounded-4 p-3 h-100 text-center">
                        <div class="text-secondary small">Tất cả</div>
                        <div id="presenceTotal" class="h2 fw-bold text-success mb-0"><?= (int)$presence['totalOnline'] ?></div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="bg-primary-subtle rounded-4 p-3 h-100 text-center">
                        <div class="text-secondary small">Đã đăng nhập</div>
                        <div id="presenceLoggedIn" class="h2 fw-bold text-primary mb-0"><?= (int)$presence['loggedInOnline'] ?></div>
                    </div>
                </div>
                <div class="col-sm-4 col-lg-2">
                    <div class="bg-warning-subtle rounded-4 p-3 h-100 text-center">
                        <div class="text-secondary small">Khách</div>
                        <div id="presenceGuests" class="h2 fw-bold text-warning-emphasis mb-0"><?= (int)$presence['guestOnline'] ?></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="border rounded-4 h-100 p-3">
                        <h3 class="h6 fw-bold">Hoạt động gần nhất</h3>
                        <div id="presenceRecent" class="d-grid gap-2">
                            <?php foreach ($presence['recent'] as $item) : ?>
                                <div class="d-flex align-items-center gap-2 small">
                                    <span class="presence-dot flex-shrink-0"></span>
                                    <span class="fw-semibold text-truncate"><?= $this->e($item['displayName']) ?></span>
                                    <span class="text-secondary text-truncate"><?= $this->e($item['currentPath']) ?></span>
                                    <span class="text-secondary ms-auto text-nowrap"><?= (int)$item['secondsAgo'] ?> giây trước</span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (!$presence['recent']) : ?>
                                <div class="text-secondary small">Chưa ghi nhận hoạt động trực tuyến.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Giữ nguyên bố cục Dashboard cũ -->
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4">
                <div class="d-flex justify-content-between">
                    <h2 class="h5 fw-bold mb-0">Đơn đặt gần đây</h2>
                    <a href="/admin/bookings">Xem tất cả</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Mã</th><th>Khách hàng</th><th>Tour</th><th>Thanh toán</th><th>Tổng</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking) : ?>
                            <tr>
                                <td><a href="/admin/bookings/show/<?= (int)$booking['bookingId'] ?>">#<?= (int)$booking['bookingId'] ?></a></td>
                                <td><?= $this->e($booking['customerName']) ?></td>
                                <td><?= $this->e($booking['tourTitle']) ?></td>
                                <td><?= $this->e(payment_status_label($booking['paymentStatus'] ?? 'UNPAID')) ?></td>
                                <td><?= number_format((float)$booking['totalPrice'], 0, ',', '.') ?>₫</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$recentBookings) : ?><tr><td colspan="5" class="text-center text-secondary py-5">Chưa có đơn đặt.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 p-4"><h2 class="h5 fw-bold mb-0">Tour được đặt nhiều</h2></div>
            <div class="list-group list-group-flush">
                <?php foreach ($topTours as $tour) : ?>
                    <a href="/admin/tours/edit/<?= (int)$tour['tourId'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-truncate me-2"><?= $this->e($tour['title']) ?></span>
                        <span class="badge bg-warning text-dark rounded-pill"><?= (int)$tour['totalBooked'] ?></span>
                    </a>
                <?php endforeach; ?>
                <?php if (!$topTours) : ?><div class="p-4 text-secondary">Chưa có dữ liệu.</div><?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 p-4"><h2 class="h5 fw-bold mb-0">Doanh thu 6 tháng</h2></div>
            <div class="card-body p-4">
                <div class="d-grid gap-3">
                    <?php
                    $max = max(array_map(static fn ($month) => (float)($month['monthlyTotal'] ?? 0), $monthlyRevenue) ?: [1]);
                    foreach ($monthlyRevenue as $month) :
                        $value = (float)($month['monthlyTotal'] ?? 0);
                    ?>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= $this->e($month['month']) ?></span>
                                <strong><?= number_format($value, 0, ',', '.') ?>₫</strong>
                            </div>
                            <div class="progress" role="progressbar" aria-label="Doanh thu tháng">
                                <div class="progress-bar bg-warning" style="width:<?= $max > 0 ? round($value / $max * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
