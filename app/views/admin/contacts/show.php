<?php $this->layout('layouts/admin', ['title' => 'Chi tiết liên hệ', 'pageTitle' => 'Chi tiết yêu cầu', 'activeMenu' => 'contact']) ?>
<?php $this->start('page') ?>
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="d-flex justify-content-between mb-4">
            <a href="/admin/contacts" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Quay lại
            </a>
            <span class="badge text-bg-<?= $contact['status'] === 'RESOLVED' ? 'success' : 'warning' ?> align-self-center">
                <?= $this->e(contact_status_label($contact['status'])) ?>
            </span>
        </div>

        <article class="card border-0 shadow-sm rounded-4 p-4 p-lg-5 mb-4">
            <h2 class="h4 fw-bold"><?= $this->e($contact['fullName']) ?></h2>
            <div class="row g-3 text-secondary mb-4">
                <div class="col-md-6"><i class="bi bi-envelope me-2"></i><?= $this->e($contact['email'] ?: '—') ?></div>
                <div class="col-md-6"><i class="bi bi-telephone me-2"></i><?= $this->e($contact['phoneNumber'] ?: '—') ?></div>
                <div class="col-md-6"><i class="bi bi-chat-dots me-2"></i>Ưu tiên: <?= $this->e($contact['contactMethod']) ?></div>
                <div class="col-md-6"><i class="bi bi-clock me-2"></i><?= date('d/m/Y H:i', strtotime($contact['createdAt'])) ?></div>
            </div>
            <h3 class="h6 fw-bold">Nội dung yêu cầu</h3>
            <div class="bg-light rounded-4 p-4 preserve-lines"><?= nl2br($this->e($contact['message'])) ?></div>
        </article>

        <?php if ($replies) : ?>
            <section class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h5 fw-bold mb-4">Lịch sử trao đổi</h2>
                <div class="d-grid gap-3">
                    <?php foreach ($replies as $reply) : ?>
                        <?php $isAdmin = $reply['senderRole'] === 'admin'; ?>
                        <div class="d-flex <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?>">
                            <div class="rounded-4 p-3 <?= $isAdmin ? 'bg-warning bg-opacity-25' : 'bg-light border' ?>" style="max-width:85%">
                                <div class="small fw-bold mb-1">
                                    <?= $isAdmin ? 'Admin · VivuTour' : $this->e($reply['senderName'] ?: $contact['fullName']) ?>
                                </div>
                                <div class="preserve-lines"><?= nl2br($this->e($reply['message'])) ?></div>
                                <div class="small text-secondary mt-2"><?= date('d/m/Y H:i', strtotime($reply['createdAt'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card border-0 shadow-sm rounded-4 p-4 p-lg-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-send-fill text-warning fs-4"></i>
                <h2 class="h5 fw-bold mb-0">Phản hồi trực tiếp trên website</h2>
            </div>

            <?php if (!empty($contact['customerUserId'])) : ?>
                <form action="/admin/contacts/reply/<?= (int)$contact['contactId'] ?>" method="POST">
                    <?= csrf_field() ?>
                    <label for="replyMessage" class="form-label fw-semibold">Nội dung phản hồi</label>
                    <textarea id="replyMessage" name="message" rows="6" maxlength="3000"
                              class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                              placeholder="Nhập nội dung tư vấn cho khách hàng..." required></textarea>
                    <?php if (isset($errors['message'])) : ?>
                        <div class="invalid-feedback"><?= $this->e($errors['message']) ?></div>
                    <?php endif; ?>
                    <div class="form-text mb-3">
                        Khách hàng sẽ nhận thông báo và xem phản hồi trong tài khoản VivuTour.
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="markResolved" value="1" id="markResolved">
                        <label class="form-check-label" for="markResolved">Đánh dấu đã xử lý sau khi gửi</label>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-warning fw-bold px-4" type="submit">
                            <i class="bi bi-send me-1"></i>Gửi phản hồi
                        </button>
                        <?php if (!empty($contact['email'])) : ?>
                            <a class="btn btn-outline-secondary" href="mailto:<?= $this->e($contact['email']) ?>">
                                <i class="bi bi-envelope me-1"></i>Gửi qua email
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else : ?>
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-info-circle me-2"></i>Khách gửi yêu cầu khi chưa đăng nhập nên chưa thể nhận phản hồi trực tiếp trên website.
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (!empty($contact['email'])) : ?>
                        <a class="btn btn-outline-primary" href="mailto:<?= $this->e($contact['email']) ?>">
                            <i class="bi bi-envelope me-1"></i>Phản hồi qua email
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($contact['phoneNumber'])) : ?>
                        <a class="btn btn-outline-dark" href="tel:<?= $this->e($contact['phoneNumber']) ?>">
                            <i class="bi bi-telephone me-1"></i>Gọi điện
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($contact['status'] !== 'RESOLVED') : ?>
                <form action="/admin/contacts/resolve/<?= (int)$contact['contactId'] ?>" method="POST" class="mt-3">
                    <?= csrf_field() ?>
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check-circle me-1"></i>Đánh dấu đã xử lý
                    </button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php $this->stop() ?>
