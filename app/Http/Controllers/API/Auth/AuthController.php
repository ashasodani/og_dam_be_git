<?php
   
namespace App\Http\Controllers\API\Auth;
   
use App\Http\Controllers\API\BaseController as BaseController;
use App\Interfaces\LoginServiceInterface;
use App\Interfaces\LogoutServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\{
    SignUpResource,
    SignInResource
};
use App\Http\Requests\Auth\{
    SignInRequest,
    SignUpRequest,
    OnBoardRequest
};
use Throwable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;

class AuthController extends BaseController
{
    /**
     * @var UserService The service for handling  user-related operations.
     */
    protected $userService;
    /**
     * @var LoginServiceInterface $loginService The service for handling api login operations.
     */
    protected $loginService;

    /**
     * @var LogoutServiceInterface $logoutServiceService The service for handling api logout operations.
     */
    protected $logoutService;

    /**
     * AuthController constructor.
     * @param UserService    $userService    The service for handling user-related operations.
     * @param LoginServiceInterface $loginService The service for handling api login operations.
     * @param LogoutServiceInterface $logoutServiceService The service for handling api logout operations.
     */
    public function __construct(
        LoginServiceInterface $loginService,
        LogoutServiceInterface $logoutService,
        UserService $userService
    ) {
        $this->userService = $userService;
        $this->loginService = $loginService;
        $this->logoutService = $logoutService;
    }
    /**
     * Handle the api Register request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(SignUpRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $user = $this->userService->createUser($data);
        
            return $this->successResponse(
                new SignUpResource($user),
                trans(
                    'common.signup_successfully',
                    ['module' =>  'User']
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
   
    /**
     * Handle the api Login request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(SignInRequest $signInRequest) : JsonResponse
    {
        try {
            $credentials = collect($signInRequest->only('email', 'password'))
            ->map(function ($value) {
                return preg_replace('/\s+/', '', $value); // removes ALL whitespace
            })
            ->toArray();
            $credentials['email'] = strtolower($credentials['email']);

           // $credentials = $signInRequest->only('email', 'password');
            $remember = $signInRequest->has('remember');
            if ($this->loginService->login($credentials, $remember)) {
                $user = $this->loginService->getUser(); 
                $user->last_login = Carbon::now();
                $user->save();
                
                return $this->successResponse(
                        new SignInResource($user),
                        trans(
                            'common.signin_successfully',
                            ['module' =>  'User']
                        )
                    );
            }
            return $this->sendError('Unauthorised.', trans('auth.failed'), 401);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function onBoard(OnBoardRequest $onboardRequest) : JsonResponse
    {
        try {
            $data = $onboardRequest->validated();
            $users = $this->userService->createUser($data);
            $users->last_login = Carbon::now();
            $users->save();
            $notifyUser = $this->userService->notifyUserForOnboarding($users);
            $credentials = $onboardRequest->only('email', 'password');
            $remember = $onboardRequest->has('remember');
            if ($this->loginService->login($credentials, $remember)) {
                $user = $this->loginService->getUser(); 
                
                    return $this->successResponse(
                        new SignInResource($user),
                        trans(
                            'common.signin_successfully',
                            ['module' =>  'User']
                        )
                    );
            }
            return $this->sendError('Unauthorised.', trans('auth.failed'), 401);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Handle the api Logout request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() : JsonResponse
    {
        try {
            $user = $this->logoutService->logout();
            return $this->successResponse($user,
                trans(
                    'common.signout_successfully',
                    ['module' =>  'User']
                )
            );
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    public function onBoardVerify(Request $request) : JsonResponse
    {
        try {
            $data = $request->get('token');
            $decoded = base64_decode($data);
            $result = $this->userService->checkInvitedUser($decoded);
            $userResult = $this->userService->checkUser($decoded);
            if($result){
                return $this->successResponse(
                    $result,
                    trans(
                        'common.verify_token',
                        ['module' =>  'User']
                    )
                );
            }
            if($userResult){
               return $this->sendError('user exist', 'user exist', 422);
            }
            return $this->sendError('Not found.', 'user not found', 404);
        } catch (Throwable $throwable) {
            report($throwable);
            return $this->sendError('Not found.', 'user not found', 404);
        }
    }
    public function checkToken(Request $request){
         $token = $request->bearerToken(); // Get from Authorization: Bearer <token>
        // $user=Auth::user();

            //if($user){
                return $this->successResponse(
                    [],
                    trans(
                        'common.verify_token',
                        ['module' =>  'User']
                    )
                );
           // }
    }
}