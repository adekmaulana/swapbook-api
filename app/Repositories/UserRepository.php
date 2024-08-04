<?php

namespace App\Repositories;

use App\Facades\ResponseFormatter;
use App\Interfaces\UserRepositoryInterface;
use App\Models\location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserRepository implements UserRepositoryInterface
{
    public function users(Request $request)
    {
        $params = $request->all();
        foreach ($params as $key => $value) {
            if (is_null($value) || $value == 'null') {
                unset($params[$key]);
            }
        }

        $users = User::where('id', '!=', auth('sanctum')->user()->id)
            ->when(isset($params['name']), function ($q) use ($params) {
                return $q->where('name', 'ilike', '%' . $params['name'] . '%');
            })
            ->orderBy('name')
            ->simplePaginate(
                $params['per_page'] ?? 10,
                ['*'],
                'page',
                $params['page'] ?? 1
            );

        if ($users->isEmpty()) {
            return ResponseFormatter::error(
                404,
                'No users found.',
            );
        }

        return ResponseFormatter::success(
            $users->getCollection(),
            'Users retrieved successfully.'
        );
    }

    public function get(Request $request)
    {
        return ResponseFormatter::success(
            $request->user()->toArray(),
            'User retrieved successfully.'
        );
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'username' => 'string|min:6|max:255|regex:/\w*$/|unique:users,username,' . $request->user()->id,
            'current_password' => 'string|min:8|max:255|current_password:sanctum',
            'new_password' => 'string|min:8|max:255|confirmed',
            'gender' => 'string|in:L,P',
            'instagram' => 'string|max:255',
            'twitter' => 'string|max:255',
            'photo_url' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true,
            );
        }

        $user = $request->user();

        if ($request->has('username')) {
            if (isset($request->check_only) && $request->check_only) {
                return ResponseFormatter::success(
                    null,
                    'Username checked successfully.'
                );
            }
            $user->username = $request->username;
        }

        if ($request->has('image')) {
            $folder = 'images/photo/' . $user->id;
            $old_photo_url = $user->photo_url;
            if ($old_photo_url) {
                if (file_exists($old_photo_url)) {
                    unlink($old_photo_url);
                }
            }

            $photo_url = $request->file('image')->store($folder, 'public');
            $photo_url = asset('storage/' . $photo_url);
            $user->photo_url = $photo_url;
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('current_password') && $request->has('new_password')) {
            if (password_verify($request->current_password, $user->password)) {
                $user->fill([
                    'password' => Hash::make($request->new_password),
                ]);
            } else {
                return ResponseFormatter::error(
                    422,
                    'The password is incorrect.',
                    true,
                );
            }
        }

        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }

        if ($request->has('instagram')) {
            $user->instagram = $request->instagram;
        }

        if ($request->has('twitter')) {
            $user->twitter = $request->twitter;
        }

        $user->save();
        return ResponseFormatter::success(
            $user->toArray(),
            'User updated successfully.'
        );
    }

    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'speed_accuracy' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'time' => 'nullable|numeric',
            'is_mock' => 'nullable|integer',
            'vertical_accuracy' => 'nullable|numeric',
            'heading_accuracy' => 'nullable|numeric',
            'elapsed_realtime_nanos' => 'nullable|numeric',
            'elapsed_realtime_uncertainty_nanos' => 'nullable|numeric',
            'satellite_number' => 'nullable|integer',
            'provider' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true,
            );
        }

        $location = Location::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->all()
        );

        return ResponseFormatter::success(
            $location->toArray(),
            'Location updated successfully.'
        );
    }
}
