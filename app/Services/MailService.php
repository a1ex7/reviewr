<?php

namespace App\Services;

use App\Services\Interfaces\MailServiceInterface;
use App\Services\Requests\Contracts\RequestServiceInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RequestRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Events\OfferWasSent;
use App\Events\UserWasAccepted;
use App\Events\UserWasDeclined;
use App\ReviewRequest; 

class MailService implements MailServiceInterface
{
    private $userRepository;
    private $requestRepository;
    private $notificationRepository;
    
    public function __construct(
        UserRepositoryInterface $userRepository,
        RequestRepositoryInterface $requestRepository,
        NotificationRepositoryInterface $notificationRepository
    ) {
        $this->userRepository = $userRepository;
        $this->requestRepository = $requestRepository;
        $this->notificationRepository = $notificationRepository;
      }

    public function sendNotification($user_id, $req_id, $action) {
        $user = $this->userRepository->findWithRelations($user_id, ['job', 'department']);
        $request = $this->requestRepository->find($req_id);

        switch ($action) {
            case 'accept':
                \Event::fire(new UserWasAccepted($request, $user));
                break;

            case 'decline':
                \Event::fire(new UserWasDeclined($request, $user));
                break;

            case 'sent_offer':
                $author = $this->userRepository->find($request->user_id);

                \Event::fire(new OfferWasSent($request, $user, $author));
                break;
        }
    }

    public function unreadNotifications($user_id)
    {
        $notifications = $this->notificationRepository->findWhere(["user_id" => $user_id]);

        foreach ($notifications as $notification) {
            $this->notificationRepository->delete($notification->id);
        }

        return $notifications;
    }
}
