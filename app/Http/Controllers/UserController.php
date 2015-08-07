<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;

use App\Services\Interfaces\RequestServiceInterface;
use App\Services\Interfaces\MailServiceInterface;

class UserController extends Controller
{
    private $requestService;
    private $mailService;
    public function __construct(RequestServiceInterface $requestService, MailServiceInterface $mailService)
    {
        $this->requestService = $requestService;
        $this->mailService = $mailService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return Response::json($this->requestService->getAllUsers(), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
    
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
    
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($userId)
    {
        return Response::json($this->requestService->getOneUserById($userId), 200);
        //return Response::json(\App\User::findOrFail($userId));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
    
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
    
    }

    public function acceptReviewRequest($user_id, $request_id)
    {
        $this->requestService->acceptReviewRequest($user_id, $request_id);
        $this->mailService->sendNotification($user_id, $request_id, 'accept');
    }

    public function declineReviewRequest($user_id, $request_id)
    {
        $this->requestService->declineReviewRequest($user_id, $request_id);
        $this->mailService->sendNotification($user_id, $request_id, 'decline');
    }

    public function offerOnReviewRequest($user_id, $request_id)
    {
        $this->requestService->offerOnReviewRequest($user_id, $request_id);
        $this->mailService->sendNotification($user_id, $request_id, 'sent_offer');

    }
    
}