<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 20/09/2018
 * Time: 19:52
 */

class YoutubeServiceAPI
{

    public $youtubeServiceAPI;
    protected $client;
    protected $redirect_uri;
    public $auth_uri = "";

    protected function initGoogleClient()
    {
        $this->redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
        $this->client = new Google_Client();
        $oauth_creds = 'oauth-credentials.json';
        $this->client->setAuthConfig($oauth_creds);
        $this->client->setRedirectUri($this->redirect_uri);
        $this->client->addScope("https://www.googleapis.com/auth/youtube");
        $this->youtubeServiceAPI = new Google_Service_YouTube($this->client);

    }

    private function genRedirect_uri()
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    }

    protected function genAccessTokenWithCode()
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
        $this->client->setAccessToken($token);
        // store in the session also
        $_SESSION['upload_token'] = $token;
    }

    // redirect back to the example
    protected function redirectToMainPage()
    {
        header('Location: ' . filter_var($this->redirect_uri, FILTER_SANITIZE_URL));
    }

    protected function uploadAccessToken()
    {
        if (!empty($_SESSION['upload_token'])) {
            $this->client->setAccessToken($_SESSION['upload_token']);
            if ($this->client->isAccessTokenExpired()) {
                unset($_SESSION['upload_token']);
            }
            $this->auth_uri = "";
        }
    }

    protected function genAuthorizationUri()
    {
        if (empty($_SESSION['upload_token'])) {
            $this->auth_uri = $this->client->createAuthUrl();
        }
    }

    protected function checkHasAccessToken()
    {
        if ($this->client->getAccessToken()) {
            return true;
        } else {
            return false;
        }
    }


}