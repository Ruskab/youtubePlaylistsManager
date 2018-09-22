<?php


function addPanelWithMessage($msg)
{
    return sprintf(
        "<div class=\"w3-panel w3-pale-blue w3-leftbar w3-rightbar w3-border-blue\">
                    <h3>%s</h3>
                </div>"
        , $msg);
}

function addAuthorizationPanelAlert($authUrl)
{
    return sprintf("<div class=\"w3-panel w3-pale-blue w3-leftbar w3-rightbar w3-border-blue\">
            <h3>Authorization Required</h3>
            <p>You need to <a href=\"%s\">authorize access</a> before proceeding.<p>
            </div>", $authUrl);
}

function getOAuthCredentialsFile()
{
    // oauth2 creds
    $oauth_creds = 'oauth-credentials.json';

    if (file_exists($oauth_creds)) {
        return $oauth_creds;
    }

    return false;
}

?>
