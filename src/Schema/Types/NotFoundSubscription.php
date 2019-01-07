<?php

namespace Nuwave\Lighthouse\Schema\Types;

use Illuminate\Http\Request;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Subscriptions\Subscriber;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class NotFoundSubscription extends GraphQLSubscription
{
    /**
     * Authorize subscriber request.
     *
     * @param  Subscriber $subscriber
     * @param  Request    $request
     *
     * @return bool
     */
    public function authorize(Subscriber $subscriber, Request $request): bool
    {
        return false;
    }

    /**
     * Filter subscribers who should receive subscription.
     *
     * @param  Subscriber $subscriber
     * @param  mixed      $root
     *
     * @return bool
     */
    public function filter(Subscriber $subscriber, $root): bool
    {
        return false;
    }

    /**
     * Resolve the subscription.
     *
     * @param  mixed          $root
     * @param  array          $args
     * @param  GraphQLContext $context
     * @param  ResolveInfo    $info
     *
     * @return mixed
     */
    public function resolve($root, array $args, GraphQLContext $context, ResolveInfo $info)
    {
    }
}
