<?php

namespace BenBjurstrom\Replicate;

use BenBjurstrom\Replicate\Data\PredictionData;
use BenBjurstrom\Replicate\Data\PredictionsData;
use BenBjurstrom\Replicate\Requests\GetPrediction;
use BenBjurstrom\Replicate\Requests\GetPredictions;
use BenBjurstrom\Replicate\Requests\PostDeploymentPrediction;
use BenBjurstrom\Replicate\Requests\PostPrediction;
use Exception;
use Saloon\Contracts\Connector;

class DeploymentResource extends Resource
{
    protected ?string $webhookUrl = null;

    /**
     * @var array<string>
     */
    protected ?array $webhookEvents;

    public function __construct(protected Connector $connector, protected string $deployment_name)
    {
        parent::__construct($connector);
    }

    /**
     * @param  array<string, float|int|string|null>  $input
     *
     * @throws Exception
     */
    public function create(string $version, array $input): PredictionData
    {
        $request = new PostDeploymentPrediction($this->deployment_name, $version, $input);
        if ($this->webhookUrl) {
            // https://replicate.com/changelog/2023-02-10-improved-webhook-events-and-event-filtering
            $request->body()->merge([
                'webhook' => $this->webhookUrl,
                'webhook_events_filter' => $this->webhookEvents,
            ]);
        }

        $response = $this->connector->send($request);

        $data = $response->dtoOrFail();
        if (! $data instanceof PredictionData) {
            throw new Exception('Unexpected data type');
        }

        return $data;
    }

    /**
     * @param  array<string>  $events
     */
    public function withWebhook(string $url, ?array $events = ['completed']): self
    {
        $this->webhookUrl = $url;
        $this->webhookEvents = $events;

        return $this;
    }
}
