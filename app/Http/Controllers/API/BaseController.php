<?php
  
namespace App\Http\Controllers\API;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use stdClass;
  
class BaseController extends Controller
{
    /**
     * @var string $moduleName The name of the module.
     */
    protected $moduleName;

    use AuthorizesRequests, ValidatesRequests;
    /**
     * success response method.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($data = [], $message = '') : JsonResponse
    {
        return response()->json([
            'status' => true,
            'status_code' => Response::HTTP_OK,
            'message_code' => 'SUCCESS',
            'message' => $message,
            'data' => $data
        ], Response::HTTP_OK);
    }
  
    /**
     * return error response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendError($error, $errorMessages = [], $code = 404) : JsonResponse
    {
        $response = [
            'status' => false,
            'status_code' => $code,
            'message' => $error,
            'message_code' => 'ERROR'
        ];
  
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
  
        return response()->json($response, $code);
    }

    /**
     * return validation response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendValidation($data = [], $message = '', $code = 420) : JsonResponse
    {
        $response = [
            'status' => false,
            'status_code' => $code,
            'message' => $message,
            'message_code' => 'ERROR'
        ];
  
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
  
        return response()->json($response, $code);
    }
}