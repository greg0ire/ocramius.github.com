<?php

namespace GithubBundle;

use Github\HttpClient\HttpClient;
use Github\Client as GithubClient;
use Sculpin\Core\Event\SourceSetEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GithubRepositoriesGenerator implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Sculpin::EVENT_BEFORE_RUN => 'beforeRun',
        ];
    }

    /**
     * Generate and replace content of pages with (github: repositories)
     * on header and '{replace_on_content}' on content.
     *
     * @param SourceSetEvent $sourceSetEvent
     */
    public function beforeRun(SourceSetEvent $sourceSetEvent)
    {
        $config = require dirname(dirname(__DIR__)) . '/config.php';
        $client = $this->createClientObject($config);

        $repositories = $client->api('user')->repositories($client['github']['user']);
        $sourceSet = $sourceSetEvent->sourceSet();

        foreach ($sourceSet->updatedSources() as $source) {
            if ($source->data()->get('github')
                && 'repositories' == $source->data()->get('github')) {

                $content = str_ireplace(
                    $config['github']['replace_on_content'],
                    $config['github']['render']($repositories),
                    $source->content()
                );

                $source->setContent($content);
                $source->setIsGenerated();
            }
        }
    }

    /**
     * Create a github object configures with information
     * stored on config.php
     *
     * @param $config
     *
     * @return GithubClient
     */
    private function createClientObject(array $config)
    {

        $client = new GithubClient(new HttpClient(array(
            'token' => $config['github']['token'],
            'timeout' => 60,
            'auth_method' => GithubClient::AUTH_URL_TOKEN
        )));

        $client->setHeaders(array(
            'User-Agent: ' . $config['github']['user_agent']
        ));

        return $client;
    }
}
