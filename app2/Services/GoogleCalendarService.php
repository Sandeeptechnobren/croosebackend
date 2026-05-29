<?php
namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{
    public function getClient()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->setAccessType('offline');
        $client->setApprovalPrompt('force');
        $client->setScopes([
            Google_Service_Calendar::CALENDAR_EVENTS
        ]);
        return $client;
    }

    public function createEvent($accessToken, $calendarData)
    {
        $client = $this->getClient();
        $client->setAccessToken($accessToken);

        $service = new Google_Service_Calendar($client);

        $event = new Google_Service_Calendar_Event([
            'summary'     => $calendarData['summary'],
            'location'    => $calendarData['location'],
            'description' => $calendarData['description'],
            'start'       => ['dateTime' => $calendarData['start'], 'timeZone' => 'Asia/Kolkata'],
            'end'         => ['dateTime' => $calendarData['end'], 'timeZone' => 'Asia/Kolkata'],
        ]);

        return $service->events->insert('primary', $event);
    }
}
