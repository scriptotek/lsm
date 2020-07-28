<?php

return $settings = [

    /*****
     * One Login Settings
     */

    // If 'strict' is True, then the PHP Toolkit will reject unsigned
    // or unencrypted messages if it expects them signed or encrypted
    // Also will reject the messages if not strictly follow the SAML
    // standard: Destination, NameId, Conditions ... are validated too.
    'strict' => true, //@todo: make this depend on laravel config

    // Enable debug mode (to print errors)
    'debug' => false, //@todo: make this depend on laravel config

    // Service Provider Data that we are deploying
    'sp' => [

        // Specifies constraints on the name identifier to be used to
        // represent the requested subject.
        // Take a look on lib/Saml2/Constants.php to see the NameIdFormat supported
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',

        // Usually x509cert and privateKey of the SP are provided by files placed at
        // the certs folder. But we can also provide them with the following parameters
        'x509cert' => '',
        'privateKey' => '',

        //LARAVEL - You don't need to change anything else on the sp
        // Identifier of the SP entity  (must be a URI)
        //'entityId' => $sp_host . '/metadata', //LARAVEL: This would be set to saml_metadata route
        // Specifies info about where and how the <AuthnResponse> message MUST be
        // returned to the requester, in this case our SP.
        'assertionConsumerService' => [
            // URL Location where the <Response> from the IdP will be returned
            'url' => '', //LARAVEL: This would be set to saml_acs route
        ],
        // Specifies info about where and how the <Logout Response> message MUST be
        // returned to the requester, in this case our SP.
        'singleLogoutService' => [
            // URL Location where the <Response> from the IdP will be returned
            'url' => '', //LARAVEL: This would be set to saml_sls route
        ],
    ],

    // Identity Provider Data that we want connect with our SP
    // https://weblogin.uio.no/simplesaml/saml2/idp/metadata.php?output=xhtml
    'idp' => [
        'entityId' => 'https://weblogin-test.uio.no',
        'singleSignOnService' => [
            'url' => 'https://weblogin-test.uio.no/simplesaml/saml2/idp/SSOService.php',
        ],
        'singleLogoutService' => [
            'url' => 'https://weblogin-test.uio.no/simplesaml/saml2/idp/SingleLogoutService.php',
        ],
        // Public x509 certificate of the IdP
        'x509cert' => 'MIIFHTCCBAWgAwIBAgICA0UwDQYJKoZIhvcNAQEEBQAwga0xCzAJBgNVBAYTAk5PMQ0wCwYDVQQHEwRPc2xvMRswGQYDVQQKExJVbml2ZXJzaXR5IG9mIE9zbG8xOjA4BgNVBAsTMUNlbnRlciBmb3IgSW5mb3JtYXRpb24gVGVjaG5vbG9neSBTZXJ2aWNlcyAoVVNJVCkxEDAOBgNVBAMTB1VTSVQgQ0ExJDAiBgkqhkiG9w0BCQEWFXdlYm1hc3RlckB1c2l0LnVpby5ubzAeFw0xMzEwMjgxMjUxMzlaFw0yMzEwMjYxMjUxMzlaMIGrMQswCQYDVQQGEwJOTzEbMBkGA1UEChMSVW5pdmVyc2l0eSBvZiBPc2xvMTowOAYDVQQLEzFDZW50ZXIgZm9yIEluZm9ybWF0aW9uIFRlY2hub2xvZ3kgU2VydmljZXMgKFVTSVQpMR0wGwYDVQQDExR3ZWJsb2dpbi10ZXN0LnVpby5ubzEkMCIGCSqGSIb3DQEJARYVd2VibWFzdGVyQHVzaXQudWlvLm5vMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArditxDo2pV0pKddtUo1yH7Znjwkf+PSYMMiI+W1EaSAQ3zyayNnF/xGCK0FmPIs0eZACs/0mODn9flhyINjWb224GS45Ry592u6Ta9HTyWrnPvAgYw0TMs/evc76B+XATiQcw4xNFFhqG1hPGYaNHwZaWmngG2F+B5xY5twN/lMwwuD+Q3sJ/B39pfHy+Y6jy0bEDpM2RrqF5tARKnU1iikwViHI0bWlFEAF2piuj/M4Cha5seIxEZhZtLLMfFX7Q7JTwprisL3pwtALNPSm9sZRLCcpFIFRNUzpgf3HNFvsYdyDw/1gXj/2RBzLBImDG1QQxg67tT/OQpL9gqO2CwIDAQABo4IBRTCCAUEwCQYDVR0TBAIwADA4BglghkgBhvhCAQ0EKxYpVzNDQS1zaWduZWQgT3BlblNTTCBHZW5lcmF0ZWQgQ2VydGlmaWNhdGUwHQYDVR0OBBYEFNrZ2Qv6rFnLBBtjKZ9sm8eiWzc8MIHaBgNVHSMEgdIwgc+AFC9SOGQmVepyVHRb5nI8z/GwjeYHoYGzpIGwMIGtMQswCQYDVQQGEwJOTzENMAsGA1UEBxMET3NsbzEbMBkGA1UEChMSVW5pdmVyc2l0eSBvZiBPc2xvMTowOAYDVQQLEzFDZW50ZXIgZm9yIEluZm9ybWF0aW9uIFRlY2hub2xvZ3kgU2VydmljZXMgKFVTSVQpMRAwDgYDVQQDEwdVU0lUIENBMSQwIgYJKoZIhvcNAQkBFhV3ZWJtYXN0ZXJAdXNpdC51aW8ubm+CAQAwDQYJKoZIhvcNAQEEBQADggEBAFfb5ednPCcwA/U6/v4JIHEOREQlXcpcKsQHT9dNjKWSiXUxF1N3KlKRCrdOSe4DVS1BkmgnAUY1GSnT1acxvsBmW1m0qu6cFlr4K8qgkDio2nPQtIv608+e51Iop6JN1B9m1UX14DXxDjozH3bLO95mChhJ00jKdIFtAXOpjZJS8LC/ii/GjKrPUl8Yz9gcmxykkryr+HdZtBUpcLDCnPhkv5Qqkr0SZQBlsr2XzCydll4ZkYUYYLRG/wxlKop9PY3dKMXLf+jlNiVH9YbiRoa1NdxDsFKTpfhnzVNbGbNp4Gkrn4lut007fhMfcq1ZbATR39NzU84WkMjbhGaisNA=',
    ],

    /***
     *
     *  OneLogin advanced settings
     *
     *
     */
    // Security settings
    'security' => [

        /** signatures and encryptions offered */

        // Indicates that the nameID of the <samlp:logoutRequest> sent by this SP
        // will be encrypted.
        'nameIdEncrypted' => false,

        // Indicates whether the <samlp:AuthnRequest> messages sent by this SP
        // will be signed.              [The Metadata of the SP will offer this info]
        'authnRequestsSigned' => false,

        // Indicates whether the <samlp:logoutRequest> messages sent by this SP
        // will be signed.
        'logoutRequestSigned' => false,

        // Indicates whether the <samlp:logoutResponse> messages sent by this SP
        // will be signed.
        'logoutResponseSigned' => false,

        /* Sign the Metadata
         False || True (use sp certs) || array (
                                                    keyFileName => 'metadata.key',
                                                    certFileName => 'metadata.crt'
                                                )
        */
        'signMetadata' => false,


        /** signatures and encryptions required **/

        // Indicates a requirement for the <samlp:Response>, <samlp:LogoutRequest> and
        // <samlp:LogoutResponse> elements received by this SP to be signed.
        'wantMessagesSigned' => false,

        // Indicates a requirement for the <saml:Assertion> elements received by
        // this SP to be signed.        [The Metadata of the SP will offer this info]
        'wantAssertionsSigned' => false,

        // Indicates a requirement for the NameID received by
        // this SP to be encrypted.
        'wantNameIdEncrypted' => false,

        // Authentication context.
        // Set to false and no AuthContext will be sent in the AuthNRequest,
        // Set true or don't present thi parameter and you will get an AuthContext 'exact' 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport'
        // Set an array with the possible auth context values: array ('urn:oasis:names:tc:SAML:2.0:ac:classes:Password', 'urn:oasis:names:tc:SAML:2.0:ac:classes:X509'),
        'requestedAuthnContext' => true,
    ],

    // Contact information template, it is recommended to suply a technical and support contacts
    'contactPerson' => [
        'technical' => [
            'givenName' => 'UiO Realfagsbiblioteket IT-drift',
            'emailAddress' => 'ureal-it-av-drift@ub.uio.no'
        ],
    ],

    // Organization information template, the info in en_US lang is recomended, add more if required
    'organization' => [
        'en-US' => [
            'name' => 'UiO Universitetsbiblioteket',
            'displayname' => 'UiO Universitetsbiblioteket',
            'url' => 'https://ub.uio.no/'
        ],
    ],

    /* Interoperable SAML 2.0 Web Browser SSO Profile [saml2int]   http://saml2int.org/profile/current

       'authnRequestsSigned' => false,    // SP SHOULD NOT sign the <samlp:AuthnRequest>,
                                          // MUST NOT assume that the IdP validates the sign
       'wantAssertionsSigned' => true,
       'wantAssertionsEncrypted' => true, // MUST be enabled if SSL/HTTPs is disabled
       'wantNameIdEncrypted' => false,
    */

];
