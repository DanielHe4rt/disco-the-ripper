<?php

namespace App\Commands;

use App\Services\DiscordService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class RipperCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'ripper:start';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Inicia o sistema de dump do discord.';

    /**
     * Set how many messages will be saved at each JSON file.
     *
     * @var int
     */
    protected $chunkSize = 100;

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        $service = app(DiscordService::class);
        $profile = $service->getProfile();
        $this->info('Salve ' . $profile['user']['username'] . "#" . $profile['user']['discriminator']);

        $result = $this->choice(
            'Selecione o ID de um servidor para entrarmos.',
            array_map(function ($item) {
                return $item['id'] . " - " . $item['nick'] ?? 'sem nickname predefinido';
            }, $profile['mutual_guilds'])
        );
        $guildId = explode(' - ', $result)[0];

        $guild = $service->getGuild($guildId);

        $channels = $service->getGuildChannels($guildId);

        $selectChannel = $this->choice(
            'Selecione a sala que você quer ripar.',
            $this->transformServerChannels($channels)
        );

        [$channelName, $channelId, $lastMessageId] = explode(' - ', $selectChannel);

        $messages = $this->fetchAllMessagesFromChannel($service, $channelId, $lastMessageId);

        foreach ($messages as $message) {
            $this->info($message['author']['username'] . ': ' . $message['content']);
        }
        $baseFileName =  date('YmdHis') . '-' . Str::slug($guild['name']) . '-' . Str::slug($channelName) . '-' . $channelId;
        dump($baseFileName);
        $this->saveMessages($baseFileName, $messages);

        $this->info('Tá feito! Encontre os arquivos na sua pasta storage!');
    }

    private function transformServerChannels($channels)
    {
        $result = [];
        foreach ($channels as $channel) {
            if ($channel['type'] == 0) {
                $result[] = $channel['name'] . " - " . $channel['id'] . ' - ' . $channel['last_message_id'];
            }
        }
        return $result;
    }

    private function fetchAllMessagesFromChannel($service, $channelId, $lastMessageId, array $result = []): array
    {
        usleep(5000);
        $this->info('Fetched ' . count($result) . ' messages.');
        $messages = $service->retrieveChannelMessages($channelId, $lastMessageId);
        $result = array_merge($result, $messages);

        if (count($messages) != 0) {
            $lastMessageId = $messages[count($messages) - 1 ]['id'];
            return $this->fetchAllMessagesFromChannel($service, $channelId, $lastMessageId, $result);
        }

        return $result;
    }

    private function saveMessages(string $filename, array $messages)
    {
        collect($messages)
            ->chunk($this->chunkSize)
            ->each(function ($messageChunk, $key) use ($filename) {
                $realName = $filename . "-page-" . ++$key . ".json";
                file_put_contents(storage_path('app/channels/') . $realName, json_encode($messageChunk));
            });
    }
}
