<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function users(Request $request)
    {
        return $this->userRepository->users($request);
    }

    public function get(Request $request)
    {
        return $this->userRepository->get($request);
    }

    public function update(Request $request)
    {
        return $this->userRepository->update($request);
    }

    public function updateLocation(Request $request)
    {
        return $this->userRepository->updateLocation($request);
    }
}
