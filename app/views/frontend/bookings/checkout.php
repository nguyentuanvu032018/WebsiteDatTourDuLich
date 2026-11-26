<?php $this->layout('layouts/defaultCustomer', ['title' => 'Đặt tour - VivuTour']) ?>
<?php $this->start('page') ?>
<section class="page-hero py-4">
    <div class="container text-white py-3">
        <h1 class="h2 fw-bold mb-1">Đặt tour</h1>
        <p class="mb-0">Kiểm tra lịch khởi hành và thông tin hành khách.</p>
    </div>
</section>
<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-8">
            <form action="/bookings/store" method="POST" class="card border-0 shadow-sm rounded-4 p-4 p-lg-5" id="bookingForm"><?= csrf_field() ?><input type="hidden" name="tourId" value="<?= (int)$tour['tourId'] ?>">
                <?php if (!empty($errors)) : ?><div class="alert alert-danger"><strong>Vui lòng kiểm tra:</strong>
                        <ul class="mb-0 mt-2"><?php foreach ($errors as $error) : ?><li><?= $this->e($error) ?></li><?php endforeach; ?></ul>
                    </div><?php endif; ?>
                <h2 class="h4 fw-bold mb-4">1. Chọn lịch khởi hành</h2>
                <div class="row g-3 mb-5"><?php foreach ($schedules as $schedule) : ?><div class="col-md-6"><label class="schedule-option d-block border rounded-4 p-3 h-100"><input class="form-check-input me-2" type="radio" name="scheduleId" value="<?= (int)$schedule['scheduleId'] ?>" <?= (int)($old['scheduleId'] ?? 0) === (int)$schedule['scheduleId'] ? 'checked' : '' ?> required><strong><?= date('d/m/Y', strtotime($schedule['startDate'])) ?> - <?= date('d/m/Y', strtotime($schedule['endDate'])) ?></strong><small class="d-block text-secondary ms-4">Còn <?= (int)($schedule['availableSlots'] ?? $schedule['maxParticipants']) ?> chỗ</small></label></div><?php endforeach; ?></div>
                <h2 class="h4 fw-bold mb-3">2. Hành khách</h2>
                <p class="text-secondary">Giá được tính theo số hành khách. Người đặt tour có thể là hành khách đầu tiên.</p>
                <div id="passengerList" class="d-grid gap-3"><?php $oldPassengers = $old['passengers'] ?? [['fullName' => $customer?->name ?? '', 'phoneNumber' => $customer?->sDT ?? '', 'relationship' => 'Người đặt']];
                                                                if (!$oldPassengers) $oldPassengers = [['fullName' => '', 'phoneNumber' => '', 'relationship' => '']];
                                                                foreach ($oldPassengers as $index => $passenger) : ?><div class="passenger-row border rounded-4 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3"><strong>Hành khách <span class="passenger-number"><?= $index + 1 ?></span></strong><button type="button" class="btn btn-sm btn-outline-danger remove-passenger"><i class="bi bi-trash"></i></button></div>
                            <div class="row g-3">
                                <div class="col-md-5"><label class="form-label">Họ tên *</label><input name="passengers[<?= $index ?>][fullName]" class="form-control passenger-name" value="<?= $this->e($passenger['fullName'] ?? '') ?>" required></div>
                                <div class="col-md-4"><label class="form-label">Số điện thoại</label><input name="passengers[<?= $index ?>][phoneNumber]" class="form-control passenger-phone" value="<?= $this->e($passenger['phoneNumber'] ?? '') ?>"></div>
                                <div class="col-md-3"><label class="form-label">Quan hệ</label><input name="passengers[<?= $index ?>][relationship]" class="form-control passenger-relation" value="<?= $this->e($passenger['relationship'] ?? '') ?>"></div>
                            </div>
                        </div><?php endforeach; ?></div><button type="button" id="addPassenger" class="btn btn-outline-dark mt-3"><i class="bi bi-person-plus me-1"></i>Thêm hành khách</button>
                <hr class="my-5">
                <h2 class="h4 fw-bold mb-3">3. Mã ưu đãi</h2>
                <div class="row g-3 align-items-end">
                    <div class="col-md-7"><label class="form-label" for="couponCode">Mã giảm giá</label><input id="couponCode" name="couponCode" class="form-control text-uppercase" value="<?= $this->e($old['couponCode'] ?? '') ?>" placeholder="Ví dụ: VIVU100"></div>
                    <div class="col-md-5">
                        <div class="form-text">Hệ thống kiểm tra và tính giảm giá khi tạo đơn.</div>
                    </div>
                </div><button type="submit" class="btn btn-warning btn-lg fw-bold mt-5 w-100">Tạo đơn và tiếp tục thanh toán <i class="bi bi-arrow-right ms-1"></i></button>
            </form>
        </div>
        <aside class="col-lg-4">
            <div class="card border-0 shadow rounded-4 sticky-lg-top booking-summary"><img src="<?= $this->e($tour['imageUrl'] ?: '/images/tour-placeholder.svg') ?>" class="card-img-top object-fit-cover" alt="<?= $this->e($tour['title']) ?>">
                <div class="card-body p-4"><span class="badge text-bg-warning-subtle text-warning-emphasis mb-2"><?= $this->e($tour['categoryName'] ?? 'Tour') ?></span>
                    <h2 class="h5 fw-bold"><?= $this->e($tour['title']) ?></h2>
                    <p class="small text-secondary"><i class="bi bi-geo-alt me-1"></i><?= $this->e($tour['locationNames'] ?? '') ?></p>
                    <div class="d-flex justify-content-between"><span>Giá/khách</span><strong class="text-danger"><?= number_format((float)$tour['basePrice'], 0, ',', '.') ?>₫</strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Số ngày</span><strong><?= (int)$tour['numberDays'] ?> ngày</strong></div>
                </div>
            </div>
        </aside>
    </div>
</div>
<template id="passengerTemplate">
    <div class="passenger-row border rounded-4 p-3">
        <div class="d-flex justify-content-between align-items-center mb-3"><strong>Hành khách <span class="passenger-number"></span></strong><button type="button" class="btn btn-sm btn-outline-danger remove-passenger"><i class="bi bi-trash"></i></button></div>
        <div class="row g-3">
            <div class="col-md-5"><label class="form-label">Họ tên *</label><input class="form-control passenger-name" required></div>
            <div class="col-md-4"><label class="form-label">Số điện thoại</label><input class="form-control passenger-phone"></div>
            <div class="col-md-3"><label class="form-label">Quan hệ</label><input class="form-control passenger-relation"></div>
        </div>
    </div>
</template>
<?php $this->stop() ?>
<?php $this->start('page_specific_js') ?><script>
    (() => {
        const list = document.getElementById('passengerList'),
            tpl = document.getElementById('passengerTemplate');

        function renumber() {
            [...list.querySelectorAll('.passenger-row')].forEach((row, i) => {
                row.querySelector('.passenger-number').textContent = i + 1;
                row.querySelector('.passenger-name').name = `passengers[${i}][fullName]`;
                row.querySelector('.passenger-phone').name = `passengers[${i}][phoneNumber]`;
                row.querySelector('.passenger-relation').name = `passengers[${i}][relationship]`;
                row.querySelector('.remove-passenger').disabled = list.children.length === 1;
            });
        }
        document.getElementById('addPassenger').addEventListener('click', () => {
            list.append(tpl.content.cloneNode(true));
            renumber();
        });
        list.addEventListener('click', e => {
            const btn = e.target.closest('.remove-passenger');
            if (btn && list.children.length > 1) {
                btn.closest('.passenger-row').remove();
                renumber();
            }
        });
        renumber();
    })();
</script><?php $this->stop() ?>