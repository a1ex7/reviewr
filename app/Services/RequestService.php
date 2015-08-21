<?php

namespace App\Services;

use App\Services\Interfaces\RequestServiceInterface;
use App\Notification;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\RequestRepositoryInterface;
use App\Repositories\Interfaces\TagRepositoryInterface;
use App;
use Illuminate\Support\Facades\Auth;

class RequestService implements RequestServiceInterface
{
    private $userRepository;
    private $requestRepository;
    private $tagRepository;
    
    public function __construct(
    UserRepositoryInterface $userRepository,
    RequestRepositoryInterface $requestRepository,
    TagRepositoryInterface $tagRepository
) {
    $this->userRepository = $userRepository;
    $this->requestRepository = $requestRepository;
    $this->tagRepository = $tagRepository;
}

    public function getAllUsers()
    {
        return $this->userRepository->all();
    }

    public function getAllRequests()
    {
        return $this->requestRepository->all();
    }

    public function getMyRequests()
    {
        return $this->requestRepository->findByField('user_id', Auth::user()->id);
    }

    public function getOfferedRequests()
    {
        return $this->requestRepository->getOffered(Auth::user()->id);
    }

    public function getAllTags()
    {
        return $this->tagRepository->all();
    }

    public function createRequest($data)
    {
        $request = $this->requestRepository->create($data);
        foreach ($data->tags as $tag) {
            $tag = $this->tagRepository->create($tag);
            $request->tags()->attach($tag->id);
            $request->save();
        }
        return $request;
    }

    public function deleteRequestById($id)
    {
        return $this->requestRepository->delete($id);
    }

    public function updateRequest($id, $data)
    {
        //dd($id, $data);
        $request = $this->requestRepository->update($id, $data);
        // tags for request update
        //foreach ($data->tags as $tag_id => $tag_item) {
        //    $tag = $this->tagRepository->update($tag_id, $tag_item);
        //    $tag->save();
        //    $request->save();
        //}

        return $request;
    }

    public function getSpecificRequestOffers($id)
    {
        return $this->requestRepository->getOffersById($id);
    }

    public function getSpecificRequestTags($id)
    {
        return $this->requestRepository->getTagsById($id);
    }

    public function getOneUserById($id)
    {   
        return $this->userRepository->OneById($id);
          
    }

    public function getOneRequestById($id)
    {
        return $this->requestRepository->OneById($id);
    }

    public function acceptReviewRequest($user_id, $req_id)
    {
        $user =  $this->getOneUserById($user_id);
        foreach ($user->requests as $request) {
            if ($request->id == $req_id) {
                $request->pivot->isAccepted = 1; 
                $request->pivot->save();
                $notification = new Notification();
                $author = $this->getOneUserById($request->user['id']);
                $notification->title = 'User ' . $author->first_name .'   '. $author->last_name . ' accept your offer for request ' . $request->title;
                $notification->user_id = $user_id;
                $notification->save();
                $notification->user()->associate($notification);
                return response()->json(['message'=> 'success'], 200);
            }
        }
        return response()->json(['message'=> 'fail'], 500);
        
    }

    public function declineReviewRequest($user_id, $req_id)
    {
        $user =  $this->getOneUserById($user_id);
        foreach ($user->requests as $request) {
            if ($request->id == $req_id) {
                $request->pivot->isAccepted = 0; 
                $request->pivot->save();

                $notification = new Notification();
                $author = $this->getOneUserById($request->user['id']);
                $notification->title = 'User ' . $user->first_name .'   '. $user->last_name . ' decline your offer for request ' . $request->title;
                $notification->user_id = $user_id;
                $notification->save();
                $notification->user()->associate($notification);
                return response()->json(['message'=> 'success'], 200);
            }
        }
        return response()->json(['message'=> 'fail'], 500);
    }

    public function offerOnReviewRequest($user_id, $req_id) 
    {
        $request = $this->getOneRequestById($req_id);
        $user = $this->getOneUserById($user_id);
        $author = $this->getOneUserById($request->user['id']);  
        
        $notification = new Notification();
        $notification->title = 'User ' . $user->first_name .'   '. $user->last_name . ' send you offer for request ' . $request->title;
        $notification->user_id = $author->id;
        $notification->save();

        
        $notification->user()->associate($notification);

        foreach ($user->requests as $request) {
            if ($request->id == $req_id) {
                return response()->json(['message'=> 'fail'], 500);
            }
        }
        $user->requests()->attach($req_id);
        return response()->json(['message'=> 'success'], 200);
    }


    public function checkUserForRequest($user_id, $req_id) {
        $user = $this->getOneUserById($user_id);
        foreach ($user->requests as $request) {
            if ($request->id == $req_id) {
                return response()->json(['message'=> 'success'], 200);
            }
        }
      //  return response()->json(['message'=> 'fail'], 500);
    }

    public function offerOffReviewRequest($user, $req_id) {
        $request = $this->getOneRequestById($req_id);
        $user = $this->getOneUserById($user->id);
        $author = $this->getOneUserById($request->user['id']); 
        $notification = new Notification();
        $notification->title = 'User ' . $user->first_name .'   '. $user->last_name . ' undo his offer for request ' . $request->title;
        $notification->user_id = $author->id;
        $notification->save();

        $user->requests()->detach($req_id);
        return response()->json(['message'=> 'success'], 200);
    }

    public function usersForRequest($request_id) {

        $request = $this->getOneRequestById($request_id);
        $users = $request->users()->wherePivot('isAccepted', 1)->get();
        return $users;
    }
 
    public function searchTagsByKeyWord($keyword)
    {
        return $this->tagRepository->searchByKeyWord($keyword);
    }

    public function getOfferedReviewRequests_()
    {
        return $this->requestRepository->getOffered_(Auth::user()->id);
    }

    public function getPopularReviewRequests()
    {
        return $this->requestRepository->getPopular();
    }

    public function getHighestRatedReviewRequests()
    {
        return $this->requestRepository->getHighestRated();
    }

    public function getReviewRequestsByGroupId($id)
    {
        return $this->requestRepository->getByGroupId($id);
    }
    
    public function checkVote($request_id, $user_id)
    {
        return $this->requestRepository->checkVote($request_id, $user_id);
    }

    public function reputationUp($request_id, $user_id)
    {
        return $this->requestRepository->reputationUp($request_id, $user_id);
    }

    public function reputationDown($request_id, $user_id)
    {
        return $this->requestRepository->reputationDown($request_id, $user_id);
    }

    public function getHighestReputationUsers()
    {
        return $this->userRepository->getByHighestReputation();
    }

    public function getHighRept($number) 
    {
        return $this->requestRepository->getHighRept($number);
    }

    public function upcomingReviewRequests()
    {
        return $this->requestRepository->upcomingReviewRequests();
    }
    
    public function lastNReviewRequests($number)
    {
        return $this->requestRepository->lastNReviewRequests($number);
    }

    public function upcomingForPeriodReviewRequests($period)
    {
        return $this->requestRepository->upcomingForPeriodReviewRequests($period);
    }

    public function getReviewRequestsByTagId($tag_id)
    {
        return $this->requestRepository->getByTagId($tag_id);
    }

    public function getPopularTags()
    {
        return $this->tagRepository->getPopular();
    }


    public function unreadNotifications($user)
    {
        return $this->userRepository->unreadNotifications($user);
    }
    
    public function getReviewRequestsByUserId($user_id)
    {
        return $this->requestRepository->getByUserId($user_id);
    }
}