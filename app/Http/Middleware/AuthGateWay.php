<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class AuthGateWay
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $procode = env('PROGRAM_CODE');
        $url = env('GATEWAY_USER_ENDPOINT');
        $params = array(
            'progcode' => $procode,
        );
        $queryString = http_build_query($params);
        $urlWithParams = $url . '?' . $queryString;
        $ch = curl_init($urlWithParams);
        $headers = array(
            'Authorization: '.$request->header("Authorization"),
            'Accept: application/json',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // Process the response
        if ($response === false || $httpCode == 401) {
            // Handle cURL error
            //$error = curl_error($ch);
            return response('401 Unauthorized', 401);
        } else {
            // Handle successful response
            $decodedResponsex = json_decode($response, true);
            $decodedResponse = (object)$decodedResponsex;
            $decodedResponse->SCOPES = (object)$decodedResponsex["SCOPES"];

            if($procode==$decodedResponse->SCOPES->progcode){
                $request->merge([
                    "user"=>$decodedResponsex
                ]);
                $user = new User([
                    "STAFFID"=>$decodedResponse->STAFFID,
                    "PREFIXID"=>$decodedResponse->PREFIXID,
                    "STAFFNAME"=>$decodedResponse->STAFFNAME,
                    "STAFFSURNAME"=>$decodedResponse->STAFFSURNAME,
                    "GENDERID"=>$decodedResponse->GENDERID,
                    "STAFFEMAIL1"=>$decodedResponse->STAFFEMAIL1,
                    "STAFFEMAIL2"=>$decodedResponse->STAFFEMAIL2,
                    "POSID"=>$decodedResponse->POSID,
                    "STAFFFACULTY"=>$decodedResponse->STAFFFACULTY,
                    "POSTYPEID"=>$decodedResponse->POSTYPEID,
                    "PROGCODE"=>$decodedResponse->SCOPES->progcode,
                    "GROUPID"=>$decodedResponse->SCOPES->groupid,
                    "GROUPNAME"=>$decodedResponse->SCOPES->groupname,
                    "STAFFDEPARTMENTNAME"=>$decodedResponse->SCOPES->staffdepartment,
                    "STAFFDEPARTMENT"=>$decodedResponse->SCOPES->staffdepartmentname,
                    "PREFIXFULLNAME"=>$decodedResponse->PREFIXFULLNAME,
                    "POSITIONNAME"=>$decodedResponse->POSITIONNAME
                ]);
                Auth::setUser($user);
            }else{
                return response('401 Unauthorized', 401);
            }
        }
        return $next($request);
    }
}
