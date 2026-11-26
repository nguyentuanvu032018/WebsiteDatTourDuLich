<?php $this->layout('layouts/defaultCustomer', ['title' => 'Liên hệ của tôi - VivuTour']) ?>
<?php $this->start('page') ?>
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="text-warning fw-bold text-uppercase small">Hỗ trợ trực tuyến</span>
                <h1 class="h2 fw-bold mb-1">Liên hệ của tôi</h1>
                <p class="text-secondary mb-0">Theo dõi yêu cầu và phản hồi trực tiếp từ VivuTour.</p>
            </div>
            <a href="/contact" class="btn btn-warning fw-bold"><i class="bi bi-plus-lg me-1"></i>Gửi yêu cầu mới</a>
        </div>

        <div class="row g-4">
            <?php foreach ($contacts as $contact) : ?>
                <div class="col-12">
                    <a href="/contacts/my/<?= (int)$contact['contactId'] ?>" class="card border-0 shadow-sm rounded-4 text-decoration-none text-dark">
                        <div class="card-body p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <strong>Yêu cầu #<?= (int)$contact['contactId'] ?></strong>
                                        <?php if ((int)($contact['unreadCount'] ?? 0) > 0) : ?>
                                            <span class="badge bg-danger rounded-pill"><?= (int)$contact['unreadCount'] ?> phản hồi mới</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-secondary text-truncate mb-2"><?= $this->e($contact['message']) ?></p>
                                    <small class="text-secondary"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($contact['createdAt'])) ?></small>
                                </div>
                                <span class="badge text-bg-<?= $contact['status'] === 'RESOLVED' ? 'success' : 'warning' ?> align-self-md-start">
                                    <?= $this->e(contact_status_label($contact['status'])) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>

            <?php if (!$contacts) : ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                        <i class="bi bi-chat-square-text display-4 text-warning"></i>
                        <h2 class="h5 fw-bold mt-3">Bạn chưa gửi yêu cầu nào</h2>
                        <p class="text-secondary">Hãy gửi yêu cầu để nhận tư vấn trực tiếp từ VivuTour.</p>
                        <div><a href="/contact" class="btn btn-warning fw-bold">Gửi yêu cầu</a></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php $this->stop() ?>
