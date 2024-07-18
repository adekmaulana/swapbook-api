<?php

namespace App\Repositories;

use App\Facades\ResponseFormatter;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Http\Request;

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
}
