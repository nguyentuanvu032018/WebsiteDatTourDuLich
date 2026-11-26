<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactReply;

class AdminContactController extends Controller
{
    private ContactMessage $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new ContactMessage(PDO());
    }

    public function index(): void
    {
        $this->sendPage('admin/contacts/index', ['contacts' => $this->model->all()]);
    }

    public function show(int $contactId): void
    {
        $contact = $this->model->find($contactId);
        if (!$contact) {
            $this->sendNotFound();
        }

        $replyModel = new ContactReply(PDO());
        $replyModel->markCustomerRepliesRead($contactId);
        $this->sendPage('admin/contacts/show', [
            'contact' => $contact,
            'replies' => $replyModel->forContact($contactId),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function reply(int $contactId): void
    {
        $contact = $this->model->find($contactId);
        if (!$contact) {
            $this->sendNotFound();
        }
        if (empty($contact['customerUserId'])) {
            redirect('/admin/contacts/show/' . $contactId, [
                'error' => 'Yêu cầu này được gửi khi chưa đăng nhập nên không thể phản hồi trực tiếp trên website.',
            ]);
        }

        $content = trim((string)($_POST['message'] ?? ''));
        if (mb_strlen($content) < 2 || mb_strlen($content) > 3000) {
            redirect('/admin/contacts/show/' . $contactId, [
                'errors' => ['message' => 'Nội dung phản hồi phải có từ 2 đến 3000 ký tự.'],
            ]);
        }

        $adminId = (int)AUTHGUARD()->user()->userId;
        if (!(new ContactReply(PDO()))->create($contactId, $adminId, 'admin', $content)) {
            redirect('/admin/contacts/show/' . $contactId, ['error' => 'Không thể lưu phản hồi.']);
        }
        if (isset($_POST['markResolved'])) {
            $this->model->markResolved($contactId);
        }
        redirect('/admin/contacts/show/' . $contactId, ['success' => 'Đã gửi phản hồi trực tiếp cho khách hàng.']);
    }

    public function resolve(int $contactId): void
    {
        if (!$this->model->find($contactId)) {
            $this->sendNotFound();
        }
        $this->model->markResolved($contactId);
        redirect('/admin/contacts', ['success' => 'Đã đánh dấu yêu cầu là đã xử lý.']);
    }

    public function destroy(int $contactId): void
    {
        if (!$this->model->delete($contactId)) {
            redirect('/admin/contacts', ['error' => 'Không thể xóa yêu cầu liên hệ.']);
        }
        redirect('/admin/contacts', ['success' => 'Đã xóa yêu cầu liên hệ.']);
    }
}
