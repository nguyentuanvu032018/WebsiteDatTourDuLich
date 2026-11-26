<?php $this->layout('layouts/defaultCustomer', ['title' => 'Lịch sử đặt tour - VivuTour']) ?>
<?php $this->start('page') ?>
<section class="page-hero py-4">
    <div class="container text-white py-3">
        <h1 class="h2 fw-bold">Lịch sử đặt tour</h1>
        <p class="mb-0">Theo dõi đơn, thanh toán hoặc hủy đơn chưa hoàn tất.</p>
    </div>
</section>
<div class="container py-5"><?php if (!$bookings) : ?><div class="text-center bg-white border rounded-4 py-5"><i class="bi bi-suitcase-lg display-3 text-secondary"></i>
            <h2 class="h4 mt-3">Bạn chưa có đơn đặt tour</h2><a href="/tours" class="btn btn-warning fw-bold mt-2">Khám phá tour</a>
        </div><?php else : ?><div class="row g-4"><?php foreach ($bookings as $booking) : ?><?php $statusClass = ['PENDING' => 'warning', 'CONFIRMED' => 'success', 'COMPLETED' => 'primary', 'CANCELLED' => 'secondary'][$booking['status']] ?? 'secondary'; ?><div class="col-12">
                <article class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-lg-5"><small class="text-secondary">Đơn #<?= (int)$booking['bookingId'] ?> · <?= date('d/m/Y H:i', strtotime($booking['createdAt'])) ?></small>
                                <h2 class="h5 fw-bold mt-1 mb-1"><?= $this->e($booking['tourTitle']) ?></h2><?php if (!empty($booking['startDate'])) : ?><span class="small text-secondary"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($booking['startDate'])) ?></span><?php endif; ?>
                            </div>
                            <div class="col-sm-4 col-lg-2"><span class="badge text-bg-<?= $statusClass ?>"><?= $this->e(booking_status_label($booking['status'])) ?></span></div>
                            <div class="col-sm-4 col-lg-2"><strong class="text-danger"><?= number_format((float)$booking['totalPrice'], 0, ',', '.') ?>₫</strong><small class="d-block text-secondary"><?= (int)$booking['passengerCount'] ?> hành khách</small></div>
                            <div class="col-sm-4 col-lg-3">
                                <?php
                                                        $bookingStatus = strtoupper(
                                                            trim((string)($booking['status'] ?? ''))
                                                        );

                                                        $paymentStatus = strtoupper(
                                                            trim((string)($booking['paymentStatus'] ?? ''))
                                                        );

                                                        $paymentId = (int)($booking['paymentId'] ?? 0);
                                                        $hasPayment = $paymentId > 0;
                                ?>

                                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                                    <?php if ($bookingStatus === 'PENDING'): ?>

                                        <?php if (!$hasPayment): ?>
                                            <!-- Chưa tạo giao dịch -->
                                            <a
                                                href="/bookings/payment/<?= (int)$booking['bookingId'] ?>"
                                                class="btn btn-sm btn-warning">
                                                Thanh toán
                                            </a>

                                            <form
                                                action="/bookings/cancel/<?= (int)$booking['bookingId'] ?>"
                                                method="POST"
                                                onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn?');">
                                                <?= csrf_field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Hủy đơn
                                                </button>
                                            </form>

                                        <?php elseif ($paymentStatus === 'PENDING'): ?>
                                            <!-- Đã thanh toán/chuyển khoản và chờ admin xác nhận -->
                                            <span class="badge bg-warning text-dark px-3 py-2">
                                                <i class="bi bi-hourglass-split me-1"></i>
                                                Đang chờ xác nhận thanh toán
                                            </span>

                                        <?php elseif (in_array($paymentStatus, ['SUCCESS', 'COMPLETED'], true)): ?>
                                            <!-- Thanh toán điện tử thành công, chờ admin xác nhận booking -->
                                            <span class="badge bg-success px-3 py-2">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Đã thanh toán, chờ xác nhận đơn
                                            </span>

                                        <?php elseif ($paymentStatus === 'FAILED'): ?>
                                            <!-- Chỉ giao dịch thất bại mới được thử lại -->
                                            <a
                                                href="/bookings/payment/<?= (int)$booking['bookingId'] ?>"
                                                class="btn btn-sm btn-warning">
                                                Thanh toán lại
                                            </a>

                                            <form
                                                action="/bookings/cancel/<?= (int)$booking['bookingId'] ?>"
                                                method="POST"
                                                onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn?');">
                                                <?= csrf_field() ?>

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger">
                                                    Hủy đơn
                                                </button>
                                            </form>

                                        <?php elseif ($paymentStatus === 'REFUNDED'): ?>
                                            <span class="badge bg-secondary px-3 py-2">
                                                Đã hoàn tiền
                                            </span>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div><?php endforeach; ?></div><?php endif; ?></div>
<?php $this->stop() ?>
