<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use GuzzleHttp\Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\DropboxResource;

class DropboxController extends BaseController
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->clientId = config('services.dropbox.client_id');
        $this->clientSecret = config('services.dropbox.client_secret');
        $this->redirectUri = config('services.dropbox.redirecturl');
    }
    public function redirectToDropbox()
    {
        $url = "https://www.dropbox.com/oauth2/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'token_access_type' => 'offline',
        ]);
        //dd($url);
         return $this->successResponse(
                new DropboxResource($url),
                 trans(
                    'common.fetch_successfully',
                    ['module' => $this->moduleName]
                 )
            );
        //return response()->json("url"=>$url);
      //  return redirect($url);
    }

    public function handleDropboxCallback(Request $request)
    {

        $code = $request->query('code');

        $client = new Client();

        $response = $client->post('https://api.dropboxapi.com/oauth2/token', [
            'form_params' => [
                'code' => $code,
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        dd($data['access_token']);
        // $data will contain access_token, refresh_token (if offline), etc.

        // Save access_token in user model or session depending on your app logic
        
        $user = Auth::user();
        $user->dropbox_tokens = $data['access_token'];
        $user->save();

        return response()->json(['message' => 'Dropbox authorized successfully']);
    }

    public function saveDropboxToken(Request $request)
    {
        $user = Auth::user();
        $user->dropbox_tokens = $request->input('access_token');
        $user->save();
        return $this->successResponse($user,
                trans(
                    'common.update_successfully',
                    ['module' => $this->moduleName]
                ));
    }
    

    public function listFiles(Request $request)
    {
        $user = Auth::user();
        $accessToken = $user->dropbox_tokens;
        //$accessToken = 'sl.u.AFx7Q3cc7BxLEjVyQsI3Yo-50aJXRw3sP9zumdkfkNY_PCd8sIKsfPnKnlXibjnMnZ46QzvyJs9WcmKBIgzlUq6dmjoFhX8awioj05JpcA62Fm6wJ9B5EUp3044BuQvvR5qlSGfKXEvQz5oH3XSKhwRHRcUHqFj1UCKyyrfALq6zwkdC3dRHMhE435gFx8yWI-sz45rZuaRj3WF-ViSWzKU2CckMszBvp6t-KJZ-wz0I3w_AUq5arxQaYqKTeDu1F9CbK0fiKFc17GWNukpFmrjzInT6LM-giPHdCdyoXtJMrlb-L8P4ThpBKmGhNd45qaf-4zVoZjaNyqmZrimGmPWMmmIq1A6ji_TqSEztntB7_kXttaFV_l0Wai1PzaLuL3jB71Gq4kSs-n_SNKlIsZmK1tqnFDeLVd7C0nJXETLyJbqK_M3CR3Q3PFHqUgHN7F6ZnZ0FT8X1SU6328jG4qjPwQSD4RmzwNte3-lpNP-Vk1kHFUe7WWzc7Vwy72tJxyXjr_DoTuDZJOCx-H1MfBsxeY7aWW9W9bBo_8uIzh_8FbgUoUhkSxk8wo2Xfno_XVTCneLudDKhcj2S2KFFAnzVplxmkD9UuOMCS5bI1kjF78XfQ7LltS_NOxy0bUrAtex0AJVlnqhUABazh_9ypUKJO6jIjVsdutU6RQxCecg5S7rDuFdMxQT51lUMe6qS_tnQDUHsXjz0b1MLKvepe10d-yiQz7rQlRJod3Sk_qeR9MP_bq1oP0nJOKbEWkdWXY0k5vYx41NMhvDt8VQRm9Gd_9xBwkRVrHCavimEvawMnkOYWIzgNuFMhVJ2QCXkvi14T5h5SXzSmAX1ALYY0l8U4QRMyvo1Sz1n5lqPCyEJ8ew6zlObk-xbdRhMeILeLhnok8gRRotvSpvrmthdBSPH6t53EWXKPnifyEqssjY0VrP_H7TEM-RGWpYAmrE86E7IH-rFS6-pDhBOE-7vrv9D6kZM8WKwSfJd4B3I4t2zWxlLcYrPHFPH3llywmabp8SUw1Kye_4qTxkFxvT6zUhNiZ9e98Us44wrOYjaBAhbCb4jqC9lTJ5JWGw51I5axDMetfrqjS8eCAy9DXCrCkA9L1osSNqqN3_G2U3moUOGH8BsMiYCyKgJAlusRmYGXTg9oBW_dnfjNoR6ho6_NshkJbH29TIHL-aCXDEGGkXbSVKyljT8ToNeO4I_nV3xYxvlznp7qB_LoWL65WH9SGzL842ilzJjsVj1jjcwbv7JMiBEMZscUv1uOSgdduAKRnB9L30FzslsWoBi4QE5T6tRj41JFLCrKZ8rEyl5ctpN3BmwmWPwtZ9avWK99CeULNiQMyycNF3rLtQxDloRs_Ffiy1Blx5U-eKufUfKINZ6hhpZGqn_R3ldw-Tr9sQQXjnrpEdEdKzbKtZXYhqz4xka';

        if (!$accessToken) {
            return response()->json(['error' => 'Dropbox not authorized'], 401);
        }

        $client = new Client();

        $response = $client->post('https://api.dropboxapi.com/2/files/list_folder', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'path' => '',
                'recursive' => false,
            ],
        ]);

        $files = json_decode($response->getBody(), true);
        //dd($files);
        $files = collect($files['entries'])->map(function ($item) {
            //dd($item);
            return [
                'name' => $item['name'],
                'path' => $item['path_display'],
                'type' => $item['.tag'],
            ];
        });
      //  dd($files);

        return response()->json($files);
    }
}