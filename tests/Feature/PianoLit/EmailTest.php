<?php

namespace Tests\Feature\PianoLit;

use Illuminate\Support\Facades\{Mail, Notification};
use App\Mail\PianoLit\{WelcomeEmail, TrialExtendedEmail};
use App\Projects\PianoLit\{Admin, User};
use Tests\PianoLitTest;

class EmailTest extends PianoLitTest
{
	/** @test */
	public function a_user_receives_an_email_upon_signing_up()
	{
		Mail::fake();

		$this->register();

		Mail::assertSent(WelcomeEmail::class);
	}

	/** @test */
	public function a_user_receives_an_email_when_their_trial_is_extended()
	{
		Mail::fake();

		$admin = create(Admin::class, ['role' => 'manager']);

		$this->signIn($admin);

		$user = create(User::class);

		$this->post(route('piano-lit.users.extend-trial', $user->id));

		Mail::assertSent(TrialExtendedEmail::class);
	}
}
