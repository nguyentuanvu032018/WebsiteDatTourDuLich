<?php $this->layout('layouts/defaultCustomer', ['title' => 'Đặt tour thành công - VivuTour']) ?>
<?php $this->start('page') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow rounded-4 text-center p-4 p-lg-5">
                <div class="success-icon mx-auto mb-4"><i class="bi bi-check-lg"></i></div>
                <h1 class="h2 fw-bold">Đã ghi nhận đơn đặt tour</h1>
                <p class="text-secondary">Mã đơn <strong>#<?= (int)$booking->bookingId ?></strong>. Bạn có thể theo dõi trạng thái trong lịch sử đặt tour.</p>
                <div class="bg-light rounded-4 p-4 text-start my-4">
                    <div class="d-flex justify-content-between mb-2"><span>Tour</span><strong class="text-end"><?= $this->e($tour['title'] ?? '') ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Tổng tiền</span><strong class="text-danger"><?= number_format($booking->totalPrice, 0, ',', '.') ?>₫</strong></div>
                    <div class="d-flex justify-content-between"><span>Thanh toán</span><strong><?= $this->e(payment_status_label($payment?->status ?? 'UNPAID')) ?></strong></div>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-2"><a href="/bookings/history" class="btn btn-warning fw-bold">Xem lịch sử đặt tour</a><a href="/tours" class="btn btn-outline-dark">Tiếp tục khám phá</a></div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
