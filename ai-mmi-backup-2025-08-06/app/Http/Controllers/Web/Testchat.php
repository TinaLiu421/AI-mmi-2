<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Testchat extends WebController {
    
    public function index() {
        $this->callGeminiApi('中午好');
    }
    
    protected function callDialogflowApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
        
            // Authentication credentials path
            $credentialsPath = storage_path('google-credentials.json');

            // Create SessionsClient instance
            $sessionsClient = new SessionsClient(['credentials' => $credentialsPath]);

            // Dialogflow project ID
            $projectId = 'ai-mmi-chat-elgf';

            // Session ID can be any string you define
            $sessionId = uniqid();

            // Specify your language code
            $languageCode = 'en-US';

            // Assemble session name
            $session = $sessionsClient->sessionName($projectId, $sessionId);

            // Create QueryInput instance
            $textInput = (new TextInput())
                ->setText($query)
                ->setLanguageCode($languageCode);

            // set QueryInput
            $queryInput = (new QueryInput())
                ->setText($textInput);

            // Send a request and get a response
            $response = $sessionsClient->detectIntent($session, $queryInput);

            // Parse response
            $queryResult = $response->getQueryResult();
            $result_answer = $queryResult->getFulfillmentText();

            // Close the SessionsClient instance
            $sessionsClient->close();
        }
        
        return $result_answer;
    }
    
    
    protected function callGeminiApi($query = '') {
        // Request URL
        $url = 'https://generativelanguage.googleapis.com/v1/models/gemini-1.0-pro-001:generateContent?key=AIzaSyCAH31vTsmetLcAmkKiWteEuviLFTfm-F8';

        // Request data
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $query
                        )
                    )
                )
            )
        );

        // Convert data to JSON format
        $jsonData = json_encode($data);

        // Set request header
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        );

        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL request and get response
        $response = curl_exec($ch);

        // Check if an error occurred
        if(curl_errno($ch)){
            //echo 'Curl error: ' . curl_error($ch);
            return '';
        }

        // Close cURL resource
        curl_close($ch);
        
        dump($response);
    }
    
    protected function callChatgptApi($query = '') {
        $result_answer = '';
        if(!empty($query)) {
            // add your code here
            
        }
        
        return $result_answer;
    }
}