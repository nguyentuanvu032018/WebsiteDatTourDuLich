<?php $this->layout('layouts/admin', ['title' => 'Chi tiết đơn', 'pageTitle' => 'Chi tiết đơn đặt tour', 'activeMenu' => 'booking']) ?>
<?php $this->start('page') ?>
<div class="d-flex justify-content-between mb-4"><a href="/admin/bookings" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Quay lại</a><span class="badge text-bg-light align-self-center fs-6"><?= $this->e(booking_status_label($booking['status'])) ?></span></div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5">
            <h2 class="h4 fw-bold mb-4">Đơn #<?= (int)$booking['bookingId'] ?></h2>
            <dl class="row mb-0">
                <dt class="col-sm-4">Tour</dt>
                <dd class="col-sm-8"><?= $this->e($booking['tourTitle']) ?></dd>
                <dt class="col-sm-4">Khởi hành</dt>
                <dd class="col-sm-8"><?= !empty($booking['startDate']) ? date('d/m/Y', strtotime($booking['startDate'])) : '—' ?></dd>
                <dt class="col-sm-4">Kết thúc</dt>
                <dd class="col-sm-8"><?= !empty($booking['endDate']) ? date('d/m/Y', strtotime($booking['endDate'])) : '—' ?></dd>
                <dt class="col-sm-4">Mã ưu đãi</dt>
                <dd class="col-sm-8"><?= $this->e($booking['couponCode'] ?: '—') ?></dd>
                <dt class="col-sm-4">Tổng tiền</dt>
                <dd class="col-sm-8 fw-bold text-danger fs-5"><?= number_format((float)$booking['totalPrice'], 0, ',', '.') ?>₫</dd>
                <dt class="col-sm-4">Ngày tạo</dt>
                <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($booking['createdAt'])) ?></dd>
            </dl>
            <hr>
            <h3 class="h5 fw-bold">Hành khách</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Điện thoại</th>
                            <th>Quan hệ</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($booking['passengers'] as $passenger): ?><tr>
                                <td><?= $this->e($passenger['fullName']) ?></td>
                                <td><?= $this->e($passenger['phoneNumber'] ?: '—') ?></td>
                                <td><?= $this->e($passenger['relationship'] ?: '—') ?></td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h2 class="h5 fw-bold">Khách hàng</h2>
            <p class="fw-semibold mb-1"><?= $this->e($booking['customerName']) ?></p>
            <p class="text-secondary mb-1"><?= $this->e($booking['customerEmail']) ?></p>
            <p class="text-secondary mb-1"><?= $this->e($booking['customerPhone'] ?: '—') ?></p>
            <p class="text-secondary mb-0"><?= $this->e($booking['address'] ?: '—') ?></p>
        </div>
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold">Thanh toán</h2>
            <dl class="row mb-0">
                <dt class="col-5">Trạng thái</dt>
                <dd class="col-7"><?= $this->e(payment_status_label($booking['paymentStatus'] ?? 'UNPAID')) ?></dd>
                <dt class="col-5">Phương thức</dt>
                <dd class="col-7"><?= $this->e($booking['paymentMethod'] ?? '—') ?></dd>
                <dt class="col-5">Mã giao dịch</dt>
                <dd class="col-7 text-break"><?= $this->e($booking['transactionId'] ?? '—') ?></dd>
            </dl>
        </div>
    </div>
</div>
<?php $this->stop() ?>
