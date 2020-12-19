<?php
class gmail
{

    public function __construct($client)
    {
        $this->client = $client;
    }

    public function readLables()
    {
        // Get the API client and construct the service object.
        $service = new Google_Service_Gmail($this->client);

        // Print the labels in the user's account.
        $user = 'me';
        $results = $service->users_labels->listUsersLabels($user);

        if (count($results->getLabels()) == 0) {
            print "No labels found.\n";
        } else {
            print "Labels:\n";
            foreach ($results->getLabels() as $label) {
                printf("- %s\n", $label->getName());
            }
        }
    }

    public function readAttachments()
    {
        // Get the API client and construct the service object.
        $service = new Google_Service_Gmail($this->client);

        $user = 'me';
        $optParams = [];
        $optParams['labelIds'] = 'SPAM';

        $messagesResponse = $service->users_messages->listUsersMessages($user, $optParams);
        $messages = $messagesResponse->getMessages();

        foreach ($messages as $message) {
            // echo 'Message with ID: ' . $message->getId();

            $messageId = $message->getId();
            // var_dump($messageId);
            // exit();

            $optParamsGet = [];
            $optParamsGet['format'] = 'full'; // Display message in payload
            $message = $service->users_messages->get($user, $messageId, $optParamsGet);

            $messagePayload = $message->getPayload();
            $headers = $message->getPayload()->getHeaders();
            $parts = $message->getPayload()->getParts();

            $timestamp = ($message->internalDate) / 1000;

            $date = date('Y-m-d H-i-s', $timestamp);

            foreach ($parts as $part) {
                if ($part->mimeType == 'application/pdf') {
                    $attachmentId = $part['body']['attachmentId'];
                }
            }

            $data = $service->users_messages_attachments->get($user, $messageId, $attachmentId);
            $data = $data->data;
            $data = strtr($data, array('-' => '+', '_' => '/'));

            $filename = "WaterMonitorSystem " . $date . ".pdf";
            // echo "<pre>";
            // var_dump($parts);
            // echo "</pre>";

            exit();
            if (!file_exists($filename)) {
                $fh = fopen($filename, "w+");
                fwrite($fh, base64_decode($data));
                fclose($fh);
            } else {
                'File ' . $filename . 'already exists!';
            }

            echo  "\n";
        }
    }


    public function read_bodies()
    {

        function decodeBody($body)
        {
            $rawData = $body;
            $sanitizedData = strtr($rawData, '-_', '+/');

            $decodedMessage = base64_decode($sanitizedData);

            if (!$decodedMessage) {
                $decodedMessage = FALSE;
            }
            return $decodedMessage;
        }
        $gmail = new Google_Service_Gmail($this->client);
        $optParams = [];
        $optParams['labelIds'] = 'SPAM';

        $list = $gmail->users_messages->listUsersMessages('me', $optParams, ['maxResults' => 1000]);


        try {


            while ($list->getMessages() != null) {

                foreach ($list->getMessages() as $mlist) {

                    $message_id = $mlist->id;

                    $optParamsGet2['format'] = 'full';
                    // var_dump($optParamsGet2);
                    // exit;
                    $single_message = $gmail->users_messages->get('me', $message_id, $optParamsGet2);
                    $payload = $single_message->getPayload();

                    // With no attachment, the payload might be directly in the body, encoded.
                    $body = $payload->getBody();
                    $FOUND_BODY = decodeBody($body['data']);

                    // If we didn't find a body, let's look for the parts
                    if (!$FOUND_BODY) {
                        $parts = $payload->getParts();
                        foreach ($parts  as $part) {
                            if ($part['body']) {
                                $FOUND_BODY = decodeBody($part['body']->data);
                                break;
                            }
                            // Last try: if we didn't find the body in the first parts, 
                            // let's loop into the parts of the parts (as @Tholle suggested).
                            if ($part['parts'] && !$FOUND_BODY) {
                                foreach ($part['parts'] as $p) {
                                    // replace 'text/html' by 'text/plain' if you prefer
                                    if ($p['mimeType'] === 'text/html' && $p['body']) {
                                        $FOUND_BODY = decodeBody($p['body']->data);
                                        break;
                                    }
                                }
                            }
                            if ($FOUND_BODY) {
                                break;
                            }
                        }
                    }

                    print_r($FOUND_BODY);
                    // exit;
                    // Finally, print the message ID and the body
                }

                if ($list->getNextPageToken() != null) {
                    $pageToken = $list->getNextPageToken();
                    $list = $gmail->users_messages->listUsersMessages('me', ['pageToken' => $pageToken, 'maxResults' => 10]);
                } else {
                    break;
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function get_subjects()
    {
        $service = new Google_Service_Gmail($this->client);
        $messages = $service->users_messages->listUsersMessages('me', ['q' => 'ahmedatef62437@gmail.com']);

        foreach ($messages->getMessages() as $mail) {

            $message = $service->users_messages->get('me', $mail->getId());
            $headers = $message->getPayload()->getHeaders();
            // var_dump($headers);
            // exit;

            $subject = array_values(array_filter($headers, function ($k) {
                return $k['name'] == 'Subject';
            }));
            echo "<Pre>";

            printf("- %s\n", $subject[0]->getValue());
            echo "</Pre>";
        }
    }
}
