<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ClearRememberMeTest extends AbstractWebTestCase
{
    /**
     * @dataProvider provideClientOptions
     */
    public function testUserChangeClearsCookie(array $options)
    {
        $client = $this->createClient($options);

        $client->request('POST', '/login', [
            '_username' => 'johannes',
            '_password' => 'test',
        ]);

        $this->assertSame(302, $client->getResponse()->getStatusCode());
        $cookieJar = $client->getCookieJar();
        $this->assertNotNull($cookieJar->get('REMEMBERME'));

        $client->request('GET', '/foo');
        $this->assertRedirect($client->getResponse(), '/login');
        $this->assertNull($cookieJar->get('REMEMBERME'));
    }

    public function provideClientOptions()
    {
        yield [['test_case' => 'ClearRememberMe', 'root_config' => 'config.yml', 'enable_authenticator_manager' => true]];
        yield [['test_case' => 'ClearRememberMe', 'root_config' => 'legacy_config.yml', 'enable_authenticator_manager' => false]];
    }
}

class RememberMeFooController
{
    public function __invoke(UserInterface $user)
    {
        return new Response($user->getUsername());
    }
}

class RememberMeUserProvider implements UserProviderInterface
{
    private $inner;

    public function __construct(InMemoryUserProvider $inner)
    {
        $this->inner = $inner;
    }

    public function loadUserByUsername($username)
    {
        return $this->inner->loadUserByUsername($username);
    }

    public function refreshUser(UserInterface $user)
    {
        $user = $this->inner->refreshUser($user);

        $alterUser = \Closure::bind(function (InMemoryUser $user) { $user->password = 'foo'; }, null, InMemoryUser::class);
        $alterUser($user);

        return $user;
    }

    public function supportsClass($class)
    {
        return $this->inner->supportsClass($class);
    }
}
