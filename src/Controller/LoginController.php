<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class LoginController
{
    private $clientId = '784675dzgbgiwg'; // LinkedIn Client ID
    private $clientSecret = 'WPL_AP1.CZqdONgFMRIShGJL.2C6c/g=='; 
    private $redirectUri = 'http://127.0.0.1:8000/linkedin/callback'; 

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="app_login")
     */
    public function index(): Response
    {
       
        $encodedRedirectUri = urlencode($this->redirectUri);

        
        $authorizationUrl = sprintf(
            'https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=%s&redirect_uri=%s&scope=openid%%20profile%%20email',
            $this->clientId,
            $encodedRedirectUri
        );

        
        return new Response('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Login</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                        background-color: #f4f4f9;
                    }
                    form {
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                        width: 300px;
                    }
                    form button {
                        width: 100%;
                        padding: 10px;
                        border: none;
                        border-radius: 4px;
                        color: #fff;
                        font-size: 16px;
                        cursor: pointer;
                        margin-bottom: 10px;
                        background-color: #0077B5; /* LinkedIn Blue */
                    }
                </style>
            </head>
            <body>
                <form>
                    <button type="button" onclick="window.location.href=\'' . $authorizationUrl . '\'">
                        Sign Up with LinkedIn
                    </button>
                </form>
            </body>
            </html>
        ');
    }

    /**
     * @Route("/linkedin/callback", name="linkedin_callback")
     */
    public function linkedinCallback(Request $request): Response
    {
        
        $this->logger->info('LinkedIn callback received', ['query' => $request->query->all()]);

        
        $authorizationCode = $request->get('code');
        if (!$authorizationCode) {
            
            $this->logger->error('Authorization failed. No code received.', ['query' => $request->query->all()]);
            return new Response('Authorization failed. No code received.');
        }

        try {
            
            $client = new Client();
            $response = $client->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $authorizationCode,
                    'redirect_uri' => $this->redirectUri,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            
            if (isset($data['access_token'])) {
                $accessToken = $data['access_token'];

                
                $this->logger->info('LinkedIn access token retrieved successfully.', ['access_token' => $accessToken]);

               
                $userProfile = $client->get('https://api.linkedin.com/v2/me', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]);
                $profileData = json_decode($userProfile->getBody(), true);

                
                $this->logger->info('User profile data retrieved', ['profile' => $profileData]);

                
                $userEmail = $client->get('https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]);
                $emailData = json_decode($userEmail->getBody(), true);

                
                $this->logger->info('User email retrieved', ['email' => $emailData['elements'][0]['handle~']['emailAddress']]);

                
                return new Response('Access token retrieved successfully: ' . $accessToken . '<br>Email: ' . $emailData['elements'][0]['handle~']['emailAddress']);
            } else {
               
                $this->logger->error('Failed to retrieve access token.', ['response' => $data]);
                return new Response('Failed to retrieve access token.');
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            
            $response = $e->getResponse();
            $errorMessage = $response ? $response->getBody()->getContents() : 'No response received';

            
            $this->logger->error('Guzzle error occurred while exchanging authorization code.', ['error_message' => $errorMessage]);

            return new Response('Guzzle error: ' . $errorMessage);
        } catch (\Exception $e) {
           
            $this->logger->error('An error occurred while handling LinkedIn OAuth.', ['error_message' => $e->getMessage()]);

            return new Response('An error occurred: ' . $e->getMessage());
        }
    }
}
