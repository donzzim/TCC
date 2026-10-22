<?php

namespace Tests\Unit;

use App\Auth\HybridUserProvider;
use App\Models\User;
use App\Services\Auth\TjesLdapService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class HybridUserProviderTest extends TestCase
{
    public function test_emes_guards_use_the_hybrid_provider(): void
    {
        $this->assertInstanceOf(HybridUserProvider::class, Auth::guard('web')->getProvider());
        $this->assertInstanceOf(HybridUserProvider::class, Auth::guard('pessoa')->getProvider());
    }

    public function test_validates_the_existing_emes_password_without_using_ldap(): void
    {
        $ldap = Mockery::mock(TjesLdapService::class);
        $ldap->shouldNotReceive('authenticateUser');

        $provider = new HybridUserProvider(app('hash'), User::class, $ldap);
        $user = new User;
        $user->password = Hash::make('senha-emes');

        $this->assertTrue($provider->validateCredentials($user, [
            'login' => 'usuario.emes',
            'password' => 'senha-emes',
        ]));
    }

    public function test_uses_tjes_when_the_local_password_does_not_match(): void
    {
        $ldap = Mockery::mock(TjesLdapService::class);
        $ldap->shouldReceive('isEnabled')->once()->andReturnTrue();
        $ldap->shouldReceive('authenticateUser')
            ->once()
            ->with('RCWERNESBACH', 'senha-tjes')
            ->andReturn([
                'dn' => 'cn=16073879725,ou=ID,o=TJES',
                'attributes' => [
                    'tjloginrede' => 'RCWERNESBACH',
                ],
            ]);

        $provider = new HybridUserProvider(app('hash'), User::class, $ldap);
        $user = new User;
        $user->password = Hash::make('outra-senha');

        $this->assertTrue($provider->validateCredentials($user, [
            'login' => 'RCWERNESBACH',
            'password' => 'senha-tjes',
        ]));
    }

    public function test_does_not_accept_a_missing_password(): void
    {
        $ldap = Mockery::mock(TjesLdapService::class);
        $ldap->shouldNotReceive('authenticateUser');

        $provider = new HybridUserProvider(app('hash'), User::class, $ldap);
        $user = new User;
        $user->password = Hash::make('senha-emes');

        $this->assertFalse($provider->validateCredentials($user, [
            'login' => 'usuario.emes',
        ]));
    }
}
