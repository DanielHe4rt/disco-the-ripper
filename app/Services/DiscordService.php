<?php


namespace App\Services;


use GuzzleHttp\Client;

class DiscordService
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => config('credentials.authorization')
            ]
        ]);
    }

    public function getProfile()
    {
        $uri = 'https://discord.com/api/v9/users/866058219859214336/profile';
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }

    public function getGuild($guildId)
    {
        $uri = 'https://discord.com/api/v9/guilds/' . $guildId;
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }

    public function getGuildChannels($guildId)
    {
        $uri = 'https://discord.com/api/v9/guilds/' . $guildId . '/channels';
        $response = $this->client->get($uri);

        return json_decode($response->getBody(), true);
    }


    public function retrieveChannelMessages($channelId, $lastMessageId, $limit = 50)
    {
        $uri = "https://discord.com/api/v9/channels/$channelId/messages?";
        $query = http_build_query([
            'before' => $lastMessageId,
            'limit' => $limit
        ]);

        $response = $this->client->get($uri . $query);

        return json_decode($response->getBody(), true);
    }
}
