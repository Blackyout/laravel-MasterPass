<?php

namespace Imanghafoori\MasterPass\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Imanghafoori\MasterPass\Tests\Stubs\UserModel as User;
use Imanghafoori\MasterPass\Tests\TestCase;

class BaseUnits extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->make(['password' => Hash::make('user_passowrd')]);
    }

    /** @test */
    public function validates_user_credentials_without_master_pass()
    {
        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'user_passowrd']);
        $this->assertTrue($isValid);

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'wrong_password']);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_hashed_master_pass()
    {
        config()->set('master_password.MASTER_PASSWORD', Hash::make('masterpass'));

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'masterpass']);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_plain_text_master_pass()
    {
        config()->set('master_password.MASTER_PASSWORD', 'masterpass');

        $isValid = $this->provider->validateCredentials($this->user, ['password' => 'masterpass']);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function validates_user_credentials_by_dynamic_master_pass()
    {
        $password = 'masterpass';
        $credentials = ['password' => $password];

        Event::partialMock()->shouldReceive('dispatch')
            ->with('masterPass.whatIsIt?', [$this->user, $credentials], true)
            ->once()
            ->andReturn($password);

        $isValid = $this->provider->validateCredentials($this->user, $credentials);

        $this->assertTrue($isValid);
    }

    /** @test */
    public function validates_if_master_pass_can_be_used()
    {
        config()->set('master_password.MASTER_PASSWORD', Hash::make($password = 'masterpass'));

        $credentials = ['password' => $password];

        Event::partialMock()->shouldReceive('dispatch')
            ->with('masterPass.canBeUsed?', [$this->user, $credentials], true)
            ->once()
            ->andReturn(true);

        $isValid = $this->provider->validateCredentials($this->user, $credentials);

        $this->assertTrue($isValid);

        Event::partialMock()->shouldReceive('dispatch')
            ->with('masterPass.canBeUsed?', [$this->user, $credentials], true)
            ->once()
            ->andReturn(false);

        $isValid = $this->provider->validateCredentials($this->user, $credentials);

        $this->assertFalse($isValid);
    }
}
