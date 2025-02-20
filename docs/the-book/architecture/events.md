---
layout:
  title:
    visible: true
  description:
    visible: false
  tableOfContents:
    visible: true
  outline:
    visible: true
  pagination:
    visible: true
---

# Events

{% hint style="info" %}
You can learn more about events in general in [the Symfony documentation](https://symfony.com/doc/current/event\_dispatcher.html).
{% endhint %}

### What is the naming convention of Sylius events?

The events that are designed for the entities have a general naming convention: `sylius.entity_name.event_name`.

The examples of such events are: `sylius.product.pre_update`, `sylius.shop_user.post_create`, `sylius.taxon.pre_create`.

#### Events reference

All Sylius bundles are using [SyliusResourceBundle](https://github.com/Sylius/SyliusResourceBundle/blob/master/docs/index.md), which has some built-in events.

| Event                                 | Description          |
| ------------------------------------- | -------------------- |
| sylius.\<resource>.pre\_create        | Before persist       |
| sylius.\<resource>.post\_create       | After flush          |
| sylius.\<resource>.pre\_update        | Before flush         |
| sylius.\<resource>.post\_update       | After flush          |
| sylius.\<resource>.pre\_delete        | Before remove        |
| sylius.\<resource>.post\_delete       | After flush          |
| sylius.\<resource>.initialize\_create | Before creating view |
| sylius.\<resource>.initialize\_update | Before creating view |

#### CRUD events rules

As you should already know, every resource controller is represented by the `sylius.controller.<resource_name>` service. Several useful events are dispatched during the execution of every default action of this controller. When creating a new resource via the `createAction` method, 2 events occur.

First, before the `persist()` is called on the resource, the `sylius.<resource_name>.pre_create` event is dispatched.

After the data storage is updated, `sylius.<resource_name>.post_create` is triggered.

The same set of events is available for the `update` and `delete` operations. All the dispatches are using the `GenericEvent` class and return the resource object by the `getSubject` method.

#### Checkout events rules

To dispatch checkout steps the event names are overloaded. See `_sylius.event` in `src/Sylius/Bundle/ShopBundle/Resources/config/routing/checkout.yml`

| Event                                     | Description                   |
| ----------------------------------------- | ----------------------------- |
| sylius.order.initialize\_address          | Before creating address view  |
| sylius.order.initialize\_select\_shipping | Before creating shipping view |
| sylius.order.initialize\_payment          | Before creating payment view  |
| sylius.order.initialize\_complete         | Before creating complete view |

### What events are already used in Sylius?

Even though Sylius has events as entry points to each resource only some of these points are already used in our use cases.

The events already used in Sylius are described in the Book alongside the concepts they concern.

{% hint style="info" %}
What is more, you can easily check all the Sylius events in your application by using this command:\
`php bin/console debug:event-dispatcher | grep sylius`
{% endhint %}

### Customizations

{% hint style="info" %}
**Customizing Logic via Events vs. State Machines**

The logic in which Sylius operates can be customized in two ways. The first of them is using the state machines: which is useful when you need to modify business logic for instance modify the flow of the checkout, and the second is listening to the kernel events related to the entities, which helps modify the HTTP responses visible directly to the user, like displaying notifications, sending emails.
{% endhint %}
