<?php

/**
 * Google OAuth — copy this file to google-oauth.php and fill in your credentials.
 *
 * Google Cloud Console → APIs & Services → Credentials → OAuth client (Web application)
 *
 * Authorized redirect URIs (add each environment you use):
 *   http://localhost/sunandspace/auth/google-callback.php
 *   http://localhost:8000/auth/google-callback.php
 *   https://yourdomain.com/auth/google-callback.php
 *
 * Leave redirect_uri empty below to auto-detect from the current host.
 */
return [
    'enabled' => false,
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => '',
];
