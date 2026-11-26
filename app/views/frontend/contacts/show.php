<?php $this->layout('layouts/defaultCustomer', ['title' => 'Trao đổi với VivuTour']) ?>
<?php $this->start('page') ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="/contacts/my" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Quay lại</a>
                    <span class="badge text-bg-<?= $contact['status'] === 'RESOLVED' ? 'success' : 'warning' ?>">
                        <?= $this->e(contact_status_label($contact['status'])) ?>
                    </span>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white p-4 border-bottom">
                        <h1 class="h4 fw-bold mb-1">Yêu cầu #<?= (int)$contact['contactId'] ?></h1>
                        <small class="text-secondary">Gửi lúc <?= date('d/m/Y H:i', strtotime($contact['createdAt'])) ?></small>
                    </div>
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-grid gap-3 mb-4">
                            <div class="d-flex justify-content-end">
                                <div class="bg-light border rounded-4 p-3" style="max-width:85%">
                                    <div class="small fw-bold mb-1">Bạn</div>
                                    <div class="preserve-lines"><?= nl2br($this->e($contact['message'])) ?></div>
                                    <div class="small text-secondary mt-2"><?= date('d/m/Y H:i', strtotime($contact['createdAt'])) ?></div>
                                </div>
                            </div>

                            <?php foreach ($replies as $reply) : ?>
                                <?php $isAdmin = $reply['senderRole'] === 'admin'; ?>
                                <div class="d-flex <?= $isAdmin ? 'justify-content-start' : 'justify-content-end' ?>">
                                    <div class="rounded-4 p-3 <?= $isAdmin ? 'bg-warning bg-opacity-25' : 'bg-light border' ?>" style="max-width:85%">
                                        <div class="small fw-bold mb-1">
                                            <?= $isAdmin ? 'Admin · VivuTour' : 'Bạn' ?>
                                        </div>
                                        <div class="preserve-lines"><?= nl2br($this->e($reply['message'])) ?></div>
                                        <div class="small text-secondary mt-2"><?= date('d/m/Y H:i', strtotime($reply['createdAt'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form action="/contacts/my/<?= (int)$contact['contactId'] ?>/reply" method="POST" class="border-top pt-4">
                            <?= csrf_field() ?>
                            <label class="form-label fw-semibold" for="customerReply">Trao đổi thêm với VivuTour</label>
                            <textarea id="customerReply" name="message" rows="4" maxlength="3000"
                                      class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                      placeholder="Nhập câu hỏi tiếp theo..." required></textarea>
                            <?php if (isset($errors['message'])) : ?>
                                <div class="invalid-feedback"><?= $this->e($errors['message']) ?></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-warning fw-bold px-4" type="submit"><i class="bi bi-send me-1"></i>Gửi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $this->stop() ?>
