<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Creativeorange\Gravatar\Facades\Gravatar;
use Creativeorange\Gravatar\Exceptions\InvalidEmailException;

class User extends Model implements AuthenticatableContract, CanResetPasswordContract
{
    use Authenticatable, CanResetPassword;

    protected $table = 'users';

    public $timestamps = false;

    protected $fillable = ['first_name', 'last_name', 'email', 'avatar',
        'thumb_avatar', 'country', 'city', 'bid', 'role', 'job_id',
        'department_id', 'binary_id'];

    protected $hidden = [ 'password', 'remember_token' ];

    public static $rules = array(
        'first_name'=>'required|min:2|alpha',
        'last_name'=>'required|min:2|alpha',
        'email'=>'required|min:2|email',
    );

    public function comments()
    {
        return $this->hasMany('App\Comment');
    }

    public function job()
    {
        return $this->belongsTo('App\Job');
    }

    public function department()
    {
        return $this->belongsTo('App\Department');
    }

    public function requests()
    {
        return $this->belongsToMany('App\ReviewRequest')
                    ->withPivot('isAccepted', 'status');
    }

    public function badges()
    {
        return $this->belongsToMany('App\Badge');
    }

    public function notifications()
    {
        return $this->hasMany('App\Notification');
    }

    public function getAvatarAttribute($avatar)
    {
        return $this->getAvatarPlaceholder($avatar);
    }

    public function getThumbAvatarAttribute($avatar)
    {
        return $this->getAvatarPlaceholder($avatar);
    }

    private function getAvatarPlaceholder($avatar)
    {
        if (empty($avatar)) {
            try {
                return Gravatar::get(
                    $this->attributes['email'],
                    [
                        'fallback' => 'identicon'
                    ]
                );
            } catch (InvalidEmailException $e) {
                return Gravatar::get(
                    'example@example.com',
                    [
                        'fallback' => 'identicon'
                    ]
                );
            }
        }

        return $avatar;
    }
}
