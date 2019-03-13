<?php
/**
 * Gmail drafts script
 */

require __DIR__ . '/vendor/autoload.php';

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

/**
 * Returns an authorized API client.
 * 
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Gmail API PHP Quickstart');
    $client->setScopes(Google_Service_Gmail::GMAIL_COMPOSE);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first
    // time.
    $tokenPath = 'token.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


/**
 * Create Draft email.
 *
 * @param Google_Service_Gmail         $service Authorized Gmail API instance.
 * @param Google_Service_Gmail_Message $message Message of the created Draft.
 * @param string                       $user    User email address, defaults to 'me'
 * 
 * @return Google_Service_Gmail_Draft Created Draft.
 */
function createDraft($service, $message, $user='me') {
    $draft = new Google_Service_Gmail_Draft();
    $draft->setMessage($message);
    try {
        $draft = $service->users_drafts->create($user, $draft);
        print "\nDraft ID: " . $draft->getId();
    } catch (Exception $e) {
        print "\nAn error occurred: " . $e->getMessage();
    }
    return $draft;
}
  
  
/**
 * Create a Message from an email formatted string.
 *
 * @param string $email Email formatted string.
 * 
 * @return Google_Service_Gmail_Message Message containing email.
 */
function createMessage($email) {
    $message = new Google_Service_Gmail_Message();
    // base64url encode the string
    //   see http://en.wikipedia.org/wiki/Base64#Implementations_and_history
    $email = strtr(base64_encode($email), array('+' => '-', '/' => '_'));
    $message->setRaw($email);
    return $message;
}


// Get the API client and construct the service object.
$client     = getClient();
$service    = new Google_Service_Gmail($client);
$email_body = file_get_contents("message.txt");
$drafts     = [];
$users      = [
    'bart@bkdsn.com',
    'lorraine.carpenter@cultmontreal.com',
    'alex.rose@cultmontreal.com',
    'dave.jaffer@cultmontreal.com',
    'robertkeaghan@gmail.com',
    'claytonsandhu@gmail.com',
    'jonathan.cummins@gmail.com',
    'lunarlodge@gmail.com',
    'hudon_roxane@hotmail.com',
    'brandonekaufman@gmail.com',
    'erikleijon@gmail.com',
    'cindy.lopez.photo@gmail.com',
    'norarosenthal@rogers.com',
    'mira@sugar4brains.ca',
    'smith.justinea@gmail.com',
    'sarahdeshaies@gmail.com',
    'max@mrwavvy.com',
];

foreach ( $users as $user_email ) {

    $message = "To: "
                . $user_email
                . "\r\nFrom: j.turowetz@gmail.com"
                . "\r\nCc: lorraine.carpenter@cultmontreal.com"
                . "\r\nSubject: Website author bio"
                . "\r\n\r\n"
                . $email_body;

    $message = createMessage($message);
    $drafts[] = createDraft($service, $message);
}

if (!empty($drafts)) {
    print "\n\rCreated some drafts yo!\n";
    // print_r($draft);
    // print "\n";
} else {
    print "No drafts created.\n";
}

