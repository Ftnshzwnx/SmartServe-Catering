<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Set global password validation complexity rules
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        // Customize email verification template
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify Email Address')
                ->greeting('Hi, ' . ($notifiable->name ?: $notifiable->full_name) . '!')
                ->line('Please click the button below to verify your email address.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create an account, no further action is required.')
                ->salutation("Regards,\nSmartServe Catering");
        });

        // Customize reset password template
        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset Password Notification')
                ->greeting('Hi, ' . ($notifiable->name ?: $notifiable->full_name) . '!')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('This password reset link will expire in ' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) . ' minutes.')
                ->line('If you did not request a password reset, no further action is required.')
                ->salutation("Regards,\nSmartServe Catering");
        });
    }
}

