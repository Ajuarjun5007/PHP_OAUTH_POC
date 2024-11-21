<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController
{
    /**
     * @Route("/homepage", name="homepage")
     */
    public function index(): Response
    {
        return new Response('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Homepage</title>
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
                    h1 {
                        color: #0077B5; /* LinkedIn Blue */
                    }
                </style>
            </head>
            <body>
                <h1>Welcome to the Homepage!</h1>
            </body>
            </html>
        ');
    }
}
