<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Traits\imageUploadTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\UserServices;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    use imageUploadTrait;

    const FOLDER_PATH = '/Uploads/images/';
    const FOLDER_NAME = 'users';

    public UserServices $userservices;

    public function __construct(UserServices $userservice){

       $this->userservices = $userservice;

    }

    /** Add it to General file */
    const SUCCESS_CODE = 200;
    const VALIDATION_ERROR_CODE = 422;
    const ERROR_CODE = 500;


    /**
     * Registers a user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
    */


    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create($this->getUserData($request));

            if ($request->hasFile('image')) {
                $imageName = $this->uploadImage_Trait($request, 'image', self::FOLDER_PATH, self::FOLDER_NAME);
                $user->update(['image' => $imageName]);
            }

            $token = $user->createToken(User::USER_TOKEN);

            DB::commit();


            return $this->success(
                [
                'userData'=>$user,
                'token'=> $token->plainTextToken,
            ],'User has been register successfully.',self::SUCCESS_CODE);

            // return $this->sendResponse([
            //     'userData' => $user,
            //     'token' => $token->plainTextToken,
            // ],'success','User has been register successfully.',self::SUCCESS_CODE);

        } catch (\Exception $ex) {
            DB::rollBack();
  
            return $this->error($ex->getMessage(),self::ERROR_CODE);
            // return $this->error("something is wrong!",self::ERROR_CODE);

        }
    }

    private function getUserData(RegisterRequest $request)
    {
        return [
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'username' => strstr($request->email, '@', true),
            'phone' => $request->phone,
            'email' => $request->email,
        ];
    }



    /**
     * Logins a user
     *
     * @param LoginRequest $request
     * @return JsonResponse
    */

    public function login(LoginRequest $request){

        $isValid = $this->isValidCredential($request);// radi tjih array json man function isValidCredential

        if (!$isValid['success']) { //sucess value is false

            // return $this->sendResponse($isValid['message'],'failed', Response::HTTP_UNPROCESSABLE_ENTITY);
            return $this->error($isValid['message'], Response::HTTP_UNPROCESSABLE_ENTITY);

        }


        $user = $isValid['user'];

        $token = $user->createToken(User::USER_TOKEN);


        return $this->success([
            'userData' => $user,
            'token' => $token->plainTextToken
        ],'Login successfully!',self::SUCCESS_CODE);

    }

    /**
     * Validates user credential
     *
     * @param LoginRequest $request
     * @return array
    */

    private function isValidCredential(LoginRequest $request) : array
    {
        $data = $request->validated();

        // Find User :
        $user = User::where('email', $data['email'])->first();

        // Email Is Invalide :
        if ($user === null) {

            return [
                'success' => false,
                'message' => 'Invalid Credential'
            ];

        }

        // Password Is Valide :
        if (Hash::check($data['password'], $user->password)) {

            return [
                'success' => true,
                'user' => $user
            ];

        }

        // Password Is Invalide :
        return [
            'success' => false,
            'message' => 'Password is not matched',// hadi nrmlemt mndirhch ngolh bli crendtial is not match
        ];

    }


    /**
     * Logins a user with token
     *
     * @return JsonResponse
    */

    public function loginWithToken() : JsonResponse
    {
        return $this->success(auth()->user(),'login successfully!',self::SUCCESS_CODE);
    }

    /**
     * Logouts a user
     *
     * @param Request $request
     * @return JsonResponse
     */


    ######## this doing deleted just the current token of user :
    // public function logout(Request $request) : JsonResponse
    // {
    //     $request->user()->currentAccessToken()->delete();//delete the current user authanticated token

    //     // return $this->success(null,'Logout successfully!');
    // }

    ######## this doing deleted all user token  :
    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens->each(function ($token, $key) {
            $token->delete();
        });

        return $this->success(null,'Logout successfully!',self::SUCCESS_CODE);
    }


}

