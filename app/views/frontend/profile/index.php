<?php $this->layout('layouts/defaultCustomer', ['title' => 'Hồ sơ cá nhân - VivuTour']) ?>
<?php $this->start('page') ?>
<section class="page-hero py-4">
    <div class="container text-white py-3">
        <h1 class="h2 fw-bold">Hồ sơ cá nhân</h1>
        <p class="mb-0">Quản lý thông tin tài khoản và mật khẩu.</p>
    </div>
</section>
<div class="container py-5">
    <div class="row g-4">
        <aside class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-4">
                <div class="profile-avatar mx-auto mb-3"><?= $this->e(mb_strtoupper(mb_substr($customer->name, 0, 1))) ?></div>
                <h2 class="h4 fw-bold mb-1"><?= $this->e($customer->name) ?></h2>
                <p class="text-secondary mb-3"><?= $this->e($customer->email) ?></p>
                <div class="border-top pt-3 text-start small">
                    <div class="mb-2"><i class="bi bi-telephone text-warning me-2"></i><?= $this->e($customer->sDT ?: 'Chưa cập nhật') ?></div>
                    <div><i class="bi bi-geo-alt text-warning me-2"></i><?= $this->e($customer->address ?: 'Chưa cập nhật') ?></div>
                </div>
            </div><a href="/bookings/history" class="btn btn-outline-dark w-100 mt-3"><i class="bi bi-clock-history me-2"></i>Lịch sử đặt tour</a>
        </aside>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 mb-4">
                <h2 class="h4 fw-bold mb-4">Thông tin tài khoản</h2><?php if (isset($errors['profile'])) : ?><div class="alert alert-danger"><?= $this->e($errors['profile']) ?></div><?php endif; ?><form action="/profile/update" method="POST"><?= csrf_field() ?><div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="name">Họ tên</label><input id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= $this->e($old['name'] ?? $customer->name) ?>" required><?php if (isset($errors['name'])) : ?><div class="invalid-feedback"><?= $this->e($errors['name']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= $this->e($old['email'] ?? $customer->email) ?>" required><?php if (isset($errors['email'])) : ?><div class="invalid-feedback"><?= $this->e($errors['email']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="phoneNumber">Số điện thoại</label><input id="phoneNumber" name="phoneNumber" class="form-control <?= isset($errors['sDT']) ? 'is-invalid' : '' ?>" value="<?= $this->e($old['phoneNumber'] ?? $customer->sDT) ?>"><?php if (isset($errors['sDT'])) : ?><div class="invalid-feedback"><?= $this->e($errors['sDT']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="address">Địa chỉ</label><input id="address" name="address" class="form-control" value="<?= $this->e($old['address'] ?? $customer->address) ?>"></div>
                        <div class="col-12"><button class="btn btn-warning fw-bold px-4">Lưu thay đổi</button></div>
                    </div>
                </form>
            </div>
            <div class="card border-0 shadow-sm rounded-4 p-4 p-lg-5">
                <h2 class="h4 fw-bold mb-4">Đổi mật khẩu</h2>
                <form action="/profile/password" method="POST"><?= csrf_field() ?><div class="row g-3">
                        <div class="col-12"><label class="form-label" for="current_password">Mật khẩu hiện tại</label><input id="current_password" type="password" name="current_password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required><?php if (isset($errors['current_password'])) : ?><div class="invalid-feedback"><?= $this->e($errors['current_password']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="new_password">Mật khẩu mới</label><input id="new_password" type="password" name="new_password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" required><?php if (isset($errors['new_password'])) : ?><div class="invalid-feedback"><?= $this->e($errors['new_password']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" required><?php if (isset($errors['password_confirmation'])) : ?><div class="invalid-feedback"><?= $this->e($errors['password_confirmation']) ?></div><?php endif; ?></div>
                        <div class="col-12"><button class="btn btn-outline-dark px-4">Đổi mật khẩu</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php if ($bookings) : ?><section class="mt-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold mb-0">Đơn gần đây</h2><a href="/bookings/history">Xem tất cả</a>
            </div>
            <div class="table-responsive bg-white rounded-4 shadow-sm">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Mã</th>
                            <th>Tour</th>
                            <th>Trạng thái</th>
                            <th>Tổng tiền</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach (array_slice($bookings, 0, 5) as $booking) : ?><tr>
                                <td>#<?= (int)$booking['bookingId'] ?></td>
                                <td><?= $this->e($booking['tourTitle']) ?></td>
                                <td><span class="badge text-bg-light"><?= $this->e($booking['status']) ?></span></td>
                                <td><?= number_format((float)$booking['totalPrice'], 0, ',', '.') ?>₫</td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </section><?php endif; ?>
</div>
<?php $this->stop() ?>