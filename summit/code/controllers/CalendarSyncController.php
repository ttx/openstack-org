<?php

use CalDAVClient\Facade\CalDavClient as CalDavClient;

/**
 * Class CalendarSyncController
 */
final class CalendarSyncController extends AbstractRestfulJsonApi
{


    // Outlook constants
    const OUTLOOK_AUTHORITY = 'https://login.microsoftonline.com/common';
    const OUTLOOK_AUTHORIZE_ENDPOINT = '/oauth2/v2.0/authorize';
    const OUTLOOK_TOKEN_ENDPOINT = '/oauth2/v2.0/token';

    private static $allowed_actions = [
        'loginGoogle',
        'loginOutlook',
        'unSyncCalendar',
        'syncAppleCalendar',
    ];

    private static $url_handlers = [
        'GET login-google'  => 'loginGoogle',
        'GET login-outlook' => 'loginOutlook',
        'DELETE unsync'     => 'unSyncCalendar',
        'PUT login-apple'   => 'syncAppleCalendar',
    ];

    /**
     * @param int $summit_id
     * @return Google_Client
     */
    private function getGoogleClient($summit_id)
    {
        $client = new Google_Client();
        $client->setClientId(GAPI_CLIENT);
        $client->setClientSecret(GAPI_CLIENT_SECRET);
        $client->setRedirectUri(GAPI_REDIRECT_URL);
        $client->setScopes(explode(',', GAPI_SCOPES));
        $client->setApprovalPrompt("force");
        $client->setAccessType("offline");
        $client->setState($summit_id); // we set the state with the summit id
        return $client;
    }

    /**
     * @return \League\OAuth2\Client\Provider\GenericProvider
     */
    private function getOutlookClient()
    {
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => OUTLOOK_APP_ID,
            'clientSecret' => OUTLOOK_APP_PASSWORD,
            'redirectUri' => OUTLOOK_REDIRECT_URL,
            'urlAuthorize' => self::OUTLOOK_AUTHORITY . self::OUTLOOK_AUTHORIZE_ENDPOINT,
            'urlAccessToken' => self::OUTLOOK_AUTHORITY . self::OUTLOOK_TOKEN_ENDPOINT,
            'urlResourceOwnerDetails' => '',
            'scopes' => OUTLOOK_SCOPES
        ]);
        return $oauthClient;
    }

    /**
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    public function loginGoogle(SS_HTTPRequest $request)
    {
        $query_string = $request->getVars();
        $summit_id = isset($query_string['state']) ? $query_string['state'] : false;
        $summit = ($summit_id) ? Summit::get()->byID($summit_id) : Summit::get_active();

        try {
            $client = $this->getGoogleClient($summit->ID);

            if (isset($query_string['code'])) {
                $client->fetchAccessTokenWithAuthCode($query_string['code']);

                $access_token = $client->getAccessToken();
                $refresh_token = $client->getRefreshToken();

                $member = Member::currentUser();

                if ($member) {
                    $member->registerGoogleAuthGrant($summit, json_encode($access_token), $refresh_token);
                }

                return $this->redirect($summit->getScheduleLink());

            }
            // redirect to IDP
            $auth_url = $client->createAuthUrl();
            return $this->redirect($auth_url);
        } catch (Exception $e) {
            SS_Log::log($e, SS_Log::ERR);
            return $this->redirect($summit->getScheduleLink() . 'sync-cal?error_msg=' . $e->getMessage());
        }
    }

    /**
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    public function loginOutlook(SS_HTTPRequest $request)
    {

        $query_string = $request->getVars();
        $summit_id    = isset($query_string['state']) ? $query_string['state'] : false;
        $summit       = ($summit_id) ? Summit::get()->byID($summit_id) : Summit::get_active();

        try {
            $oauthClient = $this->getOutlookClient();

            if (isset($query_string['code'])) {

                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $query_string['code']
                ]);

                $refresh_token = $accessToken->getRefreshToken();

                $member = Member::currentUser();
                if ($member) {
                    $member->registerOutlookAuthGrant($summit, json_encode($accessToken->jsonSerialize()), $refresh_token);
                }

                return $this->redirect($summit->getScheduleLink());

            }
            // redirect to IDP
            $auth_url = $oauthClient->getAuthorizationUrl(['state' => $summit_id]);
            return $this->redirect($auth_url);
        } catch (Exception $e) {
            SS_Log::log($e, SS_Log::ERR);
            return $this->redirect($summit->getScheduleLink() . 'sync-cal?error_msg=' . $e->getMessage());
        }

    }

    /**
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    function unSyncCalendar(SS_HTTPRequest $request)
    {
        try {
            $query_string = $request->getVars();
            $summit_id = isset($query_string['summit_id']) ? intval($query_string['summit_id']) : false;
            $summit = ($summit_id) ? Summit::get()->byID($summit_id) : Summit::get_active();
            $member = Member::currentUser();

            if (is_null($member)) return $this->permissionFailure();

            if (is_null($summit))
                return $this->notFound('summit not found!');

            $res = $member->revokeCalendarSyncInfoForSummit($summit_id);

            if ($res) return $this->ok();

            return $this->validationError(["there isn't any calendar sync set for current member and summit!"]);

        } catch (EntityValidationException $ex1) {
            SS_Log::log($ex1, SS_Log::WARN);
            return $this->validationError($ex1->getMessages());
        } catch (NotFoundEntityException $ex2) {
            SS_Log::log($ex2, SS_Log::WARN);
            return $this->notFound($ex2->getMessage());
        } catch (Exception $ex) {
            SS_Log::log($ex, SS_Log::ERR);
            return $this->serverError();
        }
    }

    /**
     * @param SS_HTTPRequest $request
     * @return SS_HTTPResponse
     */
    function syncAppleCalendar(SS_HTTPRequest $request)
    {
        try {
            $query_string = $request->getVars();
            $summit_id    = isset($query_string['state']) ? intval($query_string['state']) : false;
            $summit       = ($summit_id) ? Summit::get()->byID($summit_id) : Summit::get_active();
            $apple_cred   = $this->getJsonRequest();
            $user         = isset($apple_cred['ios_user']) ? Convert::raw2sql(trim($apple_cred['ios_user'])) : false;
            $app_password = isset($apple_cred['ios_pass']) ? Convert::raw2sql(trim($apple_cred['ios_pass'])) : false;
            $member       = Member::currentUser();

            if (is_null($member)) return $this->permissionFailure();

            if (empty($user) || empty($app_password)) {
                return $this->validationError(['Apple ID and App Password are mandatories!']);
            }

            if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
                return $this->validationError(['user name is not a valid email']);
            }

            if (is_null($summit))
                return $this->notFound('summit not found!');

            $client = new CalDavClient(
                CALDAV_BASE_SERVER_URL,
                $user,
                $app_password
            );

            $res = $client->getUserPrincipal();

            $user_ppal_url = $res->getPrincipalUrl();

            $member = Member::currentUser();
            if ($member) {
                $member->registerICloudAuthGrant($summit, $user, $app_password, $user_ppal_url);
            }
            return $this->ok();
        } catch (EntityValidationException $ex1) {
            SS_Log::log($ex1, SS_Log::WARN);
            return $this->validationError($ex1->getMessages());
        } catch (NotFoundEntityException $ex2) {
            SS_Log::log($ex2, SS_Log::WARN);
            return $this->notFound($ex2->getMessage());
        } catch (\CalDAVClient\Facade\Exceptions\UserUnAuthorizedException $ex3) {
            SS_Log::log($ex3, SS_Log::WARN);
            return $this->validationError(['wrong user or app password!']);
        } catch (Exception $ex) {
            SS_Log::log($ex, SS_Log::ERR);
            return $this->serverError();
        }
    }

    /**
     * @return mixed
     */
    protected function isApiCall()
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function authorize()
    {
        return Member::currentUserID() > 0;
    }
}