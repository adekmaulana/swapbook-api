<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface UserRepositoryInterface
{
    public function users(Request $request);
    public function get(Request $request);
    public function update(Request $request);
    public function updateLocation(Request $request);
}
