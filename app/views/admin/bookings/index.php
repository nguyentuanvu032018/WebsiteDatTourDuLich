<?php $this->layout('layouts/admin', ['title' => 'Đơn đặt tour', 'pageTitle' => 'Quản lý đơn đặt tour', 'activeMenu' => 'booking']) ?>
<?php $this->start('page') ?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>Tour</th>
                    <th>Ngày khởi hành</th>
                    <th>Trạng thái</th>
                    <th>Thanh toán</th>
                    <th>Tổng tiền</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody><?php foreach ($bookings as $booking): ?><tr>
                        <td>#<?= (int)$booking['bookingId'] ?></td>
                        <td><strong><?= $this->e($booking['customerName']) ?></strong><small class="d-block text-secondary"><?= $this->e($booking['customerEmail']) ?></small></td>
                        <td><?= $this->e($booking['tourTitle']) ?></td>
                        <td><?= !empty($booking['startDate']) ? date('d/m/Y', strtotime($booking['startDate'])) : '—' ?></td>
                        <td><span class="badge text-bg-light"><?= $this->e(booking_status_label($booking['status'])) ?></span></td>
                        <td><?= $this->e(payment_status_label($booking['paymentStatus'] ?? 'UNPAID')) ?></td>
                        <td class="fw-semibold"><?= number_format((float)$booking['totalPrice'], 0, ',', '.') ?>₫</td>
                        <td>
                            <div class="d-flex justify-content-end gap-3">
                                <!-- Xem chi tiết -->
                                <a
                                    href="/admin/bookings/show/<?= (int) $booking['bookingId'] ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <!-- Xác nhận đơn: chỉ hiện khi đang PENDING -->
                                <?php if ($booking['status'] === 'PENDING'): ?>
                                    <form
                                        action="/admin/bookings/confirm/<?= (int) $booking['bookingId'] ?>"
                                        method="POST"
                                        onsubmit="return confirm('Xác nhận đơn đặt tour này?');">
                                        <?= csrf_field() ?>

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-success"
                                            title="Xác nhận đơn">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <!-- Hủy đơn: hiện khi PENDING hoặc CONFIRMED -->
                                <?php if ($booking['status'] === 'PENDING'): ?>
                                    <form
                                        action="/admin/bookings/cancel/<?= (int) $booking['bookingId'] ?>"
                                        method="POST"
                                        onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?');">
                                        <?= csrf_field() ?>

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hủy đơn">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr><?php endforeach; ?><?php if (!$bookings): ?><tr>
                        <td colspan="8" class="text-center text-secondary py-5">Chưa có đơn đặt tour.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php $this->stop() ?>
