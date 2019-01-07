<?php

namespace Nuwave\Lighthouse\Subscriptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nuwave\Lighthouse\Schema\Types\GraphQLSubscription;
use Nuwave\Lighthouse\Subscriptions\Contracts\SubscriptionIterator;
use Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions;
use Nuwave\Lighthouse\Subscriptions\Contracts\BroadcastsSubscriptions;
use Nuwave\Lighthouse\Subscriptions\Events\BroadcastSubscriptionEvent;

class SubscriptionBroadcaster implements BroadcastsSubscriptions
{
    /**
     * @var \Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions
     */
    protected $auth;

    /**
     * @var \Nuwave\Lighthouse\Subscriptions\StorageManager
     */
    protected $storage;

    /**
     * @var \Nuwave\Lighthouse\Subscriptions\Contracts\SubscriptionIterator
     */
    protected $iterator;

    /**
     * @var \Nuwave\Lighthouse\Subscriptions\BroadcastManager
     */
    protected $broadcastManager;

    /**
     * @param  \Nuwave\Lighthouse\Subscriptions\Contracts\AuthorizesSubscriptions  $auth
     * @param  \Nuwave\Lighthouse\Subscriptions\StorageManager  $storage
     * @param  \Nuwave\Lighthouse\Subscriptions\Contracts\SubscriptionIterator  $iterator
     * @param  \Nuwave\Lighthouse\Subscriptions\BroadcastManager  $broadcastManager
     */
    public function __construct(
        AuthorizesSubscriptions $auth,
        StorageManager $storage,
        SubscriptionIterator $iterator,
        BroadcastManager $broadcastManager
    ) {
        $this->auth = $auth;
        $this->storage = $storage;
        $this->iterator = $iterator;
        $this->broadcastManager = $broadcastManager;
    }

    /**
     * Queue pushing subscription data to subscribers.
     *
     * @param  \Nuwave\Lighthouse\Schema\Types\GraphQLSubscription  $subscription
     * @param  string  $fieldName
     * @param  mixed  $root
     *
     * @return void
     */
    public function queueBroadcast(GraphQLSubscription $subscription, string $fieldName, $root): void
    {
        event(new BroadcastSubscriptionEvent($subscription, $fieldName, $root));
    }

    /**
     * Push subscription data to subscribers.
     *
     * @param  GraphQLSubscription $subscription
     * @param  string              $fieldName
     * @param  mixed               $root
     *
     * @return void
     */
    public function broadcast(GraphQLSubscription $subscription, string $fieldName, $root): void
    {
        $topic = $subscription->decodeTopic($fieldName, $root);

        $subscribers = $this->storage
            ->subscribersByTopic($topic)
            ->filter(function (Subscriber $subscriber) use ($subscription, $root): bool {
                return $subscription->filter($subscriber, $root);
            });

        $this->iterator->process(
            $subscribers,
            function (Subscriber $subscriber) use ($root): void {
                $data = graphql()->executeQuery(
                    $subscriber->queryString,
                    $subscriber->context,
                    $subscriber->args,
                    $subscriber->setRoot($root),
                    $subscriber->operationName
                );

                $this->broadcastManager->broadcast(
                    $subscriber,
                    $data->jsonSerialize()
                );
            }
        );
    }

    /**
     * Authorize the subscription.
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function authorize(Request $request): Response
    {
        return $this->auth->authorize($request)
            ? $this->broadcastManager->authorized($request)
            : $this->broadcastManager->unauthorized($request);
    }
}
