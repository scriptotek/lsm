<?php

namespace App\Http\Controllers\Auth;

use Aacotroneo\Saml2\Saml2Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Display SAML errors.
     *
     * @return \Illuminate\Http\Response
     */
    public function error()
    {
        return view('auth_error', [
            'errors' => session()->get('saml2_error', []),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @param Saml2Auth $saml
     * @return \Illuminate\Http\Response
     */
    public function samlLogout(Request $request, Saml2Auth $saml)
    {
        $user = $request->user();
        $webid = $user->getIntegration('webid');
        if (!is_null($webid) && isset($webid->account_data['saml_id']) && isset($webid->account_data['saml_session'])) {
            $saml->logout('/', $webid->account_data['saml_id'], $webid->account_data['saml_session']);

            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        return $this->logout($request);
    }

    public function authenticated(Request $request, $user)
    {
        return redirect()->intended($this->redirectPath());
    }

    public function account(Request $request)
    {
        if (!\Auth::check()) {
            return response()->json([
                'user' => null,
            ], 401);
        }

        $user = $request->user();
        $webid = $user->integrations()->where('service_name', '=', 'webid')->first();

        return response()->json([
            'user' => $user,
            'webid' => $webid,
        ]);
    }
}
