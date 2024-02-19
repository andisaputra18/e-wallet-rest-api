<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\Log;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('username', 'password'))) {
            return $this->sendError('Unauthorized', 401);
        }
        $account = User::where('username', $request->username)->first();
        if (!$account || !Hash::check($request->password, $account->password)) {
            return $this->sendError('Login Failed!', ["error" => "User unregirestered"], 401);
        } else {
            $log = new Log;
            $log->user_id = $account->id;
            $log->sign_in = 1;
            $log->save();

            $response['id'] = $account->id;
            $response['name'] = $account->name;
            $response['access_token'] = $account->createToken('auth_token')->plainTextToken;
            $response['token_type'] = "Bearer Token";
        }
        return $this->sendResponse($response, 'User login successfully.');
    }

    public function register(Request $request)
    {
        $user = new User;

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'username' => 'required|unique:users',
            'password' => ['required', Password::min(7)->numbers()->symbols()->uncompromised()]
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $input = $request->all();
        $input['password'] = Hash::make($request->password);
        $user = User::create($input);

        $success['name'] = $user->name;
        $success['token'] = $user->createToken('auth_token')->plainTextToken;
        $success['token_type'] = "Bearer Token";

        return $this->sendResponse($success, 'User register successfully.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->sendResponse([], 'User logout successfully.');
    }
}
