<?php $this->layout('layouts/defaultCustomer', ['title' => 'Thanh toán - VivuTour']) ?>
<?php $this->start('page') ?>
<?php
    $paymentStatus = strtoupper((string)($payment?->status ?? ''));
    $alreadyPaid   = in_array($paymentStatus, ['PENDING', 'SUCCESS', 'COMPLETED'], true);
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <div class="row g-4">
                <div class="col-lg-7">

                    <?php if ($alreadyPaid): ?>
                        <!-- Đã thanh toán → chặn form, hiện trạng thái + nút hủy -->
                        <div class="card border-0 shadow rounded-4 p-4 p-lg-5">
                            <div class="mb-3">
                                <h1 class="h3 fw-bold mb-1">Đơn #<?= (int)$booking->bookingId ?></h1>

                                <?php if ($paymentStatus === 'PENDING'): ?>
                                    <div class="alert alert-warning d-flex align-items-center gap-2 mt-3">
                                        <i class="bi bi-hourglass-split fs-4"></i>
                                        <div>
                                            <strong>Đang chờ admin xác nhận thanh toán</strong><br>
                                            <small>Bạn đã gửi thông tin thanh toán. Vui lòng chờ admin xử lý.</small>
                                        </div>
                                    </div>
                                <?php elseif (in_array($paymentStatus, ['SUCCESS', 'COMPLETED'], true)): ?>
                                    <div class="alert alert-success d-flex align-items-center gap-2 mt-3">
                                        <i class="bi bi-check-circle-fill fs-4"></i>
                                        <div>
                                            <strong>Thanh toán thành công</strong><br>
                                            <small>Đang chờ admin xác nhận đơn đặt tour.</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <hr>
                            <p class="text-secondary small mb-3">Nếu bạn muốn hủy đơn này, nhấn nút bên dưới. Lưu ý đơn đã thanh toán sẽ được hoàn tiền theo chính sách.</p>
                            <form action="/bookings/cancel/<?= (int)$booking->bookingId ?>" method="POST"
                                  onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-x-circle me-1"></i> Hủy đơn đặt tour
                                </button>
                            </form>
                            <a href="/bookings/history" class="btn btn-light w-100 mt-2">Quay lại lịch sử đặt tour</a>
                        </div>

                    <?php else: ?>
                        <!-- Chưa thanh toán → hiện form bình thường -->
                        <form action="/bookings/payment" method="POST" class="card border-0 shadow rounded-4 p-4 p-lg-5">
                            <?= csrf_field() ?>
                            <input type="hidden" name="bookingId" value="<?= (int)$booking->bookingId ?>">
                            <h1 class="h3 fw-bold mb-1">Chọn hình thức thanh toán</h1>
                            <p class="text-secondary mb-4">Mã đơn #<?= (int)$booking->bookingId ?></p>

                            <?php if (!empty($errors)) : ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($errors as $error) : ?>
                                        <div><?= $this->e($error) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php
                            $methods = [
                                'BANK_TRANSFER' => ['bi-bank',        'Chuyển khoản ngân hàng',    'Admin xác nhận sau khi nhận tiền.'],
                                'CASH'          => ['bi-cash-stack',  'Thanh toán tại văn phòng',  'Giữ chỗ ở trạng thái chờ thanh toán.'],
                                'MOMO'          => ['bi-phone',       'Ví điện tử MoMo',           'Mô phỏng giao dịch hoàn tất ngay.'],
                                'VNPAY'         => ['bi-credit-card', 'VNPay',                     'Mô phỏng giao dịch hoàn tất ngay.'],
                            ];
                            foreach ($methods as $code => [$icon, $label, $desc]) : ?>
                                <label class="payment-option d-flex gap-3 border rounded-4 p-3 mb-3">
                                    <input class="form-check-input mt-1" type="radio" name="paymentMethod"
                                           value="<?= $code ?>" <?= $code === 'BANK_TRANSFER' ? 'checked' : '' ?>>
                                    <i class="bi <?= $icon ?> fs-3 text-warning"></i>
                                    <span>
                                        <strong class="d-block"><?= $label ?></strong>
                                        <small class="text-secondary"><?= $desc ?></small>
                                    </span>
                                </label>
                            <?php endforeach; ?>

                            <button class="btn btn-warning btn-lg fw-bold w-100 mt-3" type="submit">
                                Xác nhận thanh toán
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold">Tóm tắt đơn hàng</h2>
                            <p class="fw-semibold mb-1"><?= $this->e($tour['title'] ?? '') ?></p>
                            <p class="small text-secondary"><?= $this->e($tour['locationNames'] ?? '') ?></p>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span>Tổng thanh toán</span>
                                <strong class="text-danger fs-4"><?= number_format($booking->totalPrice, 0, ',', '.') ?>₫</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->stop() ?>
