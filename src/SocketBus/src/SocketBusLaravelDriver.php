<?php

namespace ValterLorran\SocketBus;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Support\Str;
use ValterLorran\SocketBus\States\StateModelParser;
use Illuminate\Database\Eloquent\Builder;

class SocketBusLaravelDriver extends Broadcaster
{
    protected $socketbus;

    public function __construct($settings)
    {
        $settings['state_base_class'] = Builder::class;
        $settings['state_model_parser'] = StateModelParser::class;

        $this->socketBus = new SocketBus($settings);
    }

    private function isPrivate(string $channelName)
    {
        if (strpos($channelName, 'private-') === 0 || strpos($channelName, 'presence-') === 0 || strpos($channelName, 'state-') === 0) {
            return true;
        } else if (strpos($channelName, 'public-') === 0) {
            return false;
        }
    }

    private function normalizeChannel(string $channelName)
    {
        return str_replace(['private-', 'presence-', 'public-', 'state-'], '', $channelName);
    }

    private function verifyCanAccessPublicChannel($request, string $channelName)
    {
        foreach ($this->channels as $pattern => $callback) {
            if (! $this->channelNameMatchesPattern($channelName, $pattern)) {
                continue;
            }

            $parameters = $this->extractAuthParameters($pattern, $channelName, $callback);

            $handler = $this->normalizeChannelHandlerToCallable($callback);

            if ($result = $handler(...$parameters)) {
                return $this->validAuthenticationResponse($request, $result);
            }
        }

        throw new AccessDeniedHttpException;
    }

    public function getUserId($request)
    {
        return $this->retrieveUser($request, $request->channel_name)
            ->getAuthIdentifier();
    } 


    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannel($request->channel_name);
        if ($this->isPrivate($request->channel_name)){
            return parent::verifyUserCanAccessChannel(
                $request, $channelName
            );
        }
        return $this->verifyCanAccessPublicChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (Str::startsWith($request->channel_name, 'private-') || Str::startsWith($request->channel_name, 'public-') || Str::startsWith($request->channel_name, 'state-')) {
            return $this->socketBus->auth(
                $request->socket_id, 
                $request->channel_name,
                $result
            );
        }

        return $this->socketBus->authPresence(
            $request->socket_id, 
            $request->channel_name, 
            $this->getUserId($request),
            $result
        );
    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $formatted_channels = $this->formatChannels($channels);

        $response = $this->socketBus->broadcast($formatted_channels, $event, $payload);

        if ($response !== true) {
            throw new BroadcastException(
                "Error trying to broadcast an event to SocketBus\n{$response}"
            );
        }

        return true;
    }
}
