<?php

namespace App\Services\Auth;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RoleLocalRepository;
use App\Services\RemoteDataGrabber\Contracts\DataGrabberInterface;
use App\Services\Auth\Contracts\UserUpdater;
use App\Services\Auth\Exceptions\UpdatingFailureException;
use App\User;
use App\Services\RemoteDataGrabber\Exceptions\RemoteDataGrabberException;
use App\Repositories\Contracts\RoleGlobalRepository;
use Illuminate\Database\QueryException;

/**
 * Class ProfileAPIUserUpdater
 * @package App\Services\Auth
 */
class ProfileAPIUserUpdater extends UserUpdater
{
    protected $roleLocalRepository;
    protected $roleGlobalRepository;
    protected $userRepository;
    protected $dataGrabber;

    public function __construct(
        UserRepositoryInterface $userRepository,
        DataGrabberInterface $dataGrabber
    ) {
        $this->userRepository = $userRepository;
        $this->dataGrabber = $dataGrabber;
    }

    /**
     * Updates a base user info from payload information
     *
     * @param $payload
     * @return User $user
     */
    public function updateBaseInfo($payload)
    {
        $userInfo = $payload->toArray();
        $preparedInfo = $this->prepareBaseInfo($userInfo);

        try {
            $user = $this->userRepository->updateFirstOrCreate(
                [
                    'binary_id' => $preparedInfo['binary_id'],
                ],
                $preparedInfo
            );
        } catch (QueryException $e) {
            throw new UpdatingFailureException(
                'Cannot update the base user info: the new email isn\'t unique.'
            );
        }

        return $user;
    }

    /**
     * Updates user info according to the new information from api
     *
     * @param $cookie
     * @param $user
     * @return User $user
     */
    public function updateAdditionalInfo($cookie, $user)
    {
        $url = url(env('AUTH_ME') . '/' . $user->binary_id);
        $curlOptions = [CURLOPT_COOKIE => 'x-access-token=' . $cookie];

        try {
            $remoteInfo = $this->dataGrabber->getFromJson(
                $url,
                $curlOptions
            );
        } catch (RemoteDataGrabberException $e) {
            $message = 'Cannot receive an additional user information. '
                     . $e->getMessage();
            throw new UpdatingFailureException($message, null, $e);
        }

        if (empty($remoteInfo)) {
            $message = 'An additional user information is empty.';
            throw new UpdatingFailureException($message);
        }

        $remoteInfoArray = (array)$remoteInfo[0];
        $preparedUserInfo = $this->prepareAdditionalInfo($remoteInfoArray);

        try {
            $user = $this->userRepository->update($preparedUserInfo, $user->id);
        } catch (RepositoryException $e) {
            throw new UpdatingFailureException(
                'Cannot update the additional user info: the new email isn\'t unique.'
            );
        } catch (QueryException $e) {
            throw new UpdatingFailureException(
                'Cannot update the additional user info: the new email isn\'t unique.'
            );
        }

        return $user;
    }

    protected function prepareBaseInfo(array $arr)
    {
        $this->renameArrayKeys($arr, [
            'id'      => 'binary_id',
            'name'    => 'first_name',
            'surname' => 'last_name',
        ]);

        return $arr;
    }

    /**
     * Renames the keys pfom payload to accessible in our application
     * Attaches a role_id according to the role attribute in the array
     *
     * @param array $arr
     * @return array
     */
    protected function prepareAdditionalInfo(array $arr)
    {
        $this->renameArrayKeys($arr, [
            'name'    => 'first_name',
            'surname' => 'last_name',
        ]);

        $this->attachAvatarInfo($arr);
        return $arr;
    }

    protected function attachAvatarInfo(array &$arr)
    {
        if (array_key_exists('avatar', $arr)) {
            $avatarLinks = (array) $arr['avatar'];
            unset($arr['avatar']);

            $this->renameArrayKeys($avatarLinks, [
                'urlAva'       => 'avatar',
                'thumbnailUrlAva' => 'thumb_avatar',
            ]);

            $arr = array_merge($arr, $avatarLinks);
        }
    }
}