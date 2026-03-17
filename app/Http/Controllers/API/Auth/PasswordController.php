<?php
namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\ForgotResource;
use App\Http\Resources\ResetPasswordResource;
use App\Services\ForgotPasswordService;
use App\Services\UserChangePasswordService;
use Illuminate\Validation\ValidationException;
use Throwable;

class PasswordController extends BaseController
{
    /**
     * ForgotPasswordController constructor.
     *
     * @param ForgotPasswordService $forgotPasswordService
     *                          The service for handling admin forgot password operations.
     */
    private $forgotPasswordService, $changePasswordService;
    public function __construct(ForgotPasswordService $forgotPasswordService, UserChangePasswordService $changePasswordService)
    {
        $this->forgotPasswordService = $forgotPasswordService;
        $this->changePasswordService = $changePasswordService;
    }

    /**
     * Handle the forgot password request.
     *
     * @param Request $request The HTTP request.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws ValidationException
     */
    public function forgot(ForgotPasswordRequest $request)
    {
        try {
            $data = $request->validated();

            $result = $this->forgotPasswordService->sendResetLink($data);

            if ($result) {
                return $this->successResponse(
                    new ForgotResource((object) $result),
                    trans('passwords.sent')
                );
            }
            return $this->sendValidation($data, trans('passwords.reset_password.failed_reset_link'), 420);
            // throw ValidationException::withMessages([
            //     'email' => trans('passwords.reset_password.failed_reset_link'),
            // ]);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Handle the admin password reset request.
     *
     * @param ResetPasswordRequest $resetPasswordRequest The request containing the validated data for password reset.
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function reset(ResetPasswordRequest $resetPasswordRequest)
    {
        try {
            $user = $this->forgotPasswordService->resetPassword($resetPasswordRequest);

            if ($user) {
                return $this->successResponse(
                    new ResetPasswordResource($user),
                    trans('passwords.reset')
                );
            }

            return response()->json([
                'success' => false,
                'message' => trans('passwords.token'), // invalid token
            ], 400);
        } catch (Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }

    /**
     * Change the admin password.
     *
     * @param ChangePasswordRequest $changePasswordRequest
     *                             The request containing the validated data for changing the password.
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $data = $request->validated();

            $result = $this->changePasswordService->changePassword($data);

            if ($result === true) {
                return $this->successResponse(null, trans('passwords.changed'));
            }

            if ($result === 'invalid_current_password') {
                return $this->sendValidation($data, trans('passwords.current_invalid'), 422);
            }

            return $this->sendValidation($data, trans('passwords.reset_failed'), 422);
        } catch (\Throwable $throwable) {
            report($throwable);
            return response()->json(['error' => $throwable->getMessage()], 500);
        }
    }
}
