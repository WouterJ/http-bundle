http-bundle
===========
Provides extra HTTP related functionality in Symfony. Note: this package is still under heavy development!

Router Decoration
-----------------
A router decorator is provided to make it easier to resolve arguments required for route generation. They
work like a reversed [ParamConverter](http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html).

Lets pretend we have the following route:
```php
    /**
     * @Route("/profile/{user}/", name="app.view-profile")
     */
    public function viewProfileAction(AppUser $user);
```

A parameter converter would nicely convert the value of your route to an `AppUser`, but how 
would you generate this route? This is still working with scalars:

```php
$router->generate('app.view-profile', ['user' => $user->getId()]);
```
Okay, not really a big problem, but we're passing an id to a parameter which you would expect
to have an `AppUser` if you look at the action. Another problem is that if you want to change
the argument passed along, you will have to update every single usage of this URL. A decent
IDE can get around this issue, but wait! What about your twig templates?

```twig
{{ path('app.view-profile', { 'user': user.id }) }}
{{ path('app.view-profile', { 'user': user.getid }) }}
{{ path('app.view-profile', { 'user': user.getId() }) }}
{{ path('app.view-profile', { 'user': user[id] }) }}

{# I think you see where I'm going at #}
```
This decorator solves that problem with a minor change in your generate call. It will move
the actual parameter resolving out of your controllers, templates etc.

```php
$router->generate('app.view-profile', ['user' => $user]);
```

#### So how is it actually resolved?
You need to do two things:
 - create a resolver
 - write a service definition for it and tag it accordingly

```php
<?php
namespace App\Router;

use App\AppUser;
use Iltar\HttpBundle\Router\ParameterResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class UserParameterResolver implements ParameterResolverInterface
{
    public function supportsParameter($name, $value)
    {
        return 'user' === $name && $value instanceof AppUser
    }

    public function resolveParameter($name, $value)
    {
        return $value->getUsername(); // or getId() if you want the id instead
    }
}

```

And don't forget the service definition.
```yml
services:
    app.router.user-param-resolver:
        class: App\AppUser\UserParameterResolver
        tags:
            - { name: router.parameter_resolver, priority: 150 }
```

### Configuration Example

To cover most cases, two default resolvers are available; the `EntityIdresolver`
and `MappedGetterResolver`. The first converts every entity into an id using
`getId` and is registered with a priority of 100. The latter allows you to map
getters to a method and is registered with a priority of 200.

```yml
iltar_http:
    router:
        entity_id_resolver: true # defaults to false. Converts any known entity to an id (string) getId()
        mapped_getters:
            App\User.username : getUsername # grab the username if the key is username
            App\User          : getId       # Grab getId if nothing more specific is defined
            App\Post          : getSlug     # Always grab getSlug
            App\Reply.id      : getId       # Always grab the ID when the key is 'id'
            App\Reply         : getSlug     # otherwise grab the slug
            App\Message.slug  : getSlug     # only grab getSlug if the key is 'slug'
```

In the above example you can see that `App\User` is registered with a wildcard
on `getId`. That means that if no other -more specific- key is registered, it
will call that method. You can also see that `App\User.username` is defined.
This indicates that if the key is `username`, it will be used instead of the
wildcard. Note that wildcards are always considered less imporant than the
variants with a key. You can also register only a specific key, that means the
rest of the keys will be ignored. With the above example this means that the
`EntityIdResolver` will pick it up if it's an entity.
