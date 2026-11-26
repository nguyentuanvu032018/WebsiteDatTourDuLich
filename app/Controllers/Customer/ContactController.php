<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactReply;

class ContactController extends Controller
{
    public function create(): void
    {
        $this->sendPage('frontend/pages/contact', [
            'old' => $this->getSavedFormValues(),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function store(): void
    {
        $this->saveFormValues($_POST);
        $message = (new ContactMessage(PDO()))->fill($_POST);
        if (AUTHGUARD()->isUserLoggedIn() && !AUTHGUARD()->isAdmin()) {
            $message->customerUserId = AUTHGUARD()->user()?->userId;
        }

        $errors = $message->validate();
        if ($errors) {
            redirect('/contact', ['errors' => $errors]);
        }
        if (!$message->save()) {
            redirect('/contact', ['error' => 'Không thể lưu yêu cầu liên hệ. Vui lòng thử lại.']);
        }

        unset($_SESSION['form']);
        $destination = $message->customerUserId ? '/contacts/my/' . $message->contactId : '/contact';
        redirect($destination, ['success' => 'Yêu cầu của bạn đã được ghi nhận. Chúng tôi sẽ phản hồi sớm.']);
    }

    public function myContacts(): void
    {
        $this->requireCustomer();
        $userId = (int)AUTHGUARD()->user()->userId;
        $this->sendPage('frontend/contacts/index', [
            'contacts' => (new ContactMessage(PDO()))->forCustomer($userId),
        ]);
    }

    public function showMine(int $contactId): void
    {
        $this->requireCustomer();
        $userId = (int)AUTHGUARD()->user()->userId;
        $contact = (new ContactMessage(PDO()))->findForCustomer($contactId, $userId);
        if (!$contact) {
            $this->sendNotFound();
        }

        $replyModel = new ContactReply(PDO());
        $replyModel->markAdminRepliesRead($contactId);
        $this->sendPage('frontend/contacts/show', [
            'contact' => $contact,
            'replies' => $replyModel->forContact($contactId),
            'errors' => session_get_once('errors', []),
        ]);
    }

    public function reply(int $contactId): void
    {
        $this->requireCustomer();
        $userId = (int)AUTHGUARD()->user()->userId;
        $messageModel = new ContactMessage(PDO());
        if (!$messageModel->findForCustomer($contactId, $userId)) {
            $this->sendNotFound();
        }

        $content = trim((string)($_POST['message'] ?? ''));
        if (mb_strlen($content) < 2 || mb_strlen($content) > 3000) {
            redirect('/contacts/my/' . $contactId, [
                'errors' => ['message' => 'Nội dung phản hồi phải có từ 2 đến 3000 ký tự.'],
            ]);
        }

        if (!(new ContactReply(PDO()))->create($contactId, $userId, 'customer', $content)) {
            redirect('/contacts/my/' . $contactId, ['error' => 'Không thể gửi phản hồi. Vui lòng thử lại.']);
        }

        $messageModel->reopen($contactId);
        redirect('/contacts/my/' . $contactId, ['success' => 'Đã gửi phản hồi đến VivuTour.']);
    }
}
